<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Http\Resources\JadwalOperasiPasienResource;
use App\Http\Resources\JadwalOperasiRSResource;
use App\Models\Operasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class JadwalOperasiController extends Controller
{
    public function all(Request $request)
    {
        $request->validate([
            'tanggalawal' => 'required|date',
            'tanggalakhir' => 'required|date',
        ]);

        if ($request->tanggalakhir < $request->tanggalawal) {
            return ResponseFormatter::error([], 'Tanggal akhir tidak boleh melebihi tanggal awal', 201);
        }

        $list = $this->activeOperationQuery()
            ->whereBetween('tanggal', [$request->tanggalawal, $request->tanggalakhir])
            ->get();

        return ResponseFormatter::success([
            'list' => JadwalOperasiRSResource::collection($list),
        ], 'Ok');
    }

    public function pasien(Request $request)
    {
        $request->validate([
            'nopeserta' => 'required|string|size:13',
        ]);

        $nopeserta = $request->nopeserta;

        $list = $this->activeOperationQuery()
            ->whereHas('pasien', function (Builder $query) use ($nopeserta) {
                $query->where('nopeserta', $nopeserta);
            })
            ->get();

        return ResponseFormatter::success([
            'list' => JadwalOperasiPasienResource::collection($list),
        ], 'Ok');
    }

    private function activeOperationQuery(): Builder
    {
        return Operasi::query()
            ->where(function (Builder $query) {
                $query->where('status', 'selesai')
                    ->orWhereNull('status');
            });
    }
}
