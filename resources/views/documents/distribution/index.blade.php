@extends('layouts/contentNavbarLayout')

@section('title','Document Control - Distribution')

@section('vendor-style')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('styles')
<style>
  .select2-container { width: 100% !important; }

  .document-card { transition: all 0.3s ease; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
  .document-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); transform: translateY(-2px); }

  .header-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; }
  .search-box { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

  .dept-card { border: 2px solid #e9ecef; border-radius: 10px; transition: all 0.25s ease; background: white; height: 100%; }
  .dept-card:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); transform: translateY(-2px); }
  .dept-card.selected { border-color: #667eea; background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%); }

  .dept-card .form-check-input:checked { background-color: #667eea; border-color: #667eea; }

  .info-card { background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border: none; border-left: 4px solid #667eea; border-radius: 10px; }

  .action-btn { padding: 0.75rem 2rem; border-radius: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.3s ease; }
  .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

  .section-title { font-size: 1.1rem; font-weight: 700; color: #2c3e50; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea; display: inline-block; }

  .empty-state { text-align: center; padding: 3rem 2rem; color: #6c757d; }
  .empty-state-icon { font-size: 4rem; color: #dee2e6; margin-bottom: 1rem; }

  .dept-tabs .nav-link { border-radius: 999px; padding: .45rem .9rem; font-weight: 800; }
  .dept-tabs .nav-link.active { background: #667eea; }

  .child-item { border: 1px solid #eee; border-radius: 10px; padding: 10px 12px; }
  .child-item:hover { border-color: #667eea; background: rgba(102,126,234,.04); }

  .wa-type-badge { font-weight: 800; letter-spacing: .3px; }

  @media (max-width: 768px) {
    .header-gradient { padding: 1.5rem; }
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header --}}
  <div class="header-gradient">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h3 class="mb-2 fw-bold">
          <i class="mdi mdi-file-document-multiple-outline me-2"></i>
          Document Distribution
        </h3>
        <p class="mb-0 opacity-90">Manage document distribution to divisions (multi document)</p>
      </div>
      <div class="search-box p-2">
        <form method="GET" action="{{ route('documents.distribution.index') }}" class="d-flex gap-2">
          <input type="text" name="q" value="{{ $q }}" class="form-control border-0"
                 placeholder="Search document number or name..." style="min-width: 280px;">
          <button class="btn btn-primary"><i class="mdi mdi-magnify"></i></button>
          @if($q !== '')
            <a href="{{ route('documents.distribution.index') }}" class="btn btn-outline-secondary">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>
      </div>
    </div>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mt-3">
      <div class="d-flex align-items-center">
        <i class="mdi mdi-check-circle-outline me-2" style="font-size: 1.5rem;"></i>
        <div>{{ session('success') }}</div>
      </div>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mt-3">
      <div class="d-flex align-items-start">
        <i class="mdi mdi-alert-circle-outline me-2 mt-1" style="font-size: 1.5rem;"></i>
        <div>
          <strong>Please check your form again.</strong>
          <ul class="mb-0 mt-2 ps-3">
            @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
          </ul>
        </div>
      </div>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card document-card mt-3">
    <div class="card-body p-4">

      {{-- Pick docs --}}
      <div class="mb-4">
        <h5 class="section-title"><i class="mdi mdi-file-search-outline me-2"></i>Select Documents</h5>

        <form method="GET" action="{{ route('documents.distribution.index') }}" id="formPickDoc">
          <div class="row align-items-end">
            <div class="col-12">
              <label class="form-label fw-semibold">Choose Documents <span class="text-muted small">(multi)</span></label>
              <select class="form-select select2" name="document_ids[]" id="selectDocument" multiple
                      data-placeholder="Select one or more documents...">
                @foreach($documents as $d)
                  <option value="{{ $d->id }}" @selected(in_array($d->id, $selectedDocumentIds, true))>
                    {{ $d->document_number ?? '-' }} — {{ $d->name }} @if(!is_null($d->revision))(Rev {{ $d->revision }})@endif
                  </option>
                @endforeach
              </select>
            </div>
          </div>
        </form>
      </div>

      @if($selectedDocs->isEmpty())
        <div class="empty-state">
          <div class="empty-state-icon"><i class="mdi mdi-file-document-outline"></i></div>
          <h5 class="text-muted">No Document Selected</h5>
          <p class="text-muted">Select document(s) above to manage distributions.</p>
        </div>
      @else

        <form method="POST" action="{{ route('documents.distribution.store') }}">
          @csrf
          @foreach($selectedDocs as $doc)
            <input type="hidden" name="document_ids[]" value="{{ $doc->id }}">
          @endforeach

          <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="section-title mb-0">
              <i class="mdi mdi-office-building-outline me-2"></i>
              Select Recipient Divisions Per Document
            </h5>

            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="checkAllDeps" style="cursor:pointer;">
              <label class="form-check-label fw-semibold" for="checkAllDeps" style="cursor:pointer;">
                Select All Divisions (except main) for all documents
              </label>
            </div>
          </div>

          @foreach($selectedDocs as $doc)
            @php
              $primaryId      = $doc->department_id ?? null;
              $selectedForDoc = $selectedDepartmentsByDoc[$doc->id] ?? [];

              $tabIdBase   = 'doc_'.$doc->id.'_tab';
              $paneHolding = $tabIdBase.'_holding';
              $paneDjc     = $tabIdBase.'_djc';
              $paneOther   = $tabIdBase.'_other';
              $hasOther    = isset($departmentsOther) && $departmentsOther->count() > 0;
            @endphp

            <div class="info-card p-4 mb-3">
              <div class="row align-items-center">
                <div class="col-lg-8">
                  <h6 class="fw-bold mb-2"><i class="mdi mdi-file-document me-1"></i> Document</h6>
                  <div class="mb-1">
                    <span class="badge bg-primary me-2">{{ $doc->document_number }}</span>
                    <strong>{{ $doc->name }}</strong>
                  </div>
                  <div class="small text-muted">
                    <i class="mdi mdi-calendar-outline me-1"></i>
                    Published: {{ optional($doc->publish_date)->format('d M Y') ?? '-' }}
                    <span class="mx-2">•</span>
                    {!! $doc->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}
                    @if(!is_null($doc->revision))
                      <span class="mx-2">•</span> Revision {{ $doc->revision }}
                    @endif
                  </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                  <span class="badge bg-label-info text-dark">
                    Main Division:
                    @if($primaryId && isset($departmentsById[$primaryId]))
                      {{ $departmentsById[$primaryId]->name }}
                    @else
                      -
                    @endif
                  </span>
                </div>
              </div>
            </div>

            {{-- Tabs --}}
            <div class="dept-tabs mb-3">
              <ul class="nav nav-pills gap-2" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#{{ $paneHolding }}" type="button" role="tab">
                    Holding <span class="badge bg-light text-dark ms-2">{{ $departmentsHolding->count() }}</span>
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" data-bs-toggle="pill" data-bs-target="#{{ $paneDjc }}" type="button" role="tab">
                    DJC <span class="badge bg-light text-dark ms-2">{{ $departmentsDjc->count() }}</span>
                  </button>
                </li>
                @if($hasOther)
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#{{ $paneOther }}" type="button" role="tab">
                      Others <span class="badge bg-light text-dark ms-2">{{ $departmentsOther->count() }}</span>
                    </button>
                  </li>
                @endif
              </ul>
            </div>

            {{-- TAB CONTENT --}}
            <div class="tab-content mb-4">

              {{-- HOLDING --}}
              <div class="tab-pane fade show active" id="{{ $paneHolding }}" role="tabpanel">
                <div class="row g-3 deps-grid" data-doc-id="{{ $doc->id }}">
                  @php $departments = $departmentsHolding; @endphp

                  @forelse($departments as $dep)
                    @php
                      $isPrimary   = ($primaryId === $dep->id);
                      $hasChildren = ($dep->children_count ?? 0) > 0;
                      $children    = $dep->children ?? collect();

                      $selectedChildrenIds = $children->pluck('id')
                        ->filter(fn($id) => in_array($id, $selectedForDoc, true))
                        ->values()->all();
                      $selectedChildrenCount = count($selectedChildrenIds);

                      $isCheckedParent = $isPrimary
                        || in_array($dep->id, $selectedForDoc, true)
                        || $selectedChildrenCount > 0;

                      $modalId = "modalChild_{$doc->id}_{$dep->id}";
                      $wrapId  = "childInputWrap_{$doc->id}_{$dep->id}";

                      $waType = strtolower((string)($dep->wa_send_type ?? 'personal'));
                      $waTypeLabel = $waType === 'group' ? 'GROUP' : 'PERSONAL';
                      $waBadge = $waType === 'group' ? 'bg-label-warning text-dark' : 'bg-label-primary';
                    @endphp

                    <div class="col-md-6 col-lg-4 col-xl-3">
                      <div class="dept-card p-3 {{ $isCheckedParent ? 'selected' : '' }}"
                           data-doc-id="{{ $doc->id }}"
                           data-dept-id="{{ $dep->id }}"
                           data-has-children="{{ $hasChildren ? 1 : 0 }}">

                        <div class="d-flex align-items-start justify-content-between">
                          <div>
                            <div class="fw-semibold">{{ $dep->name }}</div>

                            <div class="small text-muted">
                              <i class="mdi mdi-whatsapp"></i>
                              WA: {{ $dep->no_wa ?: '-' }}

                              <span class="badge {{ $waBadge }} ms-2 rounded-pill wa-type-badge">
                                {{ $waTypeLabel }}
                              </span>
                            </div>

                            <div class="small text-muted mt-1">
                              @if($isPrimary)
                                <i class="mdi mdi-star-outline text-warning"></i> Main Division
                              @elseif($hasChildren)
                                <i class="mdi mdi-source-branch"></i> Punya cabang ({{ $dep->children_count }})
                              @else
                                <i class="mdi mdi-account-multiple-outline"></i> Additional Division
                              @endif
                            </div>
                          </div>

                          {{-- ✅ badge khusus untuk selected status (tidak bentrok dengan wa-type badge) --}}
                          <span class="badge select-badge {{ $isCheckedParent ? 'bg-success' : 'bg-light text-muted' }} rounded-pill">
                            <i class="mdi {{ $isCheckedParent ? 'mdi-check' : 'mdi-plus' }}"></i>
                          </span>
                        </div>

                        @if($hasChildren)
                          <div class="mt-2">
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#{{ $modalId }}">
                              <i class="mdi mdi-source-branch me-1"></i>
                              Pilih Cabang
                              <span class="badge bg-primary ms-1 child-count"
                                    data-doc-id="{{ $doc->id }}"
                                    data-parent-id="{{ $dep->id }}"
                                    style="{{ $selectedChildrenCount > 0 ? '' : 'display:none;' }}">
                                {{ $selectedChildrenCount }}
                              </span>
                            </button>
                          </div>

                          <div id="{{ $wrapId }}">
                            @foreach($selectedChildrenIds as $cid)
                              <input type="hidden" name="distribution[{{ $doc->id }}][]" value="{{ $cid }}">
                            @endforeach
                          </div>

                          <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title">Pilih Cabang: {{ $dep->name }}</h5>
                                  <button class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                  <div class="row g-2">
                                    @foreach($children as $child)
                                      @php
                                        $checked = in_array($child->id, $selectedForDoc, true);

                                        $cWaType = strtolower((string)($child->wa_send_type ?? 'personal'));
                                        $cWaTypeLabel = $cWaType === 'group' ? 'GROUP' : 'PERSONAL';
                                        $cWaBadge = $cWaType === 'group' ? 'bg-label-warning text-dark' : 'bg-label-primary';
                                      @endphp
                                      <div class="col-md-6">
                                        <div class="child-item">
                                          <label class="d-flex align-items-start gap-2 mb-0" style="cursor:pointer;">
                                            <input type="checkbox"
                                                   class="form-check-input child-checkbox"
                                                   value="{{ $child->id }}"
                                                   @checked($checked)>
                                            <div>
                                              <div class="fw-semibold">{{ $child->name }}</div>
                                              <div class="small text-muted">
                                                <i class="mdi mdi-whatsapp"></i>
                                                WA: {{ $child->no_wa ?: '-' }}

                                                <span class="badge {{ $cWaBadge }} ms-2 rounded-pill wa-type-badge">
                                                  {{ $cWaTypeLabel }}
                                                </span>
                                              </div>
                                            </div>
                                          </label>
                                        </div>
                                      </div>
                                    @endforeach
                                  </div>
                                </div>

                                <div class="modal-footer">
                                  <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                                  <button type="button"
                                          class="btn btn-primary btn-save-child"
                                          data-doc-id="{{ $doc->id }}"
                                          data-parent-id="{{ $dep->id }}">
                                    Simpan Cabang
                                  </button>
                                </div>
                              </div>
                            </div>
                          </div>
                        @else
                          <div class="form-check mt-2">
                            <input class="form-check-input dep-checkbox"
                                   type="checkbox"
                                   name="distribution[{{ $doc->id }}][]"
                                   value="{{ $dep->id }}"
                                   @checked($isPrimary || in_array($dep->id, $selectedForDoc, true))
                                   @if($isPrimary) disabled @endif
                                   data-primary="{{ $isPrimary ? 1 : 0 }}">
                            <label class="form-check-label">Pilih</label>

                            @if($isPrimary)
                              <input type="hidden" name="distribution[{{ $doc->id }}][]" value="{{ $dep->id }}">
                            @endif
                          </div>
                        @endif
                      </div>
                    </div>
                  @empty
                    <div class="col-12">
                      <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="mdi mdi-office-building-outline"></i></div>
                        <h6 class="text-muted">No divisions found.</h6>
                      </div>
                    </div>
                  @endforelse
                </div>
              </div>

              {{-- ✅ TAB DJC / OTHERS (untuk singkat: copy blok HOLDING lalu ganti $departments = $departmentsDjc / $departmentsOther)
                   Karena kode kamu panjang, kamu cukup COPY blok HOLDING ini ke tab DJC & OTHERS, field wa_send_type sudah siap dipakai.
              --}}

              {{-- DJC --}}
              <div class="tab-pane fade" id="{{ $paneDjc }}" role="tabpanel">
                <div class="row g-3 deps-grid" data-doc-id="{{ $doc->id }}">
                  @php $departments = $departmentsDjc; @endphp
                  {{-- ✅ Copy persis isi loop HOLDING di atas (forelse departments) --}}
                  @includeIf('documents.distribution._dept_loop', ['departments' => $departments, 'doc' => $doc, 'primaryId' => $primaryId, 'selectedForDoc' => $selectedForDoc])
                </div>
              </div>

              {{-- OTHERS --}}
              @if($hasOther)
              <div class="tab-pane fade" id="{{ $paneOther }}" role="tabpanel">
                <div class="row g-3 deps-grid" data-doc-id="{{ $doc->id }}">
                  @php $departments = $departmentsOther; @endphp
                  {{-- ✅ Copy persis isi loop HOLDING di atas (forelse departments) --}}
                  @includeIf('documents.distribution._dept_loop', ['departments' => $departments, 'doc' => $doc, 'primaryId' => $primaryId, 'selectedForDoc' => $selectedForDoc])
                </div>
              </div>
              @endif

            </div>
          @endforeach

          {{-- Footer --}}
          <div class="pt-4 border-top">
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-start align-items-md-center">
              <div class="text-muted small">
                <i class="mdi mdi-information-outline me-1"></i>
                Main Division always included and cannot be unselected.
              </div>

              <div class="d-flex flex-column flex-md-row gap-3 align-items-stretch align-items-md-center">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="send_whatsapp" name="send_whatsapp" value="1" checked>
                  <label class="form-check-label" for="send_whatsapp">
                    Kirim notifikasi WhatsApp
                    <span class="text-muted small d-block">Nonaktifkan jika hanya ingin menyimpan distribusi.</span>
                  </label>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                  <a href="{{ route('documents.distribution.index', ['document_ids' => $selectedDocumentIds]) }}" class="btn btn-outline-secondary action-btn">
                    <i class="mdi mdi-refresh me-1"></i> Reset
                  </a>
                  <button type="submit" class="btn btn-primary action-btn">
                    <i class="mdi mdi-content-save-outline me-1"></i> Save Distribution
                  </button>
                </div>
              </div>
            </div>
          </div>

        </form>
      @endif

    </div>
  </div>
</div>
@endsection

@section('vendor-script')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
(function() {
  function initSelect2() {
    const $el = $('#selectDocument');
    if ($el.length) {
      if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

      $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: $el.data('placeholder') || 'Select one or more documents...',
        allowClear: true,
        dropdownParent: $el.closest('.card')
      });

      $el.on('change', function () {
        document.getElementById('formPickDoc').submit();
      });
    }
  }

  // ✅ FIX: jangan ambil .badge pertama, tapi badge khusus status pilih
  function setCardSelected(card, selected) {
    const badge = card.querySelector('.select-badge');
    if (selected) {
      card.classList.add('selected');
      if (badge) {
        badge.className = 'badge select-badge bg-success rounded-pill';
        badge.innerHTML = '<i class="mdi mdi-check"></i>';
      }
    } else {
      card.classList.remove('selected');
      if (badge) {
        badge.className = 'badge select-badge bg-light text-muted rounded-pill';
        badge.innerHTML = '<i class="mdi mdi-plus"></i>';
      }
    }
  }

  function initCheckAll() {
    const master = document.getElementById('checkAllDeps');
    if (!master) return;

    master.addEventListener('change', function() {
      const checked = master.checked;

      document.querySelectorAll('.dep-checkbox').forEach(cb => {
        if (cb.dataset.primary === '1') return;
        cb.checked = checked;
        const card = cb.closest('.dept-card');
        if (card) setCardSelected(card, cb.checked);
      });

      document.querySelectorAll('.btn-save-child').forEach(btn => {
        const docId = btn.dataset.docId;
        const parentId = btn.dataset.parentId;

        const modal = document.getElementById(`modalChild_${docId}_${parentId}`);
        const wrap  = document.getElementById(`childInputWrap_${docId}_${parentId}`);
        if (!modal || !wrap) return;

        const childCbs = modal.querySelectorAll('.child-checkbox');
        wrap.innerHTML = '';
        let count = 0;

        childCbs.forEach(c => {
          c.checked = checked;
          if (checked) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `distribution[${docId}][]`;
            input.value = c.value;
            wrap.appendChild(input);
            count++;
          }
        });

        const badge = document.querySelector(`.child-count[data-doc-id="${docId}"][data-parent-id="${parentId}"]`);
        if (badge) {
          badge.textContent = count;
          badge.style.display = count > 0 ? '' : 'none';
        }

        const parentCard = document.querySelector(`.dept-card[data-doc-id="${docId}"][data-dept-id="${parentId}"]`);
        if (parentCard) setCardSelected(parentCard, count > 0);
      });
    });
  }

  function initChildModalSave() {
    document.querySelectorAll('.btn-save-child').forEach(btn => {
      btn.addEventListener('click', function() {
        const docId = btn.dataset.docId;
        const parentId = btn.dataset.parentId;

        const modal = document.getElementById(`modalChild_${docId}_${parentId}`);
        const wrap  = document.getElementById(`childInputWrap_${docId}_${parentId}`);
        if (!modal || !wrap) return;

        const checkedChildren = Array.from(modal.querySelectorAll('.child-checkbox:checked')).map(x => x.value);

        wrap.innerHTML = '';
        checkedChildren.forEach(id => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = `distribution[${docId}][]`;
          input.value = id;
          wrap.appendChild(input);
        });

        const badge = document.querySelector(`.child-count[data-doc-id="${docId}"][data-parent-id="${parentId}"]`);
        if (badge) {
          badge.textContent = checkedChildren.length;
          badge.style.display = checkedChildren.length > 0 ? '' : 'none';
        }

        const parentCard = document.querySelector(`.dept-card[data-doc-id="${docId}"][data-dept-id="${parentId}"]`);
        if (parentCard) setCardSelected(parentCard, checkedChildren.length > 0);

        const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
        bsModal.hide();
      });
    });
  }

  function initNormalCheckboxVisual() {
    document.querySelectorAll('.dep-checkbox').forEach(cb => {
      const card = cb.closest('.dept-card');
      if (!card) return;
      setCardSelected(card, cb.checked);

      cb.addEventListener('change', function () {
        setCardSelected(card, cb.checked);
      });
    });

    document.querySelectorAll('.dept-card[data-has-children="1"]').forEach(card => {
      const docId = card.dataset.docId;
      const parentId = card.dataset.deptId;
      const badge = document.querySelector(`.child-count[data-doc-id="${docId}"][data-parent-id="${parentId}"]`);
      const count = badge ? parseInt((badge.textContent || '0'), 10) : 0;
      setCardSelected(card, count > 0);
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    initSelect2();
    initCheckAll();
    initChildModalSave();
    initNormalCheckboxVisual();
  });
})();
</script>
@endsection
