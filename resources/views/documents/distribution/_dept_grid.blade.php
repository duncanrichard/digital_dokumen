@forelse($departments as $dep)
  @php
    $isPrimary = ((string)$primaryId === (string)$dep->id);

    $children = $dep->children ?? collect();
    $hasChildren = $children->isNotEmpty() || (int)($dep->children_count ?? 0) > 0;

    // pastikan selectedForDoc berisi string
    $selectedForDocStr = array_map('strval', $selectedForDoc ?? []);

    // anak yang terpilih untuk doc ini
    $selectedChildrenIds = $children
      ->pluck('id')
      ->map(fn($id) => (string)$id)
      ->filter(fn($id) => in_array($id, $selectedForDocStr, true))
      ->values()
      ->all();

    $selectedChildrenCount = count($selectedChildrenIds);

    // parent dianggap "selected" jika:
    // - dia main division
    // - parent dicek langsung (khusus parent tanpa anak, atau kamu memang izinkan parent ikut)
    // - ada anak yang dipilih
    $isCheckedParent =
        $isPrimary
        || in_array((string)$dep->id, $selectedForDocStr, true)
        || $selectedChildrenCount > 0;

    $modalId = "modalChild_{$doc->id}_{$dep->id}";
    $wrapId  = "childInputWrap_{$doc->id}_{$dep->id}";
  @endphp

  <div class="col-md-6 col-lg-4 col-xl-3">
    <div class="dept-card p-3 {{ $isCheckedParent ? 'selected' : '' }}"
         data-doc-id="{{ $doc->id }}"
         data-dept-id="{{ $dep->id }}"
         data-has-children="{{ $hasChildren ? '1' : '0' }}">

      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="fw-semibold">{{ $dep->name }}</div>
          <div class="small text-muted">
            @if($isPrimary)
              <i class="mdi mdi-star-outline text-warning"></i> Main Division
            @elseif($hasChildren)
              <i class="mdi mdi-source-branch"></i> Punya cabang ({{ (int)($dep->children_count ?? $children->count()) }})
            @else
              <i class="mdi mdi-account-multiple-outline"></i> Additional Division
            @endif
          </div>
        </div>

        <span class="badge {{ $isCheckedParent ? 'bg-success' : 'bg-light text-muted' }} rounded-pill">
          <i class="mdi {{ $isCheckedParent ? 'mdi-check' : 'mdi-plus' }}"></i>
        </span>
      </div>

      {{-- ===================== PARENT WITH CHILDREN ===================== --}}
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

        {{-- hidden inputs untuk anak yang dipilih --}}
        <div id="{{ $wrapId }}">
          @foreach($selectedChildrenIds as $cid)
            <input type="hidden" name="distribution[{{ $doc->id }}][]" value="{{ $cid }}">
          @endforeach
        </div>

        {{-- Modal pilih cabang --}}
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title">Pilih Cabang: {{ $dep->name }}</h5>

                {{-- WAJIB type="button" biar tidak submit form --}}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="modal-body">
                @if($children->isEmpty())
                  <div class="alert alert-warning mb-0">
                    Cabang belum tersedia untuk divisi ini.
                  </div>
                @else
                  <div class="row g-2">
                    @foreach($children as $child)
                      @php
                        $checked = in_array((string)$child->id, $selectedForDocStr, true);
                      @endphp

                      <div class="col-md-6">
                        <div class="child-item">
                          <label class="d-flex align-items-start gap-2 mb-0" style="cursor:pointer;">
                            <input type="checkbox"
                                   class="form-check-input child-checkbox"
                                   data-doc-id="{{ $doc->id }}"
                                   data-parent-id="{{ $dep->id }}"
                                   value="{{ $child->id }}"
                                   @checked($checked)>

                            <div>
                              <div class="fw-semibold">{{ $child->name }}</div>
                              <div class="small text-muted">WA: {{ $child->no_wa ?: '-' }}</div>
                            </div>
                          </label>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @endif
              </div>

              <div class="modal-footer">
                {{-- WAJIB type="button" biar tidak submit form --}}
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                  Tutup
                </button>

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

      {{-- ===================== NORMAL DIVISION (NO CHILD) ===================== --}}
      @else
        <div class="form-check mt-2">
          <input class="form-check-input dep-checkbox"
                 type="checkbox"
                 name="distribution[{{ $doc->id }}][]"
                 id="dep_{{ $doc->id }}_{{ $dep->id }}"
                 value="{{ $dep->id }}"
                 @checked($isPrimary || in_array((string)$dep->id, $selectedForDocStr, true))
                 @if($isPrimary) disabled @endif
                 data-primary="{{ $isPrimary ? '1' : '0' }}">

          <label class="form-check-label" for="dep_{{ $doc->id }}_{{ $dep->id }}">Pilih</label>

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
