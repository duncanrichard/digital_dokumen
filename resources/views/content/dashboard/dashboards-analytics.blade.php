@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard - Legal Analytics')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
@endsection

@section('page-script')
<script>
  // Inject data dari controller ke JS global untuk dipakai ApexCharts
  window.legalDashboard = {
    docsPerMonthLabels: @json($docsPerMonthLabels ?? []),
    docsPerMonthSeries: @json($docsPerMonthSeries ?? []),
    documentsByDepartment: @json($documentsByDepartment ?? []),
    documentsByType: @json($documentsByType ?? []),
    accessThisMonth: @json($accessThisMonth ?? []),
  };
</script>
<script src="{{ asset('assets/js/dashboards-analytics.js') }}"></script>
@endsection

@section('content')
<div class="row gy-4">
  <!-- Summary Legal card -->
  <div class="col-md-12 col-lg-4">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-1">Legal Document Dashboard 📚</h4>
        <p class="pb-0">Ringkasan aktivitas dokumen legal</p>
        <h4 class="text-primary mb-1">
          {{ number_format($summaryCards['total_documents'] ?? 0) }} Dokumen
        </h4>
        <p class="mb-2 pb-1">
          Aktif:
          <span class="text-success fw-medium">
            {{ number_format($summaryCards['active_documents'] ?? 0) }}
          </span> |
          Nonaktif:
          <span class="text-muted fw-medium">
            {{ number_format($summaryCards['inactive_documents'] ?? 0) }}
          </span>
        </p>
        <a href="{{ route('documents.index') }}" class="btn btn-sm btn-primary">
          Buka Library Dokumen
        </a>
      </div>
      <img src="{{ asset('assets/img/icons/misc/triangle-light.png') }}" class="scaleX-n1-rtl position-absolute bottom-0 end-0" width="166" alt="triangle background">
      <img src="{{ asset('assets/img/illustrations/trophy.png') }}" class="scaleX-n1-rtl position-absolute bottom-0 end-0 me-4 mb-4 pb-2" width="83" alt="legal trophy">
    </div>
  </div>
  <!--/ Summary Legal card -->

  <!-- KPI Bulanan -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
          <h5 class="card-title m-0 me-2">Aktivitas Bulan Ini</h5>
          <div class="dropdown">
            <button class="btn p-0" type="button" id="transactionID" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="mdi mdi-dots-vertical mdi-24px"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID">
              <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
              <a class="dropdown-item" href="javascript:void(0);">Export</a>
            </div>
          </div>
        </div>
        <p class="mt-3">
          <span class="fw-medium">
            Total dokumen publish bulan ini:
            {{ number_format($summaryCards['documents_this_month'] ?? 0) }}
          </span>
        </p>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3 col-6">
            <div class="d-flex align-items-center">
              <div class="avatar">
                <div class="avatar-initial bg-primary rounded shadow">
                  <i class="mdi mdi-file-document-outline mdi-24px"></i>
                </div>
              </div>
              <div class="ms-3">
                <div class="small mb-1">Dokumen Baru</div>
                <h5 class="mb-0">{{ number_format($summaryCards['new_docs_this_month'] ?? 0) }}</h5>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="d-flex align-items-center">
              <div class="avatar">
                <div class="avatar-initial bg-success rounded shadow">
                  <i class="mdi mdi-file-compare mdi-24px"></i>
                </div>
              </div>
              <div class="ms-3">
                <div class="small mb-1">Revisi Dokumen</div>
                <h5 class="mb-0">{{ number_format($summaryCards['revisions_this_month'] ?? 0) }}</h5>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="d-flex align-items-center">
              <div class="avatar">
                <div class="avatar-initial bg-warning rounded shadow">
                  <i class="mdi mdi-timer-sand mdi-24px"></i>
                </div>
              </div>
              <div class="ms-3">
                <div class="small mb-1">Akses Pending</div>
                <h5 class="mb-0">{{ number_format($summaryCards['pending_access'] ?? 0) }}</h5>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="d-flex align-items-center">
              <div class="avatar">
                <div class="avatar-initial bg-info rounded shadow">
                  <i class="mdi mdi-file-chart mdi-24px"></i>
                </div>
              </div>
              <div class="ms-3">
                <div class="small mb-1">Rata-rata Revisi</div>
                <h5 class="mb-0">{{ $summaryCards['avg_revision_per_doc'] ?? 0 }}</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ KPI Bulanan -->

  <!-- Dokumen per Bulan Chart -->
  <div class="col-xl-4 col-md-6">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between">
          <h5 class="mb-1">Dokumen per Bulan</h5>
          <div class="dropdown">
            <button class="btn p-0" type="button" id="weeklyOverviewDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="mdi mdi-dots-vertical mdi-24px"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="weeklyOverviewDropdown">
              <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
              <a class="dropdown-item" href="javascript:void(0);">Export</a>
            </div>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div id="weeklyOverviewChart"></div>
        <div class="mt-1 mt-md-3">
          <div class="d-flex align-items-center gap-3">
            <h3 class="mb-0">{{ number_format($summaryCards['documents_this_month'] ?? 0) }}</h3>
            <p class="mb-0">Total dokumen legal yang publish pada bulan berjalan.</p>
          </div>
          <div class="d-grid mt-3 mt-md-4">
            <a href="{{ route('documents.index') }}" class="btn btn-primary" type="button">
              Detail Dokumen
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Dokumen per Bulan Chart -->

  <!-- Master Data Overview -->
  <div class="col-xl-4 col-md-6">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0 me-2">Master Data Legal</h5>
        <div class="dropdown">
          <button class="btn p-0" type="button" id="totalEarnings" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="mdi mdi-dots-vertical mdi-24px"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="totalEarnings">
            <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
            <a class="dropdown-item" href="javascript:void(0);">Export</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="mb-3 mt-md-3 mb-md-5">
          <div class="d-flex align-items-center">
            <h2 class="mb-0">
              {{ number_format(($summaryCards['active_departments'] ?? 0) + ($summaryCards['active_doc_types'] ?? 0)) }}
            </h2>
            <span class="text-success ms-2 fw-medium">
              <i class="mdi mdi-check-circle-outline mdi-24px"></i>
              <small>Aktif</small>
            </span>
          </div>
          <small class="mt-1 d-block">
            Divisi & Jenis dokumen yang aktif saat ini.
          </small>
        </div>
        <ul class="p-0 m-0">
          <li class="d-flex mb-4 pb-md-2">
            <div class="avatar flex-shrink-0 me-3">
              <div class="avatar-initial bg-label-primary rounded-circle">D</div>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <h6 class="mb-0">Departments</h6>
                <small>Aktif vs Nonaktif</small>
              </div>
              <div>
                <h6 class="mb-2">
                  {{ number_format($summaryCards['active_departments'] ?? 0) }} aktif
                </h6>
                @php
                  $depTotal = ($summaryCards['active_departments'] ?? 0) + ($summaryCards['inactive_departments'] ?? 0);
                  $depPercent = $depTotal ? round(($summaryCards['active_departments'] ?? 0) / $depTotal * 100) : 0;
                @endphp
                <div class="progress bg-label-primary" style="height: 4px;">
                  <div class="progress-bar bg-primary"
                       style="width: {{ $depPercent }}%"
                       role="progressbar"
                       aria-valuenow="{{ $depPercent }}"
                       aria-valuemin="0"
                       aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </li>
          <li class="d-flex mb-4 pb-md-2">
            <div class="avatar flex-shrink-0 me-3">
              <div class="avatar-initial bg-label-info rounded-circle">T</div>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <h6 class="mb-0">Jenis Dokumen</h6>
                <small>Aktif vs Nonaktif</small>
              </div>
              <div>
                <h6 class="mb-2">
                  {{ number_format($summaryCards['active_doc_types'] ?? 0) }} aktif
                </h6>
                @php
                  $typeTotal = ($summaryCards['active_doc_types'] ?? 0) + ($summaryCards['inactive_doc_types'] ?? 0);
                  $typePercent = $typeTotal ? round(($summaryCards['active_doc_types'] ?? 0) / $typeTotal * 100) : 0;
                @endphp
                <div class="progress bg-label-info" style="height: 4px;">
                  <div class="progress-bar bg-info"
                       style="width: {{ $typePercent }}%"
                       role="progressbar"
                       aria-valuenow="{{ $typePercent }}"
                       aria-valuemin="0"
                       aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </li>
          <li class="d-flex mb-md-3">
            <div class="avatar flex-shrink-0 me-3">
              <div class="avatar-initial bg-label-secondary rounded-circle">R</div>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <h6 class="mb-0">Dokumen Tahun Ini</h6>
                <small>Akumulasi sejak awal tahun</small>
              </div>
              <div>
                <h6 class="mb-2">
                  {{ number_format($summaryCards['documents_this_year'] ?? 0) }} dokumen
                </h6>
                <div class="progress bg-label-secondary" style="height: 4px;">
                  <div class="progress-bar bg-secondary"
                       style="width: 75%"
                       role="progressbar"
                       aria-valuenow="75"
                       aria-valuemin="0"
                       aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <!--/ Master Data Overview -->

  <!-- Four Cards: Access & Revisi snapshot -->
  <div class="col-xl-4 col-md-6">
    <div class="row gy-4">
      <!-- Total Dokumen Aktif line chart placeholder -->
      <div class="col-sm-6">
        <div class="card h-100">
          <div class="card-header pb-0">
            <h4 class="mb-0">{{ number_format($summaryCards['active_documents'] ?? 0) }}</h4>
          </div>
          <div class="card-body">
            <div id="totalProfitLineChart" class="mb-3"></div>
            <h6 class="text-center mb-0">Dokumen Aktif</h6>
          </div>
        </div>
      </div>
      <!--/ Total Dokumen Aktif -->

      <!-- Access approval -->
      <div class="col-sm-6">
        <div class="card h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <div class="avatar">
              <div class="avatar-initial bg-secondary rounded-circle shadow">
                <i class="mdi mdi-lock-open-check mdi-24px"></i>
              </div>
            </div>
            <div class="dropdown">
              <button class="btn p-0" type="button" id="totalProfitID" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="mdi mdi-dots-vertical mdi-24px"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="totalProfitID">
                <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                <a class="dropdown-item" href="{{ route('documents.approvals.index') }}">Lihat Persetujuan</a>
              </div>
            </div>
          </div>
          <div class="card-body mt-mg-1">
            <h6 class="mb-2">Permintaan Akses</h6>
            <div class="d-flex flex-wrap align-items-center mb-2 pb-1">
              <h4 class="mb-0 me-2">
                {{ number_format($summaryCards['approved_access'] ?? 0) }}
              </h4>
              <small class="text-success mt-1">Approved</small>
            </div>
            <small>Pending: {{ number_format($summaryCards['pending_access'] ?? 0) }}</small>
          </div>
        </div>
      </div>

      <!-- Revisi total -->
      <div class="col-sm-6">
        <div class="card h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <div class="avatar">
              <div class="avatar-initial bg-primary rounded-circle shadow-sm">
                <i class="mdi mdi-file-compare mdi-24px"></i>
              </div>
            </div>
            <div class="dropdown">
              <button class="btn p-0" type="button" id="newProjectID" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="mdi mdi-dots-vertical mdi-24px"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="newProjectID">
                <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                <a class="dropdown-item" href="javascript:void(0);">Lihat Revisi</a>
              </div>
            </div>
          </div>
          <div class="card-body mt-mg-1">
            <h6 class="mb-2">Revisi Bulan Ini</h6>
            <div class="d-flex flex-wrap align-items-center mb-2 pb-1">
              <h4 class="mb-0 me-2">{{ number_format($summaryCards['revisions_this_month'] ?? 0) }}</h4>
              <small class="text-info mt-1">Dokumen</small>
            </div>
            <small>Monitor perubahan regulasi & kebijakan.</small>
          </div>
        </div>
      </div>

      <!-- Access status chart placeholder -->
      <div class="col-sm-6">
        <div class="card h-100">
          <div class="card-header pb-0">
            <h4 class="mb-0">
              {{ number_format(
                ($summaryCards['pending_access'] ?? 0) +
                ($summaryCards['approved_access'] ?? 0) +
                ($summaryCards['rejected_access'] ?? 0)
              ) }}
            </h4>
          </div>
          <div class="card-body">
            <div id="sessionsColumnChart" class="mb-3"></div>
            <h6 class="text-center mb-0">Permintaan Akses (Total)</h6>
          </div>
        </div>
      </div>
      <!--/ Access status chart -->
    </div>
  </div>
  <!--/ Four Cards -->

  <!-- Dokumen per Departemen -->
  <div class="col-xl-4 col-md-6">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0 me-2">Dokumen per Departemen</h5>
        <div class="dropdown">
          <button class="btn p-0" type="button" id="saleStatus" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="mdi mdi-dots-vertical mdi-24px"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="saleStatus">
            <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
            <a class="dropdown-item" href="javascript:void(0);">Export</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        @php
          $totalDeptDocs = ($documentsByDepartment ?? collect())->sum('total');
        @endphp

        @forelse($documentsByDepartment ?? [] as $row)
        @php
          $percent = $totalDeptDocs ? round(($row->total / $totalDeptDocs) * 100, 1) : 0;
        @endphp
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
          <div class="d-flex align-items-center">
            <div class="avatar me-3">
              <div class="avatar-initial bg-label-success rounded-circle">
                {{ mb_substr($row->department_name, 0, 2) }}
              </div>
            </div>
            <div>
              <div class="d-flex align-items-center gap-1">
                <h6 class="mb-0">{{ number_format($row->total) }} dokumen</h6>
                <i class="mdi mdi-file-document-outline mdi-24px text-success"></i>
                <small class="text-success">{{ $percent }}%</small>
              </div>
              <small>{{ $row->department_name }}</small>
            </div>
          </div>
          <div class="text-end">
            <h6 class="mb-0">{{ number_format($row->total) }}</h6>
            <small>Dokumen</small>
          </div>
        </div>
        @empty
        <p class="mb-0 text-muted">Belum ada data dokumen per departemen.</p>
        @endforelse
      </div>
    </div>
  </div>
  <!--/ Dokumen per Departemen -->

  <!-- Ringkasan Permintaan Akses -->
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-body row g-2">
        <div class="col-12 col-md-6 card-separator pe-0 pe-md-3">
          <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <h5 class="m-0 me-2">Status Permintaan Akses (Bulan Ini)</h5>
            <a class="fw-medium" href="{{ route('documents.approvals.index') }}">Lihat semua</a>
          </div>
          <div class="pt-2">
            <ul class="p-0 m-0">
              <li class="d-flex mb-4 align-items-center pb-2">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar-initial rounded-circle bg-label-warning d-flex align-items-center justify-content-center" style="width: 36px; height:36px;">
                    <i class="mdi mdi-timer-sand mdi-18px"></i>
                  </div>
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <h6 class="mb-0">Pending</h6>
                    <small>Menunggu persetujuan</small>
                  </div>
                  <h6 class="text-warning mb-0">
                    +{{ number_format($accessThisMonth['pending'] ?? 0) }}
                  </h6>
                </div>
              </li>
              <li class="d-flex mb-4 align-items-center pb-2">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar-initial rounded-circle bg-label-success d-flex align-items-center justify-content-center" style="width: 36px; height:36px;">
                    <i class="mdi mdi-check-circle-outline mdi-18px"></i>
                  </div>
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <h6 class="mb-0">Approved</h6>
                    <small>Diizinkan akses dokumen</small>
                  </div>
                  <h6 class="text-success mb-0">
                    +{{ number_format($accessThisMonth['approved'] ?? 0) }}
                  </h6>
                </div>
              </li>
              <li class="d-flex align-items-center pb-2">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar-initial rounded-circle bg-label-danger d-flex align-items-center justify-content-center" style="width: 36px; height:36px;">
                    <i class="mdi mdi-close-circle-outline mdi-18px"></i>
                  </div>
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <h6 class="mb-0">Rejected</h6>
                    <small>Ditolak oleh approver</small>
                  </div>
                  <h6 class="text-danger mb-0">
                    +{{ number_format($accessThisMonth['rejected'] ?? 0) }}
                  </h6>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- Dokumen per Jenis -->
        <div class="col-12 col-md-6 ps-0 ps-md-3 mt-3 mt-md-2">
          <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <h5 class="m-0 me-2">Dokumen per Jenis</h5>
            <a class="fw-medium" href="{{ route('master.jenis-dokumen.index') }}">Kelola jenis</a>
          </div>
          <div class="pt-2">
            <ul class="p-0 m-0">
              @php
                $totalTypeDocs = ($documentsByType ?? collect())->sum('total');
              @endphp

              @forelse($documentsByType ?? [] as $row)
              @php
                $percentType = $totalTypeDocs ? round(($row->total / $totalTypeDocs) * 100) : 0;
              @endphp
              <li class="d-flex mb-4 align-items-center pb-2">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar-initial rounded-circle bg-label-info d-flex align-items-center justify-content-center" style="width: 36px; height:36px;">
                    {{ mb_substr($row->type_name, 0, 2) }}
                  </div>
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <h6 class="mb-0">{{ $row->type_name }}</h6>
                    <small>{{ number_format($row->total) }} dokumen</small>
                  </div>
                  <div class="text-end">
                    <h6 class="mb-2">{{ $percentType }}%</h6>
                    <div class="progress bg-label-info" style="height: 4px; min-width:120px;">
                      <div class="progress-bar bg-info"
                           style="width: {{ $percentType }}%"
                           role="progressbar"
                           aria-valuenow="{{ $percentType }}"
                           aria-valuemin="0"
                           aria-valuemax="100"></div>
                    </div>
                  </div>
                </div>
              </li>
              @empty
              <li>
                <p class="mb-0 text-muted">Belum ada data dokumen per jenis.</p>
              </li>
              @endforelse
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Ringkasan Permintaan Akses & Dokumen per Jenis -->

  <!-- Tabel Ringkasan KPI -->
  <div class="col-12">
    <div class="card">
      <div class="table-responsive">
        <table class="table">
          <thead class="table-light">
            <tr>
              <th class="text-truncate">Kategori</th>
              <th class="text-truncate">Sub Kategori</th>
              <th class="text-truncate">Nilai</th>
              <th class="text-truncate">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="text-truncate">Dokumen</td>
              <td class="text-truncate">Total Dokumen</td>
              <td class="text-truncate">
                {{ number_format($summaryCards['total_documents'] ?? 0) }}
              </td>
              <td class="text-truncate">Seluruh dokumen legal yang terdaftar.</td>
            </tr>
            <tr>
              <td class="text-truncate">Dokumen</td>
              <td class="text-truncate">Aktif / Nonaktif</td>
              <td class="text-truncate">
                {{ number_format($summaryCards['active_documents'] ?? 0) }}
                /
                {{ number_format($summaryCards['inactive_documents'] ?? 0) }}
              </td>
              <td class="text-truncate">Status keberlakuan dokumen legal.</td>
            </tr>
            <tr>
              <td class="text-truncate">Dokumen</td>
              <td class="text-truncate">Bulan Ini</td>
              <td class="text-truncate">
                {{ number_format($summaryCards['documents_this_month'] ?? 0) }}
              </td>
              <td class="text-truncate">Total dokumen publish pada bulan berjalan.</td>
            </tr>
            <tr>
              <td class="text-truncate">Revisi</td>
              <td class="text-truncate">Revisi Bulan Ini</td>
              <td class="text-truncate">
                {{ number_format($summaryCards['revisions_this_month'] ?? 0) }}
              </td>
              <td class="text-truncate">Jumlah dokumen yang mengalami revisi bulan ini.</td>
            </tr>
            <tr>
              <td class="text-truncate">Akses</td>
              <td class="text-truncate">Pending / Approved / Rejected</td>
              <td class="text-truncate">
                {{ number_format($summaryCards['pending_access'] ?? 0) }} /
                {{ number_format($summaryCards['approved_access'] ?? 0) }} /
                {{ number_format($summaryCards['rejected_access'] ?? 0) }}
              </td>
              <td class="text-truncate">Status global permintaan akses dokumen.</td>
            </tr>
            <tr>
              <td class="text-truncate">Master Data</td>
              <td class="text-truncate">Departments Aktif</td>
              <td class="text-truncate">
                {{ number_format($summaryCards['active_departments'] ?? 0) }}
              </td>
              <td class="text-truncate">Divisi yang masih aktif menggunakan sistem.</td>
            </tr>
            <tr>
              <td class="text-truncate">Master Data</td>
              <td class="text-truncate">Jenis Dokumen Aktif</td>
              <td class="text-truncate">
                {{ number_format($summaryCards['active_doc_types'] ?? 0) }}
              </td>
              <td class="text-truncate">Tipe dokumen legal yang aktif digunakan.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <!--/ Tabel Ringkasan KPI -->
</div>
@endsection
