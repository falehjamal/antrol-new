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
        $request->validate( [
            'tanggalawal' => 'required|date',
            'tanggalakhir' => 'required|date'
        ]);

        if($request->tanggalakhir < $request->tanggalawal) {
            return ResponseFormatter::error([], 'Tanggal akhir tidak boleh melebihi tanggal awal', 201);
        }

        $list = Operasi::whereBetween('tanggal', [$request->tanggalawal, $request->tanggalakhir])
                        ->where(function($query) {
                            $query->where('status', 'selesai');
                            $query->orWhereNull('status');
                        })
                        ->get();

        return ResponseFormatter::success([
            'list' => JadwalOperasiRSResource::collection($list)
        ], 'Ok');
    }

    public function pasien(Request $request)
    {
        $request->validate( [
            'nopeserta' => 'required|string|size:13',
        ]);

        $nopeserta = $request->nopeserta;

        $list = Operasi::whereHas('pasien', function(Builder $query) use ($nopeserta) {
                            $query->where('nopeserta', $nopeserta);
                        })
                        ->where(function($query) {
                            $query->where('status', 'selesai');
                            $query->orWhereNull('status');
                        })
                        ->get();

        return ResponseFormatter::success([
            'list' => JadwalOperasiPasienResource::collection($list)
        ], 'Ok');
    }
}
