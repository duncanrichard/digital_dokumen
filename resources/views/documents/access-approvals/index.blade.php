@extends('layouts/contentNavbarLayout')

@section('title', 'Document Control - Access Approvals')

@section('content')
@include('documents._legal_workflow')
<div class="row gy-4">
  <div class="col-12">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h4 class="card-title mb-1">Persetujuan Akses Dokumen</h4>
          <p class="text-muted mb-0 small">Setujui atau tolak permintaan akses khusus.</p>
        </div>

        <form method="get" class="approval-filter d-flex align-items-center gap-2 ms-auto" aria-label="Filter status">
          <label for="approval-status" class="form-label mb-0 text-muted small fw-semibold">Filter status</label>
          <select id="approval-status" name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Semua status</option>
            <option value="pending"  {{ ($status ?? '')==='pending'  ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ ($status ?? '')==='approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ ($status ?? '')==='rejected' ? 'selected' : '' }}>Rejected</option>
          </select>
        </form>
      </div>

      <div id="approval-table-container">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>User</th>
                <th>Department</th>
                <th>Document</th>
                <th>Reason</th>
                <th>Requested At</th>
                <th>Status</th>
                <th>Decision</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $row)
                <tr>
                  <td>{{ $items->firstItem() + $loop->index }}</td>
                  <td>{{ $row->user->name ?? '-' }}</td>
                  <td>{{ optional($row->document->department)->code ?? '-' }}</td>
                  <td>
                    {{ $row->document->document_number ?? '-' }}<br>
                    <small class="text-muted">{{ $row->document->name ?? '' }}</small>
                  </td>
                  <td style="max-width:250px;">
                    <small>{{ $row->reason }}</small>
                  </td>
                  <td>{{ optional($row->requested_at)->format('d M Y H:i') }}</td>
                  <td>
                    @if(strtolower($row->status) === 'pending')
                      <span class="badge bg-warning text-dark">Pending</span>
                    @elseif($row->status === 'approved')
                      <span class="badge bg-success">Approved</span>
                    @elseif(strtolower($row->status) === 'rejected')
                      <span class="badge bg-danger">Rejected</span>
                    @else
                      <span class="badge bg-secondary">Expired</span>
                    @endif
                  </td>
                  <td>
                    @if($row->decider)
                      <small>
                        {{ $row->decider->name }}<br>
                        {{ optional($row->decided_at)->format('d M Y H:i') }}
                      </small>
                    @else
                      <span class="text-muted small">-</span>
                    @endif
                  </td>
                  <td class="text-center">
                    @if(strtolower($row->status) === 'pending')
                      <div class="d-flex justify-content-center gap-2">
                        {{-- Approve --}}
                        <form method="post" action="{{ route('documents.approvals.approve', $row->id) }}" class="d-inline">
                          @csrf
                          @method('PUT')
                          <button class="btn btn-sm btn-success">
                            <i class="mdi mdi-check-circle-outline me-1"></i> Approve
                          </button>
                        </form>

                        {{-- Reject --}}
                        <form method="post" action="{{ route('documents.approvals.reject', $row->id) }}" class="d-inline">
                          @csrf
                          @method('PUT')
                          <button class="btn btn-sm btn-outline-danger">
                            <i class="mdi mdi-close-circle-outline me-1"></i> Reject
                          </button>
                        </form>
                      </div>
                    @else
                      <span class="text-muted small">No action</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center py-4 text-muted">
                    Tidak ada permintaan akses.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      @if($items->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
          <div class="text-muted small">
            Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} requests
          </div>
          {{ $items->links() }}
        </div>
      @endif
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<style>
  .approval-filter .form-select { min-width: 150px; border-radius: .5rem; }
  @media (max-width: 575.98px) {
    .approval-filter { width: 100%; margin-left: 0 !important; }
    .approval-filter .form-select { flex: 1; }
  }
</style>
<script>
document.addEventListener('click', function (event) {
  const link = event.target.closest('#approval-table-container .pagination a');
  if (!link) return;
  event.preventDefault();

  const container = document.getElementById('approval-table-container');
  container.classList.add('opacity-50');
  fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(response => response.text())
    .then(html => {
      const parsed = new DOMParser().parseFromString(html, 'text/html');
      const replacement = parsed.getElementById('approval-table-container');
      if (!replacement) throw new Error('Pagination response tidak valid');
      container.replaceWith(replacement);
      window.history.pushState({}, '', link.href);
      window.scrollTo({ top: replacement.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
    })
    .catch(() => window.location.assign(link.href));
});
</script>
@endsection
