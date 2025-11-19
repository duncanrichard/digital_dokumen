@component('mail::message')
# Pemberitahuan Distribusi Dokumen Resmi

Kepada Yth.  
**{{ $user->name }}**  
Departemen **{{ $department->name }}**

Dengan hormat,

Bersama email ini, kami informasikan bahwa terdapat dokumen baru yang telah diterbitkan dan **ditetapkan untuk menjadi acuan bagi departemen Anda**. Dokumen tersebut perlu dipahami serta digunakan sesuai ketentuan yang berlaku di perusahaan.

---

### 📄 Informasi Dokumen
**Nomor Dokumen:** {{ $document->document_number }}  
**Judul Dokumen:** {{ $document->name }}  

@if($document->revision)
**Revisi:** {{ $document->revision }}
@endif

@if($document->publish_date)
**Tanggal Terbit:** {{ $document->publish_date }}
@endif

---

### 📎 Lampiran Dokumen  
Dokumen resmi telah **dilampirkan pada email ini**.  
Silakan mengunduh, membaca, dan menyimpan dokumen tersebut untuk kebutuhan operasional di departemen Anda.

Apabila terdapat pertanyaan, klarifikasi, atau membutuhkan versi cetak, silakan menghubungi **Unit Document Control**.

---

Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.

Hormat kami,  
**Unit Document Control**  

@endcomponent
