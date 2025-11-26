@extends('layouts/contentNavbarLayout')

@section('title', 'Permission Management')

@section('content')
<div class="row">
  <div class="col-12">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
          <i class="mdi mdi-check-circle me-2 fs-5"></i>
          <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-start">
          <i class="mdi mdi-alert-circle me-2 fs-5 mt-1"></i>
          <div class="flex-grow-1">
            <strong>Update Failed</strong>
            <ul class="mb-0 mt-2 ps-3">
              @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card shadow-sm border-0">
      <!-- Card Header -->
      <div class="card-header bg-gradient-primary text-white py-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title text-white mb-1">
              <i class="mdi mdi-shield-lock-outline me-2"></i>
              Permission Management
            </h4>
            <p class="card-subtitle text-white-50 mb-0 small">
              Configure role-based access permissions
            </p>
          </div>
        </div>
      </div>

      <div class="card-body p-4">

        <!-- Role Selection Section -->
        <div class="bg-light rounded-3 p-4 mb-4">
          <form method="GET" action="{{ route('access.permissions.index') }}" class="row g-3 align-items-end">
            <div class="col-md-8 col-lg-6">
              <label class="form-label fw-semibold mb-2">
                <i class="mdi mdi-account-key text-primary me-1"></i>
                Select Role
              </label>
              <select name="role_id" class="form-select form-select-lg shadow-sm" onchange="this.form.submit()">
                @foreach($roles as $role)
                  <option value="{{ $role->id }}" {{ $selectedRoleId == $role->id ? 'selected' : '' }}>
                    {{ $role->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4 col-lg-2">
              <button class="btn btn-outline-primary btn-lg w-100 shadow-sm" type="submit">
                <i class="mdi mdi-refresh me-1"></i>
                Refresh
              </button>
            </div>
            @if($selectedRole)
              <div class="col-12">
                <div class="alert alert-info mb-0 d-flex align-items-center">
                  <i class="mdi mdi-information-outline fs-5 me-2"></i>
                  <span>Managing permissions for <strong>{{ $selectedRole->name }}</strong> role</span>
                </div>
              </div>
            @endif
          </form>
        </div>

        @if($selectedRole)
          <form method="POST" action="{{ route('access.permissions.update') }}">
            @csrf
            <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">

            <!-- Bulk Actions -->
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" id="checkAll" style="cursor: pointer;">
                <label class="form-check-label fw-semibold" for="checkAll" style="cursor: pointer;">
                  Select All Permissions
                </label>
              </div>
              <span class="badge bg-secondary" id="selectedCount">0 selected</span>
            </div>

            <!-- Permissions Table -->
            <div class="accordion" id="permissionsAccordion">
              @foreach($groupedPermissions as $groupName => $perms)
                @php
                  $groupSlug = \Illuminate\Support\Str::slug($groupName);
                  $groupId = 'group-' . $groupSlug;
                  $checkedCount = collect($perms)->filter(fn($p) => in_array($p->name, $assignedPermissions, true))->count();
                  $totalCount = count($perms);
                @endphp

                <div class="accordion-item border rounded-3 mb-3 shadow-sm">
                  <h2 class="accordion-header" id="heading-{{ $groupSlug }}">
                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} fw-semibold" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#{{ $groupId }}" 
                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                            aria-controls="{{ $groupId }}">
                      <div class="d-flex align-items-center justify-content-between w-100 pe-3">
                        <div class="d-flex align-items-center">
                          <i class="mdi mdi-folder-outline text-primary me-2 fs-5"></i>
                          <span>{{ $groupName }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                          <span class="badge bg-primary rounded-pill">
                            {{ $checkedCount }}/{{ $totalCount }}
                          </span>
                          <div class="form-check form-switch mb-0" onclick="event.stopPropagation();">
                            <input type="checkbox" 
                                   class="form-check-input check-group" 
                                   data-group="{{ $groupSlug }}"
                                   {{ $checkedCount == $totalCount && $totalCount > 0 ? 'checked' : '' }}
                                   style="cursor: pointer;">
                          </div>
                        </div>
                      </div>
                    </button>
                  </h2>
                  <div id="{{ $groupId }}" 
                       class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" 
                       aria-labelledby="heading-{{ $groupSlug }}"
                       data-bs-parent="#permissionsAccordion">
                    <div class="accordion-body p-0">
                      <div class="table-responsive">
                        <table class="table table-hover mb-0">
                          <tbody>
                            @foreach($perms as $perm)
                              <tr class="align-middle">
                                <td style="width: 50px;" class="text-center">
                                  <div class="form-check mb-0">
                                    <input type="checkbox"
                                           class="form-check-input perm-checkbox perm-group-{{ $groupSlug }}"
                                           id="perm-{{ $perm->id }}"
                                           name="permissions[]"
                                           value="{{ $perm->name }}"
                                           {{ in_array($perm->name, $assignedPermissions, true) ? 'checked' : '' }}
                                           style="cursor: pointer;">
                                  </div>
                                </td>
                                <td>
                                  <label class="form-check-label d-flex align-items-center mb-0" 
                                         for="perm-{{ $perm->id }}"
                                         style="cursor: pointer;">
                                    <i class="mdi mdi-key-variant text-muted me-2"></i>
                                    <span class="fw-medium">{{ $perm->name }}</span>
                                  </label>
                                </td>
                                <td style="width: 100px;" class="text-end">
                                  @if(in_array($perm->name, $assignedPermissions, true))
                                    <span class="badge bg-success">
                                      <i class="mdi mdi-check"></i> Active
                                    </span>
                                  @else
                                    <span class="badge bg-secondary">
                                      <i class="mdi mdi-minus"></i> Inactive
                                    </span>
                                  @endif
                                </td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
              <a href="{{ route('access.roles.index') }}" class="btn btn-outline-secondary">
                <i class="mdi mdi-arrow-left me-1"></i>
                Back to Roles
              </a>
              <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                <i class="mdi mdi-content-save-outline me-2"></i>
                Save Permissions
              </button>
            </div>
          </form>
        @else
          <div class="text-center py-5">
            <i class="mdi mdi-alert-circle-outline text-muted" style="font-size: 4rem;"></i>
            <h5 class="mt-3 text-muted">No Roles Available</h5>
            <p class="text-muted">Please create a role first before managing permissions.</p>
            <a href="{{ route('access.roles.index') }}" class="btn btn-primary mt-2">
              <i class="mdi mdi-plus me-1"></i>
              Create Role
            </a>
          </div>
        @endif

      </div>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script>
  (function () {
    // Update selected count
    function updateSelectedCount() {
      const checked = document.querySelectorAll('.perm-checkbox:checked').length;
      const total = document.querySelectorAll('.perm-checkbox').length;
      document.getElementById('selectedCount').textContent = checked + ' of ' + total + ' selected';
    }

    // Check All functionality
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
      checkAll.addEventListener('change', function () {
        document.querySelectorAll('.perm-checkbox').forEach(function (cb) {
          cb.checked = checkAll.checked;
        });
        document.querySelectorAll('.check-group').forEach(function (g) {
          g.checked = checkAll.checked;
        });
        updateSelectedCount();
        updateGroupBadges();
      });
    }

    // Check per-group
    document.querySelectorAll('.check-group').forEach(function (groupCb) {
      groupCb.addEventListener('change', function (e) {
        e.stopPropagation();
        const slug = this.getAttribute('data-group');
        document.querySelectorAll('.perm-group-' + slug).forEach(function (cb) {
          cb.checked = groupCb.checked;
        });
        updateSelectedCount();
        updateGroupBadge(slug);
        updateCheckAllState();
      });
    });

    // Individual checkbox change
    document.querySelectorAll('.perm-checkbox').forEach(function (cb) {
      cb.addEventListener('change', function () {
        updateSelectedCount();
        
        // Update group checkbox state
        const classes = Array.from(this.classList);
        const groupClass = classes.find(c => c.startsWith('perm-group-'));
        if (groupClass) {
          const slug = groupClass.replace('perm-group-', '');
          updateGroupCheckbox(slug);
          updateGroupBadge(slug);
        }
        
        updateCheckAllState();
      });
    });

    // Update group checkbox based on individual checkboxes
    function updateGroupCheckbox(slug) {
      const groupCheckboxes = document.querySelectorAll('.perm-group-' + slug);
      const groupCb = document.querySelector('.check-group[data-group="' + slug + '"]');
      
      if (groupCb && groupCheckboxes.length > 0) {
        const allChecked = Array.from(groupCheckboxes).every(cb => cb.checked);
        const someChecked = Array.from(groupCheckboxes).some(cb => cb.checked);
        
        groupCb.checked = allChecked;
        groupCb.indeterminate = someChecked && !allChecked;
      }
    }

    // Update "Check All" state
    function updateCheckAllState() {
      const allCheckboxes = document.querySelectorAll('.perm-checkbox');
      const checkedCheckboxes = document.querySelectorAll('.perm-checkbox:checked');
      
      if (checkAll) {
        checkAll.checked = allCheckboxes.length === checkedCheckboxes.length && allCheckboxes.length > 0;
        checkAll.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
      }
    }

    // Update badge for specific group
    function updateGroupBadge(slug) {
      const groupCheckboxes = document.querySelectorAll('.perm-group-' + slug);
      const checkedCount = Array.from(groupCheckboxes).filter(cb => cb.checked).length;
      const totalCount = groupCheckboxes.length;
      
      const accordionButton = document.querySelector('[data-bs-target="#group-' + slug + '"]');
      if (accordionButton) {
        const badge = accordionButton.querySelector('.badge');
        if (badge) {
          badge.textContent = checkedCount + '/' + totalCount;
        }
      }
    }

    // Update all group badges
    function updateGroupBadges() {
      document.querySelectorAll('.check-group').forEach(function (groupCb) {
        const slug = groupCb.getAttribute('data-group');
        updateGroupBadge(slug);
      });
    }

    // Initialize on page load
    updateSelectedCount();
    updateCheckAllState();
    
    // Initialize all group checkboxes state
    document.querySelectorAll('.check-group').forEach(function (groupCb) {
      const slug = groupCb.getAttribute('data-group');
      updateGroupCheckbox(slug);
    });
  })();
</script>
@endpush

@push('page-style')
<style>
  .bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }
  
  .accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #212529;
  }
  
  .accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
  }
  
  .table-hover tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
  }
  
  .form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
  }
  
  .form-check-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
  }
  
  .btn-primary {
    background-color: #667eea;
    border-color: #667eea;
  }
  
  .btn-primary:hover {
    background-color: #5568d3;
    border-color: #5568d3;
  }
  
  .badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
  }
</style>
@endpush