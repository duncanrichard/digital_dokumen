@extends('layouts/contentNavbarLayout')

@section('title', 'Document Control - Access Approvals')

@section('content')
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
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
          <h4 class="card-title mb-1">Access Approvals</h4>
          <p class="text-muted mb-0 small">Approve / reject permintaan akses dokumen</p>
        </div>

        <form method="get" class="d-flex gap-2">
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All status</option>
            <option value="pending"  {{ ($status ?? '')==='pending'  ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ ($status ?? '')==='approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ ($status ?? '')==='rejected' ? 'selected' : '' }}>Rejected</option>
          </select>
        </form>
      </div>

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
                    @if($row->status === 'pending')
                      <span class="badge bg-warning text-dark">Pending</span>
                    @elseif($row->status === 'approved')
                      <span class="badge bg-success">Approved</span>
                    @else
                      <span class="badge bg-danger">Rejected</span>
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
                    @if($row->status === 'pending')
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
@endsection
