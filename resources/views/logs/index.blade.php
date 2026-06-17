@extends('layouts.app')

@section('title', 'Logs API')

@section('content')
@php
    $now = now()->locale('id');
@endphp

<div class="mf-page-header">
    <h1 class="mf-page-title">Logs API</h1>
    <div class="mf-page-meta">
        <i class="bx bx-calendar"></i>
        <span>{{ $now->isoFormat('dddd, D MMMM YYYY') }} | Audit trail Mobile JKN / BPJS</span>
    </div>
</div>

<div class="mf-panel">
    <div class="card-body p-4">
        <div class="filter-toolbar">
            <form id="logFilters" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" for="date_from">Dari Tanggal</label>
                    <input type="date" class="form-control" id="date_from" name="date_from">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_to">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="date_to" name="date_to">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="endpoint">Endpoint</label>
                    <select class="form-select" id="endpoint" name="endpoint">
                        <option value="">Semua</option>
                        @foreach ($endpoints as $ep)
                            <option value="{{ $ep }}">{{ $ep }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="response_status">HTTP Status</label>
                    <select class="form-select" id="response_status" name="response_status">
                        <option value="">Semua</option>
                        <option value="200">200</option>
                        <option value="201">201</option>
                        <option value="202">202</option>
                        <option value="400">400</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="client_ip">IP Client</label>
                    <input type="text" class="form-control" id="client_ip" name="client_ip" placeholder="10.0.0.1">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="client_username">Username Client</label>
                    <input type="text" class="form-control" id="client_username" name="client_username" placeholder="x-username">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" id="resetFilters">Reset</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table id="logsTable" class="mf-table w-100">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Method</th>
                        <th>Endpoint</th>
                        <th>IP</th>
                        <th>Client</th>
                        <th>HTTP</th>
                        <th>Code</th>
                        <th>Durasi</th>
                        <th class="no-export">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Detail Request Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Endpoint:</strong> <span id="detail-endpoint"></span></div>
                    <div class="col-md-3"><strong>Method:</strong> <span id="detail-method"></span></div>
                    <div class="col-md-3"><strong>Waktu:</strong> <span id="detail-created"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>IP:</strong> <span id="detail-ip"></span></div>
                    <div class="col-md-4"><strong>Client:</strong> <span id="detail-client"></span></div>
                    <div class="col-md-4"><strong>Durasi:</strong> <span id="detail-duration"></span></div>
                </div>
                <div class="mb-3">
                    <strong>URL:</strong>
                    <div class="text-muted small" id="detail-url"></div>
                </div>
                <div class="mb-3">
                    <strong>User Agent:</strong>
                    <div class="text-muted small" id="detail-ua"></div>
                </div>
                <div class="mb-3">
                    <strong>Request Headers</strong>
                    <pre class="json-preview" id="detail-headers"></pre>
                </div>
                <div class="mb-3">
                    <strong>Request Body</strong>
                    <pre class="json-preview" id="detail-request"></pre>
                </div>
                <div class="mb-3">
                    <strong>Response (HTTP <span id="detail-http"></span>, code <span id="detail-code"></span>)</strong>
                    <pre class="json-preview" id="detail-response"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('datatable-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('logFilters');
    const table = window.initServerDataTable('#logsTable', {
        ajax: {
            url: '{{ route('logs.data') }}',
            data: function (d) {
                d.date_from = document.getElementById('date_from').value;
                d.date_to = document.getElementById('date_to').value;
                d.endpoint = document.getElementById('endpoint').value;
                d.response_status = document.getElementById('response_status').value;
                d.client_ip = document.getElementById('client_ip').value;
                d.client_username = document.getElementById('client_username').value;
            },
        },
        columns: [
            { data: 'created_at' },
            { data: 'method', orderable: false, render: window.dtHtmlRender },
            { data: 'endpoint' },
            { data: 'client_ip' },
            { data: 'client_username' },
            { data: 'response_status', orderable: false, render: window.dtHtmlRender },
            { data: 'response_code' },
            { data: 'duration_ms' },
            { data: 'actions', orderable: false, searchable: false, render: window.dtHtmlRender },
        ],
    });

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

    document.getElementById('resetFilters').addEventListener('click', function () {
        filterForm.reset();
        table.ajax.reload();
    });

    document.getElementById('logsTable').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-log-detail');
        if (!btn) return;

        fetch('{{ url('/logs') }}/' + btn.dataset.id, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                document.getElementById('detail-endpoint').textContent = data.endpoint;
                document.getElementById('detail-method').textContent = data.method;
                document.getElementById('detail-created').textContent = data.created_at;
                document.getElementById('detail-ip').textContent = data.client_ip || '-';
                document.getElementById('detail-client').textContent = data.client_username || '-';
                document.getElementById('detail-duration').textContent = data.duration_ms ? data.duration_ms + ' ms' : '-';
                document.getElementById('detail-url').textContent = data.full_url || '-';
                document.getElementById('detail-ua').textContent = data.user_agent || '-';
                document.getElementById('detail-headers').textContent = JSON.stringify(data.request_headers || {}, null, 2);
                document.getElementById('detail-request').textContent = data.request_body || '-';
                document.getElementById('detail-http').textContent = data.response_status || '-';
                document.getElementById('detail-code').textContent = data.response_code ?? '-';
                document.getElementById('detail-response').textContent = data.response_body || '-';

                bootstrap.Modal.getOrCreateInstance(document.getElementById('logDetailModal')).show();
            });
    });
});
</script>
@endpush
