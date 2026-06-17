@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $now = now()->locale('id');
@endphp

<div class="mf-page-header">
    <h1 class="mf-page-title">Healthcare Dashboard</h1>
    <div class="mf-page-meta">
        <i class="bx bx-calendar"></i>
        <span>{{ $now->isoFormat('dddd, D MMMM YYYY') }} | {{ $now->format('h:i A') }}</span>
    </div>
</div>

<div class="mf-stat-grid">
    <div class="mf-stat-card">
        <div class="mf-stat-top">
            <div class="mf-stat-icon mf-stat-icon-teal"><i class="bx bx-group"></i></div>
            <span class="mf-stat-badge mf-stat-badge-up">Hari ini</span>
        </div>
        <div class="mf-stat-label">Antrean Hari Ini</div>
        <div class="mf-stat-value">{{ number_format($stats['total_antrean']) }}</div>
    </div>

    <div class="mf-stat-card">
        <div class="mf-stat-top">
            <div class="mf-stat-icon mf-stat-icon-green"><i class="bx bx-check-circle"></i></div>
            <span class="mf-stat-badge mf-stat-badge-up">{{ $stats['pct_checkin'] }}%</span>
        </div>
        <div class="mf-stat-label">Telah Dilayani</div>
        <div class="mf-stat-value">{{ number_format($stats['sudah_checkin']) }}</div>
    </div>

    <div class="mf-stat-card">
        <div class="mf-stat-top">
            <div class="mf-stat-icon mf-stat-icon-orange"><i class="bx bx-time-five"></i></div>
            <span class="mf-stat-badge mf-stat-badge-wait">Waiting</span>
        </div>
        <div class="mf-stat-label">Dalam Antrian</div>
        <div class="mf-stat-value">{{ number_format($stats['belum_checkin']) }}</div>
    </div>

    <div class="mf-stat-card">
        <div class="mf-stat-top">
            <div class="mf-stat-icon mf-stat-icon-red"><i class="bx bx-x-circle"></i></div>
            <span class="mf-stat-badge mf-stat-badge-down">{{ $stats['pct_batal'] }}%</span>
        </div>
        <div class="mf-stat-label">Dibatalkan</div>
        <div class="mf-stat-value">{{ number_format($stats['dibatalkan']) }}</div>
    </div>
</div>

<div class="mf-mid-grid">
    <div class="mf-navy-card">
        <div class="mf-navy-card-icon"><i class="bx bx-plus-medical"></i></div>
        <h3>Jadwal Operasi</h3>
        <div class="mf-navy-value">{{ number_format($stats['operasi_hari_ini']) }}</div>
        <p>Total terjadwal hari ini</p>
    </div>

    <div class="mf-perf-card">
        <div>
            <h3>System Performance</h3>
            <div class="mf-perf-metrics">
                <div class="mf-perf-metric">
                    API Requests
                    <span class="teal">{{ number_format($stats['api_logs_hari_ini']) }} /hr</span>
                </div>
                <div class="mf-perf-metric">
                    Uptime
                    <span class="green">{{ $stats['uptime'] }}%</span>
                </div>
            </div>
        </div>
        <div class="mf-bar-chart" aria-hidden="true">
            @foreach ($apiChart as $bar)
                <div class="mf-bar" style="height: {{ max(8, round(($bar / $maxChart) * 72)) }}px;"></div>
            @endforeach
        </div>
    </div>
</div>

<div class="mf-panel-grid">
    <div class="mf-panel">
        <div class="mf-panel-header">
            <h3>Antrean Terbaru Hari Ini</h3>
            <a href="{{ route('logs.index') }}">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="mf-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Booking</th>
                        <th>Poli</th>
                        <th>Dokter</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentAntrean as $item)
                        <tr>
                            <td>{{ str_pad($item->no_urut, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="mono">{{ $item->kode_booking }}</td>
                            <td>{{ $item->pendaftaran_online?->poli?->nama ?? $item->poli?->nama ?? '-' }}</td>
                            <td>{{ $item->pendaftaran_online?->dokter?->NAMADOKTER ?? '-' }}</td>
                            <td>
                                @if ($item->batal == '1')
                                    <span class="mf-status mf-status-danger"><span class="mf-status-dot"></span> Batal</span>
                                @elseif ($item->pendaftaran_online?->status_hadir == '1')
                                    <span class="mf-status mf-status-success"><span class="mf-status-dot"></span> Dilayani</span>
                                @else
                                    <span class="mf-status mf-status-warning"><span class="mf-status-dot"></span> Menunggu</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada antrean hari ini</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mf-panel">
        <div class="mf-panel-header">
            <h3>Jadwal Operasi (7 Hari Ke Depan)</h3>
        </div>
        <div class="table-responsive">
            <table class="mf-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>ID Pasien</th>
                        <th>Poli/Unit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($upcomingOperasi as $op)
                        <tr>
                            <td>
                                {{ \Carbon\Carbon::parse($op->tanggal)->locale('id')->isoFormat('D MMM') }}
                                <span class="text-muted">| 08:00</span>
                            </td>
                            <td class="mono">{{ $op->pasien?->nopeserta ?? $op->nomr ?? '-' }}</td>
                            <td>{{ $op->unit?->nama_unit ?? '-' }}</td>
                            <td>
                                @if ($op->status === 'selesai')
                                    <span class="mf-pill mf-pill-green">Selesai</span>
                                @else
                                    <span class="mf-pill mf-pill-grey">Terjadwal</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Tidak ada jadwal operasi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
