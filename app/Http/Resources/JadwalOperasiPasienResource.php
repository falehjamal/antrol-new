<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JadwalOperasiPasienResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'kodebooking' => $this->id_operasi,
            'tanggaloperasi' => $this->tanggal,
            'jenistindakan' => strip_tags($this->tindakan),
            'kodepoli' => $this->unit?->poli?->KODE_BPJS ?? '-',
            'namapoli' => $this->unit?->nama_unit ?? '-',
            'terlaksana' => $this->status == 'selesai' ? 1 : 0,
        ];
    }
}
