<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgPendaftaranOnline;
use App\Models\Antrean;
use App\Models\ApiRequestLog;
use App\Models\Operasi;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = date('Y-m-d');
        $weekEnd = date('Y-m-d', strtotime('+7 days'));

        $antreanToday = Antrean::where('tgl', $today)->where('batal', '<>', '1');

        $stats = [
            'total_antrean' => (clone $antreanToday)->count(),
            'sudah_checkin' => AgPendaftaranOnline::where('tanggal_periksa', $today)
                ->where('status_hadir', '1')
                ->whereNull('batal')
                ->count(),
            'belum_checkin' => AgPendaftaranOnline::where('tanggal_periksa', $today)
                ->where('status_hadir', '<>', '1')
                ->whereNull('batal')
                ->count(),
            'dibatalkan' => AgPendaftaranOnline::where('tanggal_periksa', $today)
                ->where('batal', '1')
                ->count(),
            'operasi_hari_ini' => Operasi::where('tanggal', $today)->count(),
            'api_logs_hari_ini' => ApiRequestLog::whereDate('created_at', $today)->count(),
        ];

        $stats['pct_checkin'] = $stats['total_antrean'] > 0
            ? round($stats['sudah_checkin'] / $stats['total_antrean'] * 100)
            : 0;
        $stats['pct_batal'] = $stats['total_antrean'] > 0
            ? round($stats['dibatalkan'] / $stats['total_antrean'] * 100)
            : 0;

        $apiTotal = ApiRequestLog::whereDate('created_at', $today)->count();
        $apiSuccess = ApiRequestLog::whereDate('created_at', $today)->where('response_status', 200)->count();
        $stats['uptime'] = $apiTotal > 0 ? round($apiSuccess / $apiTotal * 100, 2) : 100;

        $hourlyRaw = ApiRequestLog::whereDate('created_at', $today)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour');

        $apiChart = [];
        for ($h = 6; $h <= 18; $h++) {
            $apiChart[] = (int) ($hourlyRaw[$h] ?? 0);
        }
        $maxChart = max($apiChart) ?: 1;

        $recentAntrean = Antrean::with(['pendaftaran_online.poli', 'pendaftaran_online.dokter', 'poli'])
            ->where('tgl', $today)
            ->orderByDesc('no_urut')
            ->limit(10)
            ->get();

        $upcomingOperasi = Operasi::with(['unit.poli', 'pasien'])
            ->whereBetween('tanggal', [$today, $weekEnd])
            ->orderBy('tanggal')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact('stats', 'recentAntrean', 'upcomingOperasi', 'today', 'apiChart', 'maxChart'));
    }
}
