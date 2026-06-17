<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(): View
    {
        $endpoints = ApiRequestLog::query()
            ->select('endpoint')
            ->distinct()
            ->orderBy('endpoint')
            ->pluck('endpoint');

        return view('logs.index', compact('endpoints'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = ApiRequestLog::query()->orderByDesc('id');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('endpoint')) {
            $query->where('endpoint', $request->endpoint);
        }

        if ($request->filled('response_status')) {
            $query->where('response_status', $request->response_status);
        }

        if ($request->filled('client_ip')) {
            $query->where('client_ip', 'like', '%'.$request->client_ip.'%');
        }

        if ($request->filled('client_username')) {
            $query->where('client_username', 'like', '%'.$request->client_username.'%');
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('endpoint', 'like', "%{$search}%")
                    ->orWhere('client_ip', 'like', "%{$search}%")
                    ->orWhere('client_username', 'like', "%{$search}%")
                    ->orWhere('method', 'like', "%{$search}%");
            });
        }

        $total = ApiRequestLog::count();
        $filtered = $query->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);

        $rows = $query->skip($start)->take($length > 0 ? $length : 15)->get();

        $data = $rows->map(function (ApiRequestLog $log) {
            $methodClass = match ($log->method) {
                'GET' => 'mf-log-badge-info',
                'POST' => 'mf-log-badge-primary',
                'PUT', 'PATCH' => 'mf-log-badge-warning',
                'DELETE' => 'mf-log-badge-danger',
                default => 'mf-log-badge-muted',
            };

            return [
                'created_at' => $log->created_at?->format('d/m/Y H:i:s') ?? '-',
                'method' => '<span class="mf-log-badge '.$methodClass.'">'.$log->method.'</span>',
                'endpoint' => e($log->endpoint),
                'client_ip' => e($log->client_ip ?? '-'),
                'client_username' => e($log->client_username ?? '-'),
                'response_status' => $log->response_status
                    ? '<span class="mf-log-badge '.($log->response_status === 200 ? 'mf-log-badge-success' : 'mf-log-badge-warning').'">'.$log->response_status.'</span>'
                    : '-',
                'response_code' => $log->response_code ?? '-',
                'duration_ms' => $log->duration_ms !== null ? $log->duration_ms.' ms' : '-',
                'actions' => '<button type="button" class="btn btn-sm antrol-btn-primary btn-log-detail" style="width:auto;padding:0.35rem 0.75rem;font-size:0.75rem;" data-id="'.$log->id.'"><i class="bx bx-show"></i> Detail</button>',
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function show(ApiRequestLog $log): JsonResponse
    {
        return response()->json([
            'id' => $log->id,
            'method' => $log->method,
            'endpoint' => $log->endpoint,
            'full_url' => $log->full_url,
            'client_ip' => $log->client_ip,
            'user_agent' => $log->user_agent,
            'client_username' => $log->client_username,
            'request_headers' => $log->request_headers,
            'request_body' => $log->request_body,
            'response_status' => $log->response_status,
            'response_code' => $log->response_code,
            'response_body' => $log->response_body,
            'duration_ms' => $log->duration_ms,
            'created_at' => $log->created_at?->format('d/m/Y H:i:s'),
        ]);
    }
}
