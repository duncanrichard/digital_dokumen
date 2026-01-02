@extends('layouts/contentNavbarLayout')

@section('title', 'Master Data - Divisi')

@section('content')
@php
  $me       = auth()->user();
  $role     = optional($me)->role;
  $roleName = $role->name ?? null;

  $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

  $canCreate = $isSuperadmin || ($role && $role->hasPermissionTo('master.departments.create'));
  $canUpdate = $isSuperadmin || ($role && $role->hasPermissionTo('master.departments.update'));
  $canDelete = $isSuperadmin || ($role && $role->hasPermissionTo('master.departments.delete'));

  $officeType = $officeType ?? request('office_type', 'holding');
  $q = $q ?? request('q');

  $submenuHoldingUrl = route('master.departments.index', array_merge(request()->except('page'), ['office_type' => 'holding']));
  $submenuDjcUrl     = route('master.departments.index', array_merge(request()->except('page'), ['office_type' => 'djc']));

  // tampilkan nama detail: "Parent + spasi + Child"
  $detailLabel = function(string $parentName = null, string $childName = null) {
    $parentName = trim((string) $parentName);
    $childName  = trim((string) $childName);

    if ($childName === '') return $parentName;
    if ($parentName !== '' && stripos($childName, $parentName) === 0) return $childName;

    return $parentName !== '' ? ($parentName . ' ' . $childName) : $childName;
  };

  // ✅ tambah 1 kolom "Jenis WA"
  // kolom: #, Office, Code, Name, Description, No. WA, Jenis WA, Status, Detail, Actions
  $colspan = ($canUpdate || $canDelete) ? 10 : 9; // default lama
  // ✅ sekarang ada 1 kolom tambahan => +1
  $colspan = $colspan + 1;
@endphp

