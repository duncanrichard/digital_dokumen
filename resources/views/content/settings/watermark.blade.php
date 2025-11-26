@extends('layouts/contentNavbarLayout')

@section('title', 'Watermark / DRM')

@section('content')
@php
  $me       = auth()->user();
  $role     = optional($me)->role;
  $roleName = $role->name ?? null;

  $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

  // View-nya sudah difilter di controller, tapi kita tetap siapkan flag
  $canUpdate = $isSuperadmin || ($role && $role->hasPermissionTo('settings.watermark.update'));
@endphp

<div class="row gy-4">
  <div class="col-12 col-lg-8">
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Update failed.</strong> Please fix the following:
        <ul class="mb-0 mt-2 ps-3">
          @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @unless($canUpdate)
      <div class="alert alert-warning mb-0">
        Anda tidak memiliki izin untuk mengubah pengaturan watermark. Form hanya dapat dibaca (read-only).
      </div>
    @endunless

    <div class="card shadow-sm mt-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Watermark / DRM</h4>
        @if($canUpdate)
          <span class="badge bg-label-success">Editable</span>
        @else
          <span class="badge bg-label-secondary">Read-only</span>
        @endif
      </div>

      <div class="card-body">
        <form method="POST"
              action="{{ route('settings.watermark.update') }}"
              enctype="multipart/form-data">
          @csrf

          {{-- Biar gampang, semua control kita wrap di fieldset --}}
          <fieldset {{ $canUpdate ? '' : 'disabled' }}>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1"
                     {{ old('enabled', $setting->enabled ?? false) ? 'checked' : '' }}>
              <label class="form-check-label" for="enabled">Enable watermark</label>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Mode</label>
                @php $mode = old('mode', $setting->mode ?? 'text'); @endphp
                <select name="mode" class="form-select" id="mode">
                  <option value="text"  {{ $mode === 'text' ? 'selected' : '' }}>Text</option>
                  <option value="image" {{ $mode === 'image' ? 'selected' : '' }}>Image</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Font Size</label>
                <input type="number"
                       class="form-control @error('font_size') is-invalid @enderror"
                       name="font_size"
                       value="{{ old('font_size', $setting->font_size ?? 28) }}"
                       min="8" max="120">
                @error('font_size')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label class="form-label">Rotation (°)</label>
                <input type="number"
                       class="form-control @error('rotation') is-invalid @enderror"
                       name="rotation"
                       value="{{ old('rotation', $setting->rotation ?? 45) }}"
                       min="-180" max="180">
                @error('rotation')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label class="form-label">Opacity (%)</label>
                <input type="number"
                       class="form-control @error('opacity') is-invalid @enderror"
                       name="opacity"
                       value="{{ old('opacity', $setting->opacity ?? 30) }}"
                       min="0" max="100">
                @error('opacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label class="form-label">Position</label>
                @php $pos = old('position', $setting->position ?? 'center'); @endphp
                <select name="position"
                        class="form-select @error('position') is-invalid @enderror">
                  <option value="center"        {{ $pos==='center' ? 'selected' : '' }}>Center</option>
                  <option value="top-left"      {{ $pos==='top-left' ? 'selected' : '' }}>Top Left</option>
                  <option value="top-right"     {{ $pos==='top-right' ? 'selected' : '' }}>Top Right</option>
                  <option value="bottom-left"   {{ $pos==='bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                  <option value="bottom-right"  {{ $pos==='bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                </select>
                @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label class="form-label">Color (HEX)</label>
                <input type="text"
                       class="form-control @error('color_hex') is-invalid @enderror"
                       name="color_hex"
                       value="{{ old('color_hex', $setting->color_hex ?? '#A0A0A0') }}"
                       placeholder="#A0A0A0">
                @error('color_hex')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="repeat" name="repeat" value="1"
                         {{ old('repeat', $setting->repeat ?? true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="repeat">Repeat / diagonal pattern</label>
                </div>
              </div>

              {{-- TEXT MODE --}}
              <div class="col-12 mode-text">
                <label class="form-label">Text Template</label>
                <input type="text"
                       class="form-control @error('text_template') is-invalid @enderror"
                       name="text_template"
                       value="{{ old('text_template', $setting->text_template ?? 'CONFIDENTIAL — {user.name} — {date}') }}"
                       placeholder="e.g. CONFIDENTIAL — {user.name} — {date}">
                <small class="text-muted">Variables: {user.name}, {user.username}, {date}, {datetime}</small>
                @error('text_template')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              {{-- IMAGE MODE --}}
              <div class="col-12 mode-image mt-2" style="display: none;">
                <label class="form-label">Watermark Image (PNG/JPG)</label>
                <input type="file"
                       class="form-control @error('image') is-invalid @enderror"
                       name="image"
                       accept=".png,.jpg,.jpeg">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror

                @if(!empty($setting->image_path))
                  <small class="text-muted d-block mt-1">
                    Current: <a href="{{ asset($setting->image_path) }}" target="_blank">view</a>
                  </small>
                @endif
              </div>

              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="show_on_download" name="show_on_download" value="1"
                         {{ old('show_on_download', $setting->show_on_download ?? true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="show_on_download">
                    Apply on download route as well
                  </label>
                </div>
              </div>
            </div>
          </fieldset>

          @if($canUpdate)
            <div class="mt-4 d-flex justify-content-end">
              <button type="submit" class="btn btn-primary">
                <i class="mdi mdi-content-save-outline me-1"></i> Save Changes
              </button>
            </div>
          @endif
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
  (function(){
    function toggleModeFields() {
      const mode = document.getElementById('mode').value;
      const textEl  = document.querySelector('.mode-text');
      const imageEl = document.querySelector('.mode-image');

      if (textEl)  textEl.style.display  = (mode === 'text')  ? '' : 'none';
      if (imageEl) imageEl.style.display = (mode === 'image') ? '' : 'none';
    }

    const modeSelect = document.getElementById('mode');
    if (modeSelect) {
      modeSelect.addEventListener('change', toggleModeFields);
      toggleModeFields();
    }
  })();
</script>
@endsection
