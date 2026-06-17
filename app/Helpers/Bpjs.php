<?php

namespace App\Helpers;

use NajmulFaiz\Bpjs\Antrean\WSBpjs;
use NajmulFaiz\Bpjs\VClaim\Rujukan;

class Bpjs
{
    public static function rujukan_by_nomorreferensi($nomorreferensi)
    {
        $rujukan = new Rujukan(config('bpjs.vclaim'));

        $data_rujukan = $rujukan->cariByNoRujukan('', $nomorreferensi);
        if ($data_rujukan['metaData']['code'] !== '200') {
            $data_rujukan = $rujukan->cariByNoRujukan('RS', $nomorreferensi);
        }

        if ($data_rujukan['metaData']['code'] !== '200') {
            return null;
        }

        return $data_rujukan['response'];
    }

    public static function cek_jadwal_hfis($tanggal, $poli)
    {
        $antrean = new WSBpjs(config('bpjs.antrean'));

        $jadwal_dokter = $antrean->refJadwalDokter($poli, $tanggal);

        if ($jadwal_dokter['metadata']['code'] !== 200) {
            return null;
        }

        return $jadwal_dokter['response'];
    }
}