<div class="row gy-4">
  <div class="col-12">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Validation failed.</strong> Please check the form below.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card">
      <div class="card-header">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
          <div>
            <h5 class="card-title mb-1">Divisi</h5>

            <div class="mt-3">
              <ul class="nav nav-pills">
                <li class="nav-item">
                  <a class="nav-link {{ $officeType === 'holding' ? 'active' : '' }}" href="{{ $submenuHoldingUrl }}">
                    Holding
                  </a>
                </li>
                <li class="nav-item ms-2">
                  <a class="nav-link {{ $officeType === 'djc' ? 'active' : '' }}" href="{{ $submenuDjcUrl }}">
                    DJC
                  </a>
                </li>
              </ul>
            </div>
          </div>

          <div class="d-flex gap-2 align-items-center">
            <form method="get" class="d-flex" role="search">
              <input type="hidden" name="office_type" value="{{ $officeType }}">
              <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search code/name..." />
              <button class="btn btn-outline-primary ms-2" type="submit">
                <i class="mdi mdi-magnify"></i>
              </button>
            </form>

            @if($canCreate)
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="mdi mdi-plus"></i> Add Divisi
              </button>
            @endif
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:60px;">#</th>
              <th style="width:90px;">Office</th>
              <th style="width:120px;">Code</th>
              <th>Name</th>
              <th>Description</th>
              <th style="width:160px;">No. WA</th>
              <th style="width:110px;">Jenis WA</th> {{-- ✅ new --}}
              <th style="width:110px;">Status</th>
              <th style="width:260px;">Detail</th>
              @if($canUpdate || $canDelete)
                <th class="text-end" style="width:90px;">Actions</th>
              @endif
            </tr>
          </thead>

          <tbody>
            @forelse($items as $i => $row)
              @php
                $childCount = (int) ($row->children_count ?? 0);
                $waType = strtolower((string)($row->wa_send_type ?? 'personal'));
              @endphp

              {{-- ===== PARENT ROW ===== --}}
              <tr>
                <td>{{ $items->firstItem() + $i }}</td>

                <td>
                  @if(($row->office_type ?? '') === 'djc')
                    <span class="badge bg-label-warning rounded-pill text-uppercase">DJC</span>
                  @else
                    <span class="badge bg-label-info rounded-pill text-uppercase">Holding</span>
                  @endif
                </td>

                <td class="fw-medium">{{ $row->code }}</td>

                <td>
                  <div class="fw-semibold">{{ $row->name }}</div>
                  <div class="text-muted small">
                    Divisi utama
                    @if($childCount > 0)
                      • {{ $childCount }} detail
                    @endif
                  </div>
                </td>

                <td class="text-muted">{{ \Illuminate\Support\Str::limit($row->description, 80) }}</td>

                <td>
                  {!! $row->no_wa ? '<span class="fw-medium">'.$row->no_wa.'</span>' : '<span class="text-muted">-</span>' !!}
                </td>

                {{-- ✅ Jenis WA --}}
                <td>
                  @if($waType === 'group')
                    <span class="badge bg-label-primary rounded-pill">Group</span>
                  @else
                    <span class="badge bg-label-secondary rounded-pill">Personal</span>
                  @endif
                </td>

                <td>
                  @if($row->is_active)
                    <span class="badge bg-label-success rounded-pill">Active</span>
                  @else
                    <span class="badge bg-label-secondary rounded-pill">Inactive</span>
                  @endif
                </td>

                {{-- DETAIL INLINE (collapse) --}}
                <td>
                  <div class="d-flex flex-wrap gap-2">
                    <button type="button"
                            class="btn btn-sm btn-outline-info"
                            data-bs-toggle="collapse"
                            data-bs-target="#detailRow-{{ $row->id }}"
                            aria-expanded="false"
                            aria-controls="detailRow-{{ $row->id }}">
                      <i class="mdi mdi-eye-outline me-1"></i>
                      Lihat Detail
                      @if($childCount > 0)
                        <span class="badge bg-info text-white ms-1">{{ $childCount }}</span>
                      @endif
                    </button>

                    @if($canCreate)
                      <button type="button"
                              class="btn btn-sm btn-primary"
                              data-bs-toggle="modal"
                              data-bs-target="#createDetailModal-{{ $row->id }}">
                        <i class="mdi mdi-plus me-1"></i>
                        Add Detail
                      </button>
                    @endif
                  </div>
                </td>

                @if($canUpdate || $canDelete)
                  <td class="text-end">
                    <button class="btn btn-sm btn-icon btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal-{{ $row->id }}"
                            title="Edit">
                      <i class="mdi mdi-pencil-outline"></i>
                    </button>
                  </td>
                @endif
              </tr>

              {{-- ===== DETAIL ROW (collapse content) ===== --}}
              <tr class="collapse" id="detailRow-{{ $row->id }}">
                <td colspan="{{ $colspan }}" class="bg-body-tertiary">
                  <div class="p-3">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="fw-semibold">
                        Detail dari: <span class="text-primary">{{ $row->name }}</span>
                      </div>
                    </div>

                    @if(($row->children ?? collect())->isEmpty())
                      <div class="alert alert-warning mb-0">
                        Belum ada detail untuk divisi <strong>{{ $row->name }}</strong>.
                        <div class="small text-muted mt-2">Contoh: <strong>Jangli</strong>, <strong>Supriyadi</strong></div>
                      </div>
                    @else
                      <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 bg-white">
                          <thead class="table-light">
                            <tr>
                              <th style="width:60px;">#</th>
                              <th>Name</th>
                              <th style="width:160px;">No. WA</th>
                              <th style="width:110px;">Jenis WA</th> {{-- ✅ new --}}
                              <th style="width:110px;">Status</th>
                              <th style="width:130px;" class="text-end">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($row->children as $idx => $child)
                              @php
                                $childWaType = strtolower((string)($child->wa_send_type ?? $row->wa_send_type ?? 'personal'));
                              @endphp
                              <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                  <div class="fw-semibold">{{ $detailLabel($row->name, $child->name) }}</div>
                                  <div class="text-muted small">Detail: {{ $child->name }}</div>
                                </td>
                                <td>{{ $child->no_wa ?: '-' }}</td>

                                {{-- ✅ child jenis WA --}}
                                <td>
                                  @if($childWaType === 'group')
                                    <span class="badge bg-label-primary rounded-pill">Group</span>
                                  @else
                                    <span class="badge bg-label-secondary rounded-pill">Personal</span>
                                  @endif
                                </td>

                                <td>
                                  @if($child->is_active)
                                    <span class="badge bg-label-success rounded-pill">Active</span>
                                  @else
                                    <span class="badge bg-label-secondary rounded-pill">Inactive</span>
                                  @endif
                                </td>

                                <td class="text-end">
                                  @if($canUpdate)
                                    <button type="button"
                                            class="btn btn-sm btn-icon btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editDetailModal-{{ $child->id }}"
                                            title="Edit detail">
                                      <i class="mdi mdi-pencil-outline"></i>
                                    </button>
                                  @endif

                                  @if($canDelete)
                                    <form method="POST"
                                          action="{{ route('master.departments.details.destroy', $child->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus detail ini?')">
                                      @csrf
                                      @method('DELETE')
                                      <button type="submit"
                                              class="btn btn-sm btn-icon btn-outline-danger"
                                              title="Delete detail">
                                        <i class="mdi mdi-delete-outline"></i>
                                      </button>
                                    </form>
                                  @endif
                                </td>
                              </tr>

                              {{-- ===== MODAL EDIT DETAIL ===== --}}
                              @push('modals')
                                <div class="modal fade" id="editDetailModal-{{ $child->id }}" tabindex="-1" aria-hidden="true">
                                  <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content shadow-lg rounded-3">
                                      <form method="POST" action="{{ route('master.departments.details.update', $child->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_from" value="detail_edit">
                                        <input type="hidden" name="_parent_id" value="{{ $row->id }}">

                                        <div class="modal-header border-0">
                                          <h5 class="modal-title fw-semibold">Edit Detail: {{ $detailLabel($row->name, $child->name) }}</h5>
                                          <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
                                            <i class="mdi mdi-close"></i>
                                          </button>
                                        </div>

                                        <div class="modal-body pt-0">

                                          <div class="mb-3">
                                            <label class="form-label required">Nama Detail</label>
                                            <input type="text"
                                                   name="name"
                                                   class="form-control"
                                                   required
                                                   value="{{ old('name', $child->name) }}"
                                                   placeholder="Contoh: Jangli">
                                            <div class="form-text">
                                              Tampilan otomatis menjadi: <strong>{{ $row->name }} (Nama Detail)</strong>
                                            </div>
                                          </div>

                                          {{-- ✅ Jenis WA --}}
                                          <div class="mb-3">
                                            <label class="form-label required">Jenis Divisi (WA)</label>
                                            <select name="wa_send_type" class="form-select" required>
                                              @php $v = old('wa_send_type', $child->wa_send_type ?? $row->wa_send_type ?? 'personal'); @endphp
                                              <option value="personal" {{ $v==='personal'?'selected':'' }}>Personal</option>
                                              <option value="group" {{ $v==='group'?'selected':'' }}>Group</option>
                                            </select>
                                          </div>

                                          <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" rows="3" class="form-control">{{ old('description', $child->description) }}</textarea>
                                          </div>

                                          <div class="mb-3">
                                            <label class="form-label">No. WhatsApp</label>
                                            <input type="text" name="no_wa" class="form-control"
                                                   value="{{ old('no_wa', $child->no_wa) }}"
                                                   placeholder="e.g., +6281234567890">
                                          </div>

                                          <div class="mb-1">
                                            <label class="form-label required">Status</label>
                                            <select name="is_active" class="form-select" required>
                                              <option value="1" {{ old('is_active', $child->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Active</option>
                                              <option value="0" {{ old('is_active', $child->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="modal-footer border-0 d-flex justify-content-end">
                                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                          <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                            <i class="mdi mdi-content-save-outline"></i> Save
                                          </button>
                                        </div>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                              @endpush
                            @endforeach
                          </tbody>
                        </table>
                      </div>
                    @endif

                  </div>
                </td>
              </tr>

              {{-- ===== MODAL CREATE DETAIL ===== --}}
              @if($canCreate)
                @push('modals')
                  <div class="modal fade" id="createDetailModal-{{ $row->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                      <div class="modal-content shadow-lg rounded-3">
                        <form method="POST" action="{{ route('master.departments.details.store', $row->id) }}">
                          @csrf
                          <input type="hidden" name="_from" value="detail_create">
                          <input type="hidden" name="_parent_id" value="{{ $row->id }}">

                          <div class="modal-header border-0">
                            <h5 class="modal-title fw-semibold">Tambah Detail untuk: {{ $row->name }}</h5>
                            <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
                              <i class="mdi mdi-close"></i>
                            </button>
                          </div>

                          <div class="modal-body pt-0">
                            <div class="alert alert-info">
                              Isi cukup <strong>nama detail</strong> saja.<br>
                              Contoh input: <strong>Jangli</strong><br>
                              Tampilan: <strong>{{ $row->name }} Jangli</strong>
                            </div>

                            <div class="mb-3">
                              <label class="form-label required">Nama Detail</label>
                              <input type="text" name="name" class="form-control" required placeholder="Contoh: Jangli" value="{{ old('name') }}">
                            </div>

                            {{-- ✅ Jenis WA --}}
                            <div class="mb-3">
                              <label class="form-label required">Jenis Divisi (WA)</label>
                              @php $v = old('wa_send_type', $row->wa_send_type ?? 'personal'); @endphp
                              <select name="wa_send_type" class="form-select" required>
                                <option value="personal" {{ $v==='personal'?'selected':'' }}>Personal</option>
                                <option value="group" {{ $v==='group'?'selected':'' }}>Group</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Description</label>
                              <textarea name="description" rows="3" class="form-control" placeholder="Optional...">{{ old('description') }}</textarea>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">No. WhatsApp</label>
                              <input type="text" name="no_wa" class="form-control" placeholder="e.g., +6281234567890" value="{{ old('no_wa') }}">
                            </div>

                            <div class="mb-1">
                              <label class="form-label required">Status</label>
                              <select name="is_active" class="form-select" required>
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                              </select>
                            </div>
                          </div>

                          <div class="modal-footer border-0 d-flex justify-content-end">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                              <i class="mdi mdi-content-save-outline"></i> Save Detail
                            </button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                @endpush
              @endif

              {{-- ===== MODAL EDIT PARENT ===== --}}
              @if($canUpdate || $canDelete)
                @push('modals')
                  <div class="modal fade" id="editModal-{{ $row->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                      <div class="modal-content shadow-lg rounded-3">
                        <form method="POST" action="{{ route('master.departments.update', $row->id) }}">
                          @csrf
                          @method('PUT')

                          <div class="modal-header border-0">
                            <h5 class="modal-title fw-semibold">Edit Divisi: {{ $row->name }}</h5>
                            <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
                              <i class="mdi mdi-close"></i>
                            </button>
                          </div>

                          <div class="modal-body pt-0">
                            <div class="mb-3">
                              <label class="form-label required">Jenis Office</label>
                              <select name="office_type" class="form-select" required>
                                <option value="holding" {{ ($row->office_type ?? 'holding') === 'holding' ? 'selected' : '' }}>Holding</option>
                                <option value="djc" {{ ($row->office_type ?? 'holding') === 'djc' ? 'selected' : '' }}>DJC</option>
                              </select>
                            </div>

                            {{-- ✅ Jenis WA --}}
                            <div class="mb-3">
                              <label class="form-label required">Jenis Divisi (WA)</label>
                              @php $v = old('wa_send_type', $row->wa_send_type ?? 'personal'); @endphp
                              <select name="wa_send_type" class="form-select" required>
                                <option value="personal" {{ $v==='personal'?'selected':'' }}>Personal</option>
                                <option value="group" {{ $v==='group'?'selected':'' }}>Group</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label class="form-label required">Code</label>
                              <input type="text" name="code" value="{{ $row->code }}" class="form-control" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label required">Name</label>
                              <input type="text" name="name" value="{{ $row->name }}" class="form-control" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Description</label>
                              <textarea name="description" rows="3" class="form-control">{{ $row->description }}</textarea>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">No. WhatsApp</label>
                              <input type="text" name="no_wa" value="{{ $row->no_wa }}" class="form-control" placeholder="e.g., +6281234567890">
                            </div>

                            <div class="mb-1">
                              <label class="form-label required">Status</label>
                              <select name="is_active" class="form-select" required>
                                <option value="1" {{ $row->is_active ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !$row->is_active ? 'selected' : '' }}>Inactive</option>
                              </select>
                            </div>
                          </div>

                          <div class="modal-footer border-0 d-flex justify-content-between">
                            @if($canDelete)
                              <button type="button"
                                      class="btn btn-outline-danger d-inline-flex align-items-center gap-2"
                                      onclick="if(confirm('Delete this division?')) document.getElementById('deleteForm-{{ $row->id }}').submit();">
                                <i class="mdi mdi-delete-outline"></i> Delete
                              </button>
                            @endif

                            <div class="d-flex gap-2">
                              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                              @if($canUpdate)
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                  <i class="mdi mdi-content-save-outline"></i> Save
                                </button>
                              @endif
                            </div>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                  @if($canDelete)
                    <form id="deleteForm-{{ $row->id }}" method="POST" action="{{ route('master.departments.destroy', $row->id) }}" class="d-none">
                      @csrf
                      @method('DELETE')
                      <input type="hidden" name="office_type" value="{{ $officeType }}">
                    </form>
                  @endif
                @endpush
              @endif

            @empty
              <tr>
                <td colspan="{{ $colspan }}" class="text-center text-muted py-4">
                  No data available
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($items->hasPages())
        <div class="card-footer">
          {{ $items->links() }}
        </div>
      @endif
    </div>

  </div>
</div>

{{-- ===== MODAL CREATE DIVISI UTAMA ===== --}}
@if($canCreate)
  <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow-lg rounded-3">
        <form method="POST" action="{{ route('master.departments.store') }}">
          @csrf
          <input type="hidden" name="_from" value="create">

          <div class="modal-header border-0">
            <h5 class="modal-title fw-semibold">Add Divisi</h5>
            <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
              <i class="mdi mdi-close"></i>
            </button>
          </div>

          <div class="modal-body pt-0">
            <div class="mb-3">
              <label class="form-label required">Jenis Office</label>
              <select name="office_type" class="form-select @error('office_type') is-invalid @enderror" required>
                <option value="holding" {{ old('office_type', $officeType) == 'holding' ? 'selected' : '' }}>Holding</option>
                <option value="djc" {{ old('office_type', $officeType) == 'djc' ? 'selected' : '' }}>DJC</option>
              </select>
              @error('office_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- ✅ Jenis WA --}}
            <div class="mb-3">
              <label class="form-label required">Jenis Divisi (WA)</label>
              <select name="wa_send_type" class="form-select @error('wa_send_type') is-invalid @enderror" required>
                <option value="personal" {{ old('wa_send_type','personal')=='personal'?'selected':'' }}>Personal</option>
                <option value="group" {{ old('wa_send_type')=='group'?'selected':'' }}>Group</option>
              </select>
              @error('wa_send_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label required">Code</label>
              <input type="text" name="code" value="{{ old('code') }}"
                     class="form-control @error('code') is-invalid @enderror"
                     required placeholder="e.g., HRD.K, FIN, OPS">
              @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label required">Name</label>
              <input type="text" name="name" value="{{ old('name') }}"
                     class="form-control @error('name') is-invalid @enderror"
                     required placeholder="e.g., HRD Klinik">
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" rows="3"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Short description...">{{ old('description') }}</textarea>
              @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label">No. WhatsApp</label>
              <input type="text" name="no_wa" value="{{ old('no_wa') }}"
                     class="form-control @error('no_wa') is-invalid @enderror"
                     placeholder="e.g., +6281234567890">
              @error('no_wa')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-1">
              <label class="form-label required">Status</label>
              <select name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
              </select>
              @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="modal-footer border-0 d-flex justify-content-end">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
              <i class="mdi mdi-content-save-outline"></i> Save
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
@endif

{{-- ✅ semua modal detail/edit parent dari @push('modals') ditaruh di sini (DI LUAR TABLE) --}}
@stack('modals')
@endsection

@push('styles')
<style>
  .form-label.required::before { content: '*'; color: #dc3545; margin-right: .35rem; font-weight: 600; }
  .btn-icon { width: 34px; height: 34px; display:inline-flex; align-items:center; justify-content:center; padding:0; }
</style>
@endpush

@section('page-script')
<script>
  // Auto-open Create modal on validation fail (divisi utama)
  @if($errors->any() && old('_from') === 'create')
    const createModalEl = document.getElementById('createModal');
    if (createModalEl) new bootstrap.Modal(createModalEl).show();
  @endif

  // Auto-open collapse + modal detail create/edit bila validasi gagal
  @if($errors->any() && in_array(old('_from'), ['detail_create','detail_edit'], true) && old('_parent_id'))
    const pid = @json(old('_parent_id'));
    const collapseEl = document.getElementById('detailRow-' + pid);
    if (collapseEl) new bootstrap.Collapse(collapseEl, { toggle: true });

    // buka modal create detail
    @if(old('_from') === 'detail_create')
      const m1 = document.getElementById('createDetailModal-' + pid);
      if (m1) new bootstrap.Modal(m1).show();
    @endif
  @endif
</script>
@endsection
