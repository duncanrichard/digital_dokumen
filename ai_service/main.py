"""Layanan AI lokal untuk indeks dan pencarian semantik dokumen PDF."""
import json
import os
from pathlib import Path

import fitz
import numpy as np
from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel, Field
from sentence_transformers import SentenceTransformer

APP_DIR = Path(__file__).parent
DATA_FILE = APP_DIR / "data" / "documents.npz"
TOKEN = os.getenv("DOCUMENT_AI_TOKEN", "")
MODEL = os.getenv("DOCUMENT_AI_MODEL", "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2")
model = SentenceTransformer(MODEL)
app = FastAPI(title="Document AI", version="1.0")

class IndexRequest(BaseModel):
    document_id: str
    file_path: str
    title: str = ""
    document_number: str = ""
    document_type: str = ""
    department: str = ""
    publish_date: str = ""
    notes: str = ""
    status: str = ""

class SearchRequest(BaseModel):
    query: str = Field(min_length=2, max_length=200)
    limit: int = Field(default=8, ge=1, le=30)

def authorize(value: str | None):
    if TOKEN and value != f"Bearer {TOKEN}":
        raise HTTPException(401, "Token layanan AI tidak valid")

def load_index():
    if not DATA_FILE.exists(): return [], np.empty((0, 384), dtype=np.float32)
    data = np.load(DATA_FILE, allow_pickle=True)
    return json.loads(str(data["documents"].item())), data["vectors"]

def save_index(documents, vectors):
    DATA_FILE.parent.mkdir(parents=True, exist_ok=True)
    np.savez_compressed(DATA_FILE, documents=json.dumps(documents), vectors=vectors)

def extract_text(file_path: str) -> str:
    path = Path(file_path).resolve()
    storage_root = Path(os.getenv("DOCUMENT_STORAGE_PATH", "/var/www/dokumen/storage/app/public")).resolve()
    if storage_root not in path.parents or path.suffix.lower() != ".pdf":
        raise HTTPException(422, "File harus PDF di dalam penyimpanan dokumen")
    if not path.is_file(): raise HTTPException(404, "File dokumen tidak ditemukan")
    with fitz.open(path) as pdf:
        return "\n".join(page.get_text() for page in pdf)

@app.get("/health")
def health(): return {"status": "ok", "model": MODEL}

@app.post("/index")
def index_document(item: IndexRequest, authorization: str | None = Header(default=None)):
    authorize(authorization)
    text = extract_text(item.file_path).strip()
    if not text: raise HTTPException(422, "PDF tidak memiliki teks. Gunakan OCR untuk PDF hasil scan.")
    excerpt = " ".join(text.split())[:500]
    content = "\n".join([
        item.title, item.document_number, item.document_type, item.department,
        item.publish_date, item.status, item.notes, text[:12000],
    ])
    vector = model.encode([content], normalize_embeddings=True).astype(np.float32)
    documents, vectors = load_index()
    documents = [doc for doc in documents if doc["document_id"] != item.document_id]
    vectors = np.array([vectors[i] for i, doc in enumerate(load_index()[0]) if doc["document_id"] != item.document_id], dtype=np.float32)
    if vectors.size == 0: vectors = np.empty((0, vector.shape[1]), dtype=np.float32)
    documents.append({"document_id": item.document_id, "excerpt": excerpt, "content": content.lower()})
    save_index(documents, np.vstack([vectors, vector]))
    return {"indexed": item.document_id}

@app.post("/search")
def search(request: SearchRequest, authorization: str | None = Header(default=None)):
    authorize(authorization)
    documents, vectors = load_index()
    if not documents: return {"results": []}
    query = model.encode([request.query], normalize_embeddings=True)[0]
    scores = vectors @ query
    order = np.argsort(scores)[::-1]
    # Satu kata biasanya nomor/nama spesifik; jangan mengembalikan kecocokan
    # semantik yang tidak benar-benar memuat kata tersebut.
    terms = request.query.lower().split()
    exact_only = len(terms) == 1 and len(terms[0]) >= 3
    results = []
    for i in order:
        if scores[i] <= 0.35: continue
        if exact_only and terms[0] not in documents[i].get("content", ""): continue
        results.append({k: v for k, v in documents[i].items() if k != "content"} | {"score": round(float(scores[i]), 4)})
        if len(results) >= request.limit: break
    return {"results": results}
