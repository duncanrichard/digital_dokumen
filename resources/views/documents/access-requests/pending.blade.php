@extends('layouts/contentNavbarLayout')

@section('title', 'Request Document Access')

@section('content')
<div class="row gy-4">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h4 class="card-title mb-0">Request Document Access</h4>
      </div>

      <div class="card-body">
        <div class="alert alert-info">
          <i class="mdi mdi-information-outline me-2"></i>
          Permintaan akses untuk dokumen ini sudah dikirim dan sedang menunggu persetujuan.
        </div>

        <h5 class="mb-3">Document Information</h5>
        <dl class="row mb-4">
          <dt class="col-sm-3">Document Number</dt>
          <dd class="col-sm-9">{{ $document->document_number }}</dd>

          <dt class="col-sm-3">Document Name</dt>
          <dd class="col-sm-9">{{ $document->name }}</dd>

          <dt class="col-sm-3">Department</dt>
          <dd class="col-sm-9">{{ optional($document->department)->code }} - {{ optional($document->department)->name }}</dd>

          <dt class="col-sm-3">Type</dt>
          <dd class="col-sm-9">{{ optional($document->jenisDokumen)->kode }} - {{ optional($document->jenisDokumen)->nama }}</dd>

          <dt class="col-sm-3">Publish Date</dt>
          <dd class="col-sm-9">
            {{ \Carbon\Carbon::parse($document->publish_date)->format('d M Y') }}
          </dd>

          <dt class="col-sm-3">Request Status</dt>
          <dd class="col-sm-9">
            <span class="badge bg-warning text-dark">Pending Approval</span>
          </dd>

          <dt class="col-sm-3">Requested At</dt>
          <dd class="col-sm-9">
            {{ optional($accessRequest->requested_at)->format('d M Y H:i') }}
          </dd>
        </dl>

        <div class="d-flex justify-content-between align-items-center">
          <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left"></i> Back to Document Library
          </a>

          @if($accessRequest->status === 'pending')
            <span class="text-muted small">
              Anda akan dapat mengakses dokumen ini setelah permintaan disetujui.
            </span>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
