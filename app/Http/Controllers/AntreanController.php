<?php

namespace App\Http\Controllers;

use App\Helpers\Bpjs;
use App\Helpers\ResponseFormatter;
use App\Models\AgPendaftaranOnline;
use App\Models\AgPendaftaranOnlineBpjs;
use App\Models\Antrean;
use App\Models\Dokter;
use App\Models\JadwalDokter;
use App\Models\JamPelayanan;
use App\Models\KuotaPoli;
use App\Models\Pasien;
use App\Models\Pendaftaran;
use App\Models\Poli;
use App\Models\PoliEstimasi;
use App\Models\Tarif;
use App\Models\TmpCartBayar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NajmulFaiz\Bpjs\Antrean\WSBpjs;
use Illuminate\Support\Facades\Http;

class AntreanController extends Controller
{

    public function status(Request $request)
    {
        $request->validate( [
            'kodepoli'       => 'required',
            'kodedokter'     => 'required',
            'tanggalperiksa' => 'required|date',
            'jampraktek'     => 'required',
        ]);

        $poli = Poli::where('KODE_BPJS', $request->kodepoli)->first();
        if (empty($poli)) {
            return ResponseFormatter::error([], 'Poli tidak belum tersedia untuk pendaftaran online.', 201);
        }

        $dokter = Dokter::where('KODE_DPJP_BPJS', $request->kodedokter)->first();
        if (empty($dokter)) {
            return ResponseFormatter::error([], 'Dokter belum tersedia untuk pendaftaran online', 201);
        }

        if ($request->tanggalperiksa < date('Y-m-d')) {
            return ResponseFormatter::error([], 'Tanggal Periksa Tidak Berlaku', 201);
        }

        if ($request->tanggalperiksa > date('Y-m-d', strtotime('+5 days'))) {
            return ResponseFormatter::error([], 'Tanggal Periksa Belum Tersedia', 201);
        }

        $jampraktek = explode('-', $request->jampraktek);
        $jam_pelayanan = JamPelayanan::where('kodepoly', $poli->kode)
            ->where('kodedokter', $dokter->KDDOKTER)
            ->where('hari', date('N', strtotime($request->tanggalperiksa)))
            ->where('jam_mulai', $jampraktek[0])
            ->where('jam_selesai', $jampraktek[1])
            ->first();

        if (!$jam_pelayanan) {
            return ResponseFormatter::error([], 'Jadwal Dokter ' . $dokter->NAMADOKTER . ' Tersebut Belum Tersedia untuk online, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya', 201);
        }

        $kuota_nilai = $jam_pelayanan->kuota == '' ? 60 : $jam_pelayanan->kuota;
        $sesi = $jam_pelayanan->sesi ?? 'pagi';

        $totalantrean = Antrean::where('tgl', $request->tanggalperiksa)
            ->where('kd_poli', $poli->kode)
            ->where('kd_dokter', $dokter->KDDOKTER)
            ->where('batal', '<>', '1')
            ->where('sesi', $sesi)
            ->count();

        $antrean = Antrean::whereHas('pendaftaran', function (Builder $query) {
            $query->where('status', 0);
        })
            ->where('tgl', $request->tanggalperiksa)
            ->where('kd_poli', $poli->kode)
            ->where('kd_dokter', $dokter->KDDOKTER)
            ->where('sesi', $sesi)
            ->get();

        $sisaantrean = $kuota_nilai - $totalantrean;

        return ResponseFormatter::success([
            'namapoli' => $poli->nama,
            'namadokter' => $dokter->NAMADOKTER,
            'totalantrean' => $totalantrean,
            'sisaantrean' => $antrean->count(),
            'antreanpanggil' => $antrean->first() ? $antrean->first()->no_urut : null,
            'sisakuotajkn' => $sisaantrean < 1 ? 0 : $sisaantrean,
            'kuotajkn' => $kuota_nilai,
            'sisakuotanonjkn' => $sisaantrean < 1 ? 0 : $sisaantrean,
            'kuotanonjkn' => $kuota_nilai,
            'keterangan' => ""
        ], 'Ok');
    }

    public function ambil(Request $request)
    {
        $request->validate( [
            'nomorkartu' => 'required|string|size:13',
            'nik' => 'required|string|size:16',
            'nohp' => 'required|string|min:10|max:13',
            'kodepoli' => 'required|exists:App\Models\Poli,KODE_BPJS',
            'norm' => 'nullable|string|size:6',
            'tanggalperiksa' => 'required|date',
            'kodedokter' => 'required|exists:App\Models\Dokter,KODE_DPJP_BPJS',
            'jampraktek' => 'required',
            'jeniskunjungan' => 'required|in:1,2,3,4',
            'nomorreferensi' => 'required',
        ]);

        // if($request->kodepoli == 'SAR') {
        //     return ResponseFormatter::error([], 'Mohon maaf, pendaftaran online sedang dalam perbaikan', 201);
        // }

        $tanggal_daftar = date('Y-m-d');
        if ($request->tanggalperiksa <= $tanggal_daftar) {
            if (time() <= strtotime($tanggal_daftar . ' ' . '11:00:00')) {
                // echo "MASUK 2 " . date('Y-m-d H:i:s');
            } else {
                return ResponseFormatter::error([], 'Pendaftaran untuk hari ini sudah ditutup', 201);
            }
        }

        // BEGIN CEK MASA BERLAKU RUJUKAN
        // $expired = strtotime("+30 days", time());
        // if(strtotime($request->tanggalperiksa) > $expired) {
        //     return ResponseFormatter::error([], 'Rujukan sudah tidak berlaku', 201);
        // }
        // END CEK MASA BERLAKU RUJUKAN

        // BEGIN CEK RUJUKAN
        // $rujukan = Bpjs::rujukan_by_nomorreferensi($request->nomorreferensi);
        // if(!$rujukan) {
        //     return ResponseFormatter::error([], 'Nomor rujukan tidak valid', 201);
        // }
        // END CEK RUJUKAN

        // BEGIN GET DATA POLI & DOKTER
        if ($request->kodepoli == 'IRM') {
            $poli = Poli::where('kode', 34)->first();
        } else {
            $poli = Poli::where('KODE_BPJS', $request->kodepoli)->first();
        }
        $dokter = Dokter::where('KODE_DPJP_BPJS', $request->kodedokter)->first();
        // END GET DATA POLI & DOKTER

        // BEGIN CEK JADWAL DOKTER HFIS
        // $jadwal_dokter = Bpjs::cek_jadwal_hfis($request->tanggalperiksa, $request->kodepoli);
        // if(!$jadwal_dokter) {
        //     return ResponseFormatter::error([], 'Pendaftaran ke Poli Ini Sedang Tutup', 201);
        // }

        // $jadwal_dokter = collect($jadwal_dokter);
        // $jadwal_dokter->where('kodedokter', $request->kodedokter)
        //                     ->where('jadwal', $request->jampraktek)
        //                     ->first();
        // if(!$jadwal_dokter) {
        //     return ResponseFormatter::error([], 'Jadwal Dokter ' . $dokter->NAMADOKTER . ' Tersebut Belum Tersedia, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya', 201);
        // }
        // END CEK JADWAL DOKTER HFIS

        // BEGIN CEK JADWAL DOKTER SIMRS
        if ($request->tanggalperiksa > date('Y-m-d', strtotime('+30 days'))) {
            return ResponseFormatter::error([], 'Pendaftaran ke Poli Ini Belum Tersedia', 201);
        }

        $jadwal_dokter = JadwalDokter::where('tanggal', $request->tanggalperiksa)
            ->where('kodepoly', $poli->kode)
            ->where('kodedokter', $dokter->KDDOKTER)
            ->first();
        if (empty($jadwal_dokter)) {
            return ResponseFormatter::error([], 'Jadwal Dokter ' . $dokter->NAMADOKTER . ' Tersebut Belum Tersedia untuk online, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya', 201);
        }
        // END CEK JADWAL DOKTER SIMRS

        // BEGIN CEK TANGGAL PERIKSA
        // date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
        // if($request->tanggalperiksa <= date('Y-m-d')) {
        //     return ResponseFormatter::error([], 'Pendaftaran untuk tanggal ' . $request->tanggalperiksa . ' sudah ditutup', 201);
        // }
        // END CEK TANGGAL PERIKSA

        // BEGIN CEK ANTRIAN
        $totalantrean = Antrean::where('tgl', $request->tanggalperiksa)
                                ->where('kd_poli', $poli->kode)
                                ->where('batal', '<>', '1')
                                ->count();

        $kuota = KuotaPoli::where('poly', $poli->kode)
            ->where('hari', date('N', strtotime($request->tanggalperiksa)))
            ->first();

        $sisaantrean = $kuota ? $kuota->nilai - $totalantrean : 0;
        if ($sisaantrean <= 0) {
            // return ResponseFormatter::error([], 'Pendaftaran ke Poli Ini Sedang Tutup', 201);
            return ResponseFormatter::error([], 'Kuota untuk poli ini sudah penuh', 201);
        }
        // END CEK ANTRIAN

        $nomr       = $request->nomr;
        $nik        = $request->nik;
        $nomorkartu = $request->nomorkartu;
        // BEGIN CEK PENDAFTARAN
        $pendaftaran = AgPendaftaranOnline::where('tanggal_periksa', $request->tanggalperiksa)
            ->where('kodepoly', $poli->kode)
            ->where(function ($query) use ($nomr, $nik, $nomorkartu) {
                $query->where('nomr', $nomr);
                $query->orWhere('nik', $nik);
                $query->orWhere('no_kartu', $nomorkartu);
            })
            ->whereNull('batal')
            ->first();

        if (!empty($pendaftaran)) {
            return ResponseFormatter::error([], 'Nomor Antrean Hanya Dapat Diambil 1 Kali Pada Tanggal Yang Sama', 201);
        }
        // END CEK PENDAFTARAN

        // DATA PASIEN
        $pasien = Pasien::where('NOKTP', $request->nik)
            ->orWhere('nopeserta', $request->nomorkartu)
            ->first();

        $jenis_kelamin = ['L' => 1, 'P' => 2];
        if (empty($pasien)) {
            return ResponseFormatter::error([], 'Data pasien ini tidak ditemukan, silahkan Melakukan Registrasi Pasien Baru', 202);
        }

        $pendaftaran_nama            = $pasien->NAMA;
        $pendaftaran_nomr            = $pasien->NOMR;
        $pendaftaran_provinsi        = $pasien->KDPROVINSI;
        $pendaftaran_kota            = $pasien->KOTA;
        $pendaftaran_kecamatan       = $pasien->KDKECAMATAN;
        $pendaftaran_kelurahan       = $pasien->KELURAHAN;
        $pendaftaran_alamat          = $pasien->ALAMAT;
        $pendaftaran_jenis_kelamin   = in_array($pasien->JENISKELAMIN, ['L', 'P']) ? $jenis_kelamin[$pasien->JENISKELAMIN] : $pasien->JENISKELAMIN;
        $pendaftaran_notelp          = $request->nohp;
        $pendaftaran_tempat          = $pasien->TEMPAT;
        $pendaftaran_tgllahir        = $pasien->TGLLAHIR;
        // END DATA PASIEN

        // BEGIN SIMPAN PENDAFTARAN ONLINE
        $pendaftaran_online                     = new AgPendaftaranOnline;
        $pendaftaran_online->nama                 = $pendaftaran_nama;
        $pendaftaran_online->nomr                 = $pendaftaran_nomr;
        $pendaftaran_online->provinsi             = $pendaftaran_provinsi;
        $pendaftaran_online->kota                 = $pendaftaran_kota;
        $pendaftaran_online->kecamatan             = $pendaftaran_kecamatan;
        $pendaftaran_online->kelurahan             = $pendaftaran_kelurahan;
        $pendaftaran_online->alamat             = $pendaftaran_alamat;
        $pendaftaran_online->jenis_kelamin         = $pendaftaran_jenis_kelamin;
        $pendaftaran_online->kodepoly             = $poli->kode;
        $pendaftaran_online->kodedokter         = $dokter->KDDOKTER;
        $pendaftaran_online->telepon             = $pendaftaran_notelp;
        $pendaftaran_online->tempat             = $pendaftaran_tempat;
        $pendaftaran_online->tanggal_lahir         = $pendaftaran_tgllahir;
        $pendaftaran_online->tanggal_daftar     = date('Y-m-d H:i:s');
        $pendaftaran_online->tanggal_periksa     = $request->tanggalperiksa;
        $pendaftaran_online->nik                 = $request->nik;
        // $pendaftaran_online->cara_bayar		    = $rujukan['rujukan']['peserta']['jenisPeserta']['kode'] == '21' ? 10: 20;
        $pendaftaran_online->cara_bayar            = 10;
        $pendaftaran_online->no_kartu             = $request->nomorkartu;
        $pendaftaran_online->via                 = 'mobile_jkn';
        $pendaftaran_online->via_new_app         = 1;
        $pendaftaran_online->versi_baru         = 1;
        $pendaftaran_online->status_hadir         = 0;
        $pendaftaran_online->save();
        // END SIMPAN PENDAFTARAN ONLINE

        // BEGIN SIMPAN PENDAFTARAN ONLINE
        $pendaftaran_online_bpjs = new AgPendaftaranOnlineBpjs();
        $pendaftaran_online_bpjs->id_pendaftaran_online = $pendaftaran_online->id;
        $pendaftaran_online_bpjs->no_kartu              = $pendaftaran_online->no_kartu;
        $pendaftaran_online_bpjs->ppk_pelayanan         = '1133R001';
        // $pendaftaran_online_bpjs->jenis_pelayanan       = $rujukan['rujukan']['pelayanan']['kode'];
        $pendaftaran_online_bpjs->kelas_rawat           = 3;
        // $pendaftaran_online_bpjs->nomor_rujukan         = $rujukan['rujukan']['noKunjungan'];
        // $pendaftaran_online_bpjs->tgl_rujukan           = $rujukan['rujukan']['tglKunjungan'];
        // $pendaftaran_online_bpjs->ppk_rujukan           = $rujukan['rujukan']['provPerujuk']['kode'];
        // $pendaftaran_online_bpjs->nama_ppk_rujukan      = $rujukan['rujukan']['provPerujuk']['nama'];
        // $pendaftaran_online_bpjs->diagnosa_awal         = $rujukan['rujukan']['diagnosa']['kode'] . ' - ' . $rujukan['rujukan']['diagnosa']['nama'];
        // $pendaftaran_online_bpjs->poli                  = $rujukan['rujukan']['poliRujukan']['kode'];
        // $pendaftaran_online_bpjs->no_telp               = $rujukan['rujukan']['peserta']['mr']['noTelepon'];
        // $pendaftaran_online_bpjs->no_kartu              = $rujukan['rujukan']['peserta']['noKartu'];
        $pendaftaran_online_bpjs->save();
        // END SIMPAN PENDAFTARAN ONLINE

        $antrean_akhir = Antrean::where('tgl', $request->tanggalperiksa)
            ->where('kd_poli', $poli->kode)
            ->orderBy('no_urut', 'desc')
            ->first();

        $estimasi = PoliEstimasi::where('poly', $poli->kode)
                                ->first();

        $estimasi = $antrean_akhir ? date('Y-m-d H:i:s', strtotime('+' . $estimasi->waktu . ' minutes', strtotime($antrean_akhir->estimasi))) : date('Y-m-d H:i:s', strtotime($request->tanggalperiksa . $estimasi->jam_mulai));
        $urut_baru = $antrean_akhir ? $antrean_akhir->no_urut + 1 : 1;

        $kodebooking = date('Ymd', strtotime($request->tanggalperiksa)) . '-' . sprintf('%03s', $poli->kode) . '-' . sprintf('%03s', $urut_baru);

        $antrean_baru = new Antrean;
        $antrean_baru->kd_poli      = $poli->kode;
        $antrean_baru->id_online    = $pendaftaran_online->id;
        $antrean_baru->no_urut      = $urut_baru;
        $antrean_baru->tgl          = $request->tanggalperiksa;
        $antrean_baru->estimasi     = $estimasi;
        $antrean_baru->kode_booking = $kodebooking;
        $antrean_baru->nomr         = $pendaftaran_online->nomr;
        $antrean_baru->save();

        // $data_antrean = [
        //     'kodebooking'      => $kodebooking,
        //     'jenispasien'      => "JKN",
        //     'nomorkartu'       => $pendaftaran_online->no_kartu,
        //     'nik'              => $pendaftaran_online->nik,
        //     'nohp'             => $pendaftaran_online->telepon,
        //     'kodepoli'         => $poli->KODE_BPJS,
        //     'namapoli'         => $poli->nama,
        //     'pasienbaru'       => 0,
        //     'norm'             => $pendaftaran_online->nomr,
        //     'tanggalperiksa'   => $pendaftaran_online->tanggal_periksa,
        //     'kodedokter'       => $dokter->KODE_DPJP_BPJS,
        //     'namadokter'       => $dokter->NAMADOKTER,
        //     'jampraktek'       => $request->jampraktek,
        //     'jeniskunjungan'   => $request->jeniskunjungan,
        //     'nomorreferensi'   => $request->nomorreferensi,
        //     'nomorantrean'     => $antrean_baru->no_urut,
        //     'angkaantrean'     => $antrean_baru->no_urut,
        //     'estimasidilayani' => strtotime($estimasi) * 1000,
        //     'sisakuotajkn'     => $sisaantrean - 1,
        //     'kuotajkn'         => $kuota->nilai,
        //     'sisakuotanonjkn'  => $sisaantrean - 1,
        //     'kuotanonjkn'      => $kuota->nilai,
        //     'keterangan'       => 'Peserta harap 60 menit lebih awal guna pencatatan administrasi.'
        // ];

        // $antrean_bpjs = new WSBpjs(config('bpjs.antrean'));
        // $response_antrol = $antrean_bpjs->tambahAntrean($data_antrean);

        // $antrean_baru->request = json_encode($data_antrean);
        // $antrean_baru->response = json_encode($response_antrol);
        // $antrean_baru->update();

        // dispatch(new ProccessReferensi($request->nomorreferensi, $request->jeniskunjungan, $pendaftaran_online->id));
        dispatch(new \App\Jobs\SyncRujukanJob($pendaftaran_online->id));

        return ResponseFormatter::success([
            'nomorantrean' => $antrean_baru->no_urut,
            'angkaantrean' => $antrean_baru->no_urut,
            'kodebooking' => $kodebooking,
            'norm' => $pendaftaran_online->nomr,
            'namapoli' => $poli->nama,
            'namadokter' => $dokter->NAMADOKTER,
            'estimasidilayani' => strtotime($estimasi) * 1000,
            'sisakuotajkn' => $sisaantrean - 1,
            'kuotajkn' => $kuota->nilai,
            'sisakuotanonjkn' => $sisaantrean - 1,
            'kuotanonjkn' => $kuota->nilai,
            'keterangan' => "Peserta harap 60 menit lebih awal guna pencatatan administrasi.",
        ], "Ok", 200);
    }

    public function pasien_baru(Request $request)
    {
        $request->validate( [
            "nomorkartu" => 'required|string|size:13',
            "nik" => 'required|string|size:16',
            "nomorkk" => 'required|string|size:16',
            "nama" => 'required|string|max:255',
            "jeniskelamin" => 'required|in:L,P',
            "tanggallahir" => 'required|date|before_or_equal:today',
            "nohp" => 'required|string|max:20',
            "alamat" => 'required|string',
            "kodeprop" => 'required|string',
            "namaprop" => 'required|string',
            "kodedati2" => 'required|string',
            "namadati2" => 'required|string',
            "kodekec" => 'required|string',
            "namakec" => 'required|string',
            "kodekel" => 'required|string',
            "namakel" => 'required|string',
            "rw" => 'required|string',
            "rt" => 'required|string',
        ]);

        $pasien = Pasien::where('noktp', $request->nik)
            ->orWhere('nopeserta', $request->nomorkartu)
            ->first();

        if (!empty($pasien)) {
            return ResponseFormatter::error([], 'Data Peserta Sudah Pernah Dientrikan', 201);
        }

        $max_nomr = Pasien::select('nomr')->orderBy('nomr', 'desc')->first();
        $new_nomr = sprintf('%06s', $max_nomr->nomr + 1);

        $new_pasien = new Pasien;
        $new_pasien->NOMR = $new_nomr;
        $new_pasien->NAMA = $request->nama;
        $new_pasien->TGLLAHIR = $request->tanggallahir;
        $new_pasien->JENISKELAMIN = $request->jeniskelamin;
        $new_pasien->ALAMAT = $request->alamat;
        $new_pasien->nopeserta = $request->nomorkartu;
        $new_pasien->NOTELP = $request->nohp;

        if (!$new_pasien->save()) {
            return ResponseFormatter::error([], 'Pendaftaran pasien baru gagal', 201);
        }

        return ResponseFormatter::success([
            'norm' => $new_pasien->NOMR
        ], 'Harap datang ke admisi untuk melengkapi data rekam medis');
    }

    public function sisa(Request $request)
    {
        $request->validate( [
            'kodebooking' => 'required',
        ]);

        $antrol = Antrean::where('kode_booking', $request->kodebooking)
            ->orWhere('id_online', $request->kodebooking)
            ->first();

        if (empty($antrol)) {
            return ResponseFormatter::error([], 'Kode booking tidak ditemukan (Antrean)', 201);
        }

        // $pendaftaran = AgPendaftaranOnline::where('id', $request->kodebooking)
        //                                     ->first();

        if (!$antrol->pendaftaran_online) {
            return ResponseFormatter::error([], 'Kode booking tidak ditemukan (Pendaftaran)', 201);
        }

        $pendaftaran = $antrol->pendaftaran_online;

        $sesi = $antrol->sesi ?? 'pagi';
        $jam_pelayanan = JamPelayanan::where('kodepoly', $pendaftaran->kodepoly)
            ->where('kodedokter', $pendaftaran->kodedokter)
            ->where('hari', date('N', strtotime($pendaftaran->tanggal_periksa)))
            ->where('sesi', $sesi)
            ->orderBy('jam_mulai')
            ->first();

        if (!$jam_pelayanan) {
            return ResponseFormatter::error([], 'Jadwal Dokter ' . $pendaftaran->dokter->NAMADOKTER . ' Tersebut Belum Tersedia untuk online, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya', 201);
        }

        $kuota_nilai = $jam_pelayanan->kuota == '' ? 60 : $jam_pelayanan->kuota;
        $sesi = $jam_pelayanan->sesi ?? $sesi;

        $totalantrean = Antrean::where('tgl', $pendaftaran->tanggal_periksa)
            ->where('kd_poli', $pendaftaran->kodepoly)
            ->where('kd_dokter', $pendaftaran->kodedokter)
            ->where('batal', '<>', '1')
            ->where('sesi', $sesi)
            ->count();

        $antrean = Antrean::whereHas('pendaftaran', function (Builder $query) {
            $query->where('status', 0);
        })
            ->where('tgl', $pendaftaran->tanggal_periksa)
            ->where('kd_poli', $pendaftaran->kodepoly)
            ->where('kd_dokter', $pendaftaran->kodedokter)
            ->where('sesi', $sesi)
            ->get();

        $sisaantrean = $kuota_nilai - $totalantrean;

        return ResponseFormatter::success([
            'namapoli' => $pendaftaran->poli->nama,
            'namadokter' => $pendaftaran->dokter->NAMADOKTER,
            'totalantrean' => $totalantrean,
            'sisaantrean' => $antrean->count(),
            'antreanpanggil' => $antrean->first() ? $antrean->first()->no_urut : null,
            'sisakuotajkn' => $sisaantrean < 1 ? 0 : $sisaantrean,
            'kuotajkn' => $kuota_nilai,
            'sisakuotanonjkn' => $sisaantrean < 1 ? 0 : $sisaantrean,
            'kuotanonjkn' => $kuota_nilai,
            'keterangan' => ""
        ], 'Ok');
    }

    public function batal(Request $request)
    {
        $request->validate( [
            'kodebooking' => 'required',
            'keterangan' => 'required',
        ]);

        $antrol = DB::connection('mysql2')
                    ->table('no_antrian')
                    ->leftJoin('ag_pendaftaran_online', 'ag_pendaftaran_online.id', '=', 'no_antrian.id_online')
                    ->whereRaw('no_antrian.kode_booking = ? OR no_antrian.id_online = ?', [$request->kodebooking, $request->kodebooking])
                    ->select('no_antrian.kode_booking', 'ag_pendaftaran_online.id')
                    ->first();

        if (empty($antrol)) {
            return ResponseFormatter::error([], 'Kode booking tidak ditemukan (Antrean)', 201);
        }

        if (!$antrol->id) {
            return ResponseFormatter::error([], 'Kode booking tidak ditemukan (Pendaftaran)', 201);
        }

        // $pendaftaran = $antrol->pendaftaran_online;
        $pendaftaran = AgPendaftaranOnline::where('id', $antrol->id)->first();

        if ($pendaftaran->status_hadir == '1') {
            return ResponseFormatter::error([], 'Pasien Sudah Dilayani, Antrean Tidak Dapat Dibatalkan', 201);
        }

        if ($pendaftaran->batal == '1') {
            return ResponseFormatter::error([], 'Antrean Tidak Ditemukan atau Sudah Dibatalkan', 201);
        }

        $pendaftaran->batal = 1;
        $pendaftaran->alasan_batal = $request->keterangan;

        if (!$pendaftaran->save()) {
            return ResponseFormatter::error([], 'Gagal', 201);
        }

        $antrean = Antrean::where('kode_booking', $request->kodebooking)->first();
        $antrean->batal = 1;
        $antrean->alasan_batal = $request->keterangan;

        if (!$antrean->update()) {
            return ResponseFormatter::error([], 'Gagal', 201);
        }

        return ResponseFormatter::success([], 'Ok');
    }

    public function checkin(Request $request)
    {
        $request->validate( [
            'kodebooking' => 'required',
            'waktu'       => 'required',
        ]);

        // $pendaftaran_online = AgPendaftaranOnline::where('id', $request->kodebooking)
        //                                         ->first();

        Log::info('BEGIN CHECKIN ' . $request->kodebooking . ' | ' . $request->waktu);

        $antrean = Antrean::where('kode_booking', $request->kodebooking)
            ->orWhere('id_online', $request->kodebooking)
            ->first();

        if (empty($antrean)) {
            Log::info('Kode Booking Tidak Ditemukan');
            return ResponseFormatter::error([], 'Kode booking tidak ditemukan', 201);
        }

        $pendaftaran_online = $antrean->pendaftaran_online;

        if ($pendaftaran_online->tanggal_periksa != date('Y-m-d', ($request->waktu / 1000))) {
            Log::info('Tanggal Check In Tidak Sama');
            return ResponseFormatter::error([], 'Check in hanya dapat dilakukan sesuai tanggal periksa', 201);
        }

        if ($pendaftaran_online->status_hadir == '1') {
            Log::info('Pasien Sudah Check In');
            return ResponseFormatter::error([], 'Pasien sudah melakukan check in', 201);
        }

        if ($pendaftaran_online->batal == '1') {
            Log::info('Pasien Sudah Batal');
            return ResponseFormatter::error([], 'Antrean tidak ditemukan atau sudah dibatalkan', 201);
        }

        $pendaftaran  = new Pendaftaran;
        $pendaftaran->NOMR              = $pendaftaran_online->nomr;
        $pendaftaran->KDRUJUK           = $pendaftaran_online->KDRUJUK == '0' ? '1' : $pendaftaran_online->KDRUJUK;
        $pendaftaran->TGLREG            = $pendaftaran_online->tanggal_periksa;
        $pendaftaran->KDDOKTER          = $pendaftaran_online->kodedokter;
        $pendaftaran->KDPOLY            = $pendaftaran_online->kodepoly;
        $pendaftaran->KDCARABAYAR       = $pendaftaran_online->cara_bayar;
        $pendaftaran->SHIFT             = '1';
        $pendaftaran->STATUS            = '0';
        $pendaftaran->KETERANGAN_STATUS = '0';
        $pendaftaran->PASIENBARU        = '1';
        $pendaftaran->JAMREG            = date('Y-m-d H:i:s');
        $pendaftaran->NIP               = 'pendaftaran';
        $pendaftaran->save();

        $tarif = Tarif::where('kode_unit', $pendaftaran_online->kodepoly)
            ->where('kode_profesi', $pendaftaran_online->dokter->KDPROFESI)
            ->first();

        $ip = $request->ip();
        $tmpCartBayar = new TmpCartBayar;
        $tmpCartBayar->KODETARIF      = $tarif->kode_tindakan;
        $tmpCartBayar->QTY            = 1;
        $tmpCartBayar->IP             = $ip;
        $tmpCartBayar->ID             = $tarif->kode_tindakan;
        $tmpCartBayar->POLY           = $pendaftaran_online->kodepoly;
        $tmpCartBayar->KDDOKTER       = $pendaftaran_online->kodedokter;
        $tmpCartBayar->TARIF          = $tarif->tarif;
        $tmpCartBayar->TOTTARIF       = $tarif->tarif;
        $tmpCartBayar->JASA_PELAYANAN = $tarif->jasa_pelayanan;
        $tmpCartBayar->JASA_SARANA    = $tarif->jasa_sarana;
        $tmpCartBayar->UNIT           = $pendaftaran_online->kodepoly;
        $tmpCartBayar->id_kategori    = $tarif->id_kategori;
        $tmpCartBayar->save();

        $save_tindakan = DB::connection('mysql2')->select('CALL pr_savebill_tindakanrajal_dokter("' . $pendaftaran_online->nomr . '", "1", "pendaftaran", "' . $pendaftaran->IDXDAFTAR . '", CURDATE(), 0, 0, "' . $ip . '", "' . $pendaftaran_online->cara_bayar . '", "' . $pendaftaran_online->kodepoly . '", 0, "' . $pendaftaran_online->kodedokter . '", "' . $pendaftaran_online->kodepoly . '")');

        $pendaftaran_online->status_hadir  = '1';
        $pendaftaran_online->status_berkas = '1';
        $pendaftaran_online->update();

        // $antrean = Antrean::where('id_online', $request->kodebooking)
        //                     ->first();
        $antrean->idxdaftar = $pendaftaran->IDXDAFTAR;
        $antrean->update();

        Log::info('Berhasil Check In');

        return ResponseFormatter::success([], 'Ok');
    }

    public function ambilv2(Request $request)
    {
        $request->validate( [
            'nomorkartu' => 'required|string|size:13',
            'nik' => 'required|string|size:16',
            'nohp' => 'required|string|min:10|max:13',
            'kodepoli' => 'required|exists:App\Models\Poli,KODE_BPJS',
            'norm' => 'nullable|string|size:6',
            'tanggalperiksa' => 'required|date',
            'kodedokter' => 'required|exists:App\Models\Dokter,KODE_DPJP_BPJS',
            'jampraktek' => 'required',
            'jeniskunjungan' => 'required|in:1,2,3,4',
            'nomorreferensi' => 'required',
        ]);

        // BEGIN CEK WAKTU DAFTAR
        $tanggal_daftar = date('Y-m-d');
        if ($request->tanggalperiksa <= $tanggal_daftar) {
            if (time() <= strtotime($tanggal_daftar . ' ' . '11:00:00')) {
                // echo "MASUK 2 " . date('Y-m-d H:i:s');
            } else {
                return ResponseFormatter::error([], 'Pendaftaran untuk hari ini sudah ditutup', 201);
            }
        }

        if ($request->tanggalperiksa > date('Y-m-d', strtotime('+30 days'))) {
            return ResponseFormatter::error([], 'Pendaftaran ke Poli Ini Belum Tersedia', 201);
        }
        // END CEK WAKTU DAFTAR


        // BEGIN GET DATA POLI & DOKTER
        if ($request->kodepoli == 'IRM') {
            $poli = Poli::where('kode', 34)->first();
        } else {
            $poli = Poli::where('KODE_BPJS', $request->kodepoli)->first();
        }
        $dokter = Dokter::where('KODE_DPJP_BPJS', $request->kodedokter)->first();
        // END GET DATA POLI & DOKTER

        // BEGIN CEK JADWAL DOKTER SIMRS
        $jadwal_dokter = JadwalDokter::where('tanggal', $request->tanggalperiksa)
            ->where('kodepoly', $poli->kode)
            ->where('kodedokter', $dokter->KDDOKTER)
            ->first();
        if (empty($jadwal_dokter)) {
            return ResponseFormatter::error([], 'Jadwal Dokter ' . $dokter->NAMADOKTER . ' Tersebut Belum Tersedia untuk online, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya', 201);
        }
        // END CEK JADWAL DOKTER SIMRS

        // BEGIN GET KUOTA, ESTIMASI LAYAN, JAM MULAI
        $jam_pelayanan = JamPelayanan::where('kodepoly', $poli->kode)
                                        ->where('kodedokter', $dokter->KDDOKTER)
                                        ->where('hari', date('N', strtotime($request->tanggalperiksa)))
                                        ->first();

        $setting_kuota = KuotaPoli::where('poly', $poli->kode)
                                    ->where('hari', date('N', strtotime($request->tanggalperiksa)))
                                    ->first();

        $setting_estimasi = PoliEstimasi::where('poly', $poli->kode)
                                        ->first();

        $kuota            = null;
        $jam_mulai        = null;
        $estimasi_layanan = null;
        if($request->kodepoli == 'JAN') {
            $kuota            = $jam_pelayanan->kuota;
            $jam_mulai        = $jam_pelayanan->jam_mulai;
            $estimasi_layanan = $jam_pelayanan->estimasi;
        } else {
            $kuota            = $setting_kuota->nilai;
            $jam_mulai        = $setting_estimasi->jam_mulai;
            $estimasi_layanan = $setting_estimasi->waktu;
        }
        // END GET KUOTA, ESTIMASI LAYAN, JAM MULAI

        // BEGIN CEK ANTRIAN
        $totalantrean = Antrean::where('tgl', $request->tanggalperiksa)
                                ->where('kd_poli', $poli->kode)
                                ->where('batal', '<>', '1')
                                ->count();

        $sisaantrean = $kuota ? $kuota - $totalantrean : 0;
        if ($sisaantrean <= 0) {
            return ResponseFormatter::error([], 'Kuota untuk poli ini sudah penuh', 201);
        }
        // END CEK ANTRIAN

        // BEGIN GENERATE KODEBOOKING
        $antrean_akhir = Antrean::where('tgl', $request->tanggalperiksa)
                                ->where('kd_poli', $poli->kode)
                                ->orderBy('no_urut', 'desc')
                                ->first();

        $estimasi = $antrean_akhir ? date('Y-m-d H:i:s', strtotime('+' . $estimasi_layanan . ' minutes', strtotime($antrean_akhir->estimasi))) : date('Y-m-d H:i:s', strtotime($request->tanggalperiksa . $jam_mulai));
        $urut_baru = $antrean_akhir ? $antrean_akhir->no_urut + 1 : 1;

        $kodebooking = date('Ymd', strtotime($request->tanggalperiksa)) . '-' . sprintf('%03s', $poli->kode) . '-' . sprintf('%03s', $urut_baru);
        // END GENERATE KODEBOOKING

        $nomr       = $request->nomr;
        $nik        = $request->nik;
        $nomorkartu = $request->nomorkartu;
        // BEGIN CEK PENDAFTARAN
        $pendaftaran = AgPendaftaranOnline::where('tanggal_periksa', $request->tanggalperiksa)
                                            ->where('kodepoly', $poli->kode)
                                            ->where(function ($query) use ($nomr, $nik, $nomorkartu) {
                                                $query->where('nomr', $nomr);
                                                $query->orWhere('nik', $nik);
                                                $query->orWhere('no_kartu', $nomorkartu);
                                            })
                                            ->whereNull('batal')
                                            ->first();

        if (!empty($pendaftaran)) {
            return ResponseFormatter::error([], 'Nomor Antrean Hanya Dapat Diambil 1 Kali Pada Tanggal Yang Sama', 201);
        }
        // END CEK PENDAFTARAN

        // DATA PASIEN
        $pasien = Pasien::where('NOKTP', $request->nik)
            ->orWhere('nopeserta', $request->nomorkartu)
            ->first();

        $jenis_kelamin = ['L' => 1, 'P' => 2];
        if (empty($pasien)) {
            return ResponseFormatter::error([], 'Data pasien ini tidak ditemukan, silahkan Melakukan Registrasi Pasien Baru', 202);
        }

        $pendaftaran_nama            = $pasien->NAMA;
        $pendaftaran_nomr            = $pasien->NOMR;
        $pendaftaran_provinsi        = $pasien->KDPROVINSI;
        $pendaftaran_kota            = $pasien->KOTA;
        $pendaftaran_kecamatan       = $pasien->KDKECAMATAN;
        $pendaftaran_kelurahan       = $pasien->KELURAHAN;
        $pendaftaran_alamat          = $pasien->ALAMAT;
        $pendaftaran_jenis_kelamin   = in_array($pasien->JENISKELAMIN, ['L', 'P']) ? $jenis_kelamin[$pasien->JENISKELAMIN] : $pasien->JENISKELAMIN;
        $pendaftaran_notelp          = $request->nohp;
        $pendaftaran_tempat          = $pasien->TEMPAT;
        $pendaftaran_tgllahir        = $pasien->TGLLAHIR;
        // END DATA PASIEN

        // BEGIN SIMPAN PENDAFTARAN ONLINE
        $pendaftaran_online                  = new AgPendaftaranOnline;
        $pendaftaran_online->nama            = $pendaftaran_nama;
        $pendaftaran_online->nomr            = $pendaftaran_nomr;
        $pendaftaran_online->provinsi        = $pendaftaran_provinsi;
        $pendaftaran_online->kota            = $pendaftaran_kota;
        $pendaftaran_online->kecamatan       = $pendaftaran_kecamatan;
        $pendaftaran_online->kelurahan       = $pendaftaran_kelurahan;
        $pendaftaran_online->alamat          = $pendaftaran_alamat;
        $pendaftaran_online->jenis_kelamin   = $pendaftaran_jenis_kelamin;
        $pendaftaran_online->kodepoly        = $poli->kode;
        $pendaftaran_online->kodedokter      = $dokter->KDDOKTER;
        $pendaftaran_online->telepon         = $pendaftaran_notelp;
        $pendaftaran_online->tempat          = $pendaftaran_tempat;
        $pendaftaran_online->tanggal_lahir   = $pendaftaran_tgllahir;
        $pendaftaran_online->tanggal_daftar  = date('Y-m-d H:i:s');
        $pendaftaran_online->tanggal_periksa = $request->tanggalperiksa;
        $pendaftaran_online->nik             = $request->nik;
        $pendaftaran_online->cara_bayar      = 10;
        $pendaftaran_online->no_kartu        = $request->nomorkartu;
        $pendaftaran_online->via             = 'mobile_jkn';
        $pendaftaran_online->via_new_app     = 1;
        $pendaftaran_online->versi_baru      = 1;
        $pendaftaran_online->status_hadir    = 0;
        $pendaftaran_online->save();
        // END SIMPAN PENDAFTARAN ONLINE

        // BEGIN SIMPAN PENDAFTARAN ONLINE
        $pendaftaran_online_bpjs = new AgPendaftaranOnlineBpjs();
        $pendaftaran_online_bpjs->id_pendaftaran_online = $pendaftaran_online->id;
        $pendaftaran_online_bpjs->no_kartu              = $pendaftaran_online->no_kartu;
        $pendaftaran_online_bpjs->ppk_pelayanan         = '1133R001';
        $pendaftaran_online_bpjs->kelas_rawat           = 3;
        $pendaftaran_online_bpjs->save();
        // END SIMPAN PENDAFTARAN ONLINE

        $antrean_baru = new Antrean;
        $antrean_baru->kd_poli      = $poli->kode;
        $antrean_baru->kd_dokter    = $dokter->KDDOKTER;
        $antrean_baru->id_online    = $pendaftaran_online->id;
        $antrean_baru->no_urut      = $urut_baru;
        $antrean_baru->tgl          = $request->tanggalperiksa;
        $antrean_baru->estimasi     = $estimasi;
        $antrean_baru->kode_booking = $kodebooking;
        $antrean_baru->nomr         = $pendaftaran_online->nomr;
        $antrean_baru->save();

        // dispatch(new ProccessReferensi($request->nomorreferensi, $request->jeniskunjungan, $pendaftaran_online->id));

        return ResponseFormatter::success([
            'nomorantrean'     => $urut_baru,
            'angkaantrean'     => $urut_baru,
            'kodebooking'      => $kodebooking,
            'norm'             => $pasien->NOMR,
            'namapoli'         => $poli->nama,
            'namadokter'       => $dokter->NAMADOKTER,
            'estimasidilayani' => strtotime($estimasi) * 1000,
            'sisakuotajkn'     => $sisaantrean - 1,
            'kuotajkn'         => $kuota,
            'sisakuotanonjkn'  => $sisaantrean - 1,
            'kuotanonjkn'      => $kuota,
            'keterangan'       => "Peserta harap 60 menit lebih awal guna pencatatan administrasi.",
        ], "Ok", 200);
    }

    public function ambilv3(Request $request)
    {
        $request->validate( [
            'nomorkartu' => 'required|string|size:13',
            'nik' => 'required|string|size:16',
            'nohp' => 'required|string|min:10|max:13',
            'kodepoli' => 'required|exists:App\Models\Poli,KODE_BPJS',
            'norm' => 'nullable|string|size:6',
            'tanggalperiksa' => 'required|date',
            'kodedokter' => 'required|exists:App\Models\Dokter,KODE_DPJP_BPJS',
            'jampraktek' => 'required',
            'jeniskunjungan' => 'required|in:1,2,3,4',
            'nomorreferensi' => 'required',
        ]);

        Log::info('Request : ' . json_encode($request->all()));

        // BEGIN CEK WAKTU DAFTAR
        $tanggal_daftar = date('Y-m-d');
        $jampraktek = explode('-', $request->jampraktek);
        // Ambil jam selesai praktek
        $jam_selesai = $jampraktek[1];
        // Hitung batas waktu: 30 menit sebelum jam selesai
        $batas_waktu = date('H:i:s', strtotime('-30 minutes', strtotime($jam_selesai)));

        if ($request->tanggalperiksa <= $tanggal_daftar) {
            if (time() <= strtotime($tanggal_daftar . ' ' . $batas_waktu)) {
                // echo "MASUK 2 " . date('Y-m-d H:i:s');
            } else {
                return ResponseFormatter::error([], 'Pendaftaran untuk hari ini sudah ditutup', 201);
            }
        }

        if ($request->tanggalperiksa > date('Y-m-d', strtotime('+30 days'))) {
            return ResponseFormatter::error([], 'Pendaftaran ke Poli Ini Belum Tersedia', 201);
        }
        // END CEK WAKTU DAFTAR

        // BEGIN GET DATA POLI & DOKTER
        if ($request->kodepoli == 'IRM') {
            $poli = Poli::where('kode', 34)->first();
        } else {
            $poli = Poli::where('KODE_BPJS', $request->kodepoli)->first();
        }
        $dokter = Dokter::where('KODE_DPJP_BPJS', $request->kodedokter)->first();
        // END GET DATA POLI & DOKTER

        // BEGIN CEK JADWAL DOKTER SIMRS
        $jadwal_dokter = JadwalDokter::where('tanggal', $request->tanggalperiksa)
            ->where('kodepoly', $poli->kode)
            ->where('kodedokter', $dokter->KDDOKTER)
            ->first();
        if (empty($jadwal_dokter)) {
            return ResponseFormatter::error([], 'Jadwal Dokter ' . $dokter->NAMADOKTER . ' Tersebut Belum Tersedia untuk online, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya', 201);
        }
        // END CEK JADWAL DOKTER SIMRS

        // BEGIN GET KUOTA, ESTIMASI LAYAN, JAM MULAI
        $kd_poli = $poli->kode;
        $kd_dokter = $dokter->KDDOKTER; // 803 36
        $tanggal = $request->tanggalperiksa;
        $jam_pelayanan = JamPelayanan::where('kodepoly', $kd_poli)
                                        ->where('kodedokter', $kd_dokter)
                                        ->where('hari', date('N', strtotime($tanggal)))
                                        ->where('jam_mulai', $jampraktek[0])
                                        ->where('jam_selesai', $jampraktek[1])
                                        ->first();

        if(!$jam_pelayanan) {
            return ResponseFormatter::error([], 'Jadwal Dokter ' . $dokter->NAMADOKTER . ' Tersebut Belum Tersedia untuk online, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya', 201);
        }

        $kuota            = $jam_pelayanan->kuota == '' ? 60 : $jam_pelayanan->kuota;
        $jam_mulai        = strtotime($tanggal . ' ' . $jam_pelayanan->jam_mulai);
        $jam_max_estimasi = strtotime('-30 minutes', strtotime($tanggal . ' ' . $jam_pelayanan->jam_selesai));
        $estimasi_layanan = $jam_pelayanan->estimasi ?? 6;
        $sesi = $jam_pelayanan->sesi ?? 'pagi';
        // END GET KUOTA, ESTIMASI LAYAN, JAM MULAI

        // BEGIN CEK ANTRIAN
        $totalantrean = Antrean::where('tgl', $request->tanggalperiksa)
                                ->where('kd_poli', $poli->kode)
                                ->where('kd_dokter', $kd_dokter)
                                ->where('batal', '<>', '1')
                                ->where('sesi', $sesi)
                                ->count();

        $sisaantrean = $kuota ? $kuota - $totalantrean : 0;
        if ($sisaantrean <= 0) {
            return ResponseFormatter::error([], 'Kuota untuk poli ini sudah penuh ', 201);
        }
        // END CEK ANTRIAN

        // BEGIN GENERATE KODEBOOKING
        $antrean_akhir = Antrean::where('tgl', $tanggal)
                                ->where('kd_poli', $kd_poli)
                                ->where('kd_dokter', $kd_dokter)
                                ->where('sesi', $sesi)
                                ->orderBy('no_urut', 'desc')
                                ->first();

        $urut_baru = $antrean_akhir ? $antrean_akhir->no_urut + 1 : 1;
        $estimasi = $antrean_akhir ? strtotime('+' . ($estimasi_layanan * ($urut_baru - 1)) . ' minutes', $jam_mulai) : $jam_mulai;
        if($estimasi > $jam_max_estimasi) {
            $estimasi = $jam_max_estimasi;
        }

        $prefix = date('ymd', strtotime($tanggal)) . sprintf('%03s', $kd_poli) . sprintf('%03s', $kd_dokter);
        $kodebooking = $prefix . '-' . sprintf('%03s', $urut_baru);
        // END GENERATE KODEBOOKING

        $nomr       = $request->nomr;
        $nik        = $request->nik;
        $nomorkartu = $request->nomorkartu;
        // BEGIN CEK PENDAFTARAN
        $pendaftaran = AgPendaftaranOnline::where('tanggal_periksa', $request->tanggalperiksa)
                                            ->where('kodepoly', $poli->kode)
                                            ->where(function ($query) use ($nomr, $nik, $nomorkartu) {
                                                $query->where('nomr', $nomr);
                                                $query->orWhere('nik', $nik);
                                                $query->orWhere('no_kartu', $nomorkartu);
                                            })
                                            ->whereNull('batal')
                                            ->first();

        if (!empty($pendaftaran)) {
            return ResponseFormatter::error([], 'Nomor Antrean Hanya Dapat Diambil 1 Kali Pada Tanggal Yang Sama', 201);
        }
        // END CEK PENDAFTARAN

        // DATA PASIEN
        $pasien = Pasien::where('NOKTP', $request->nik)
            ->orWhere('nopeserta', $request->nomorkartu)
            ->first();

        $jenis_kelamin = ['L' => 1, 'P' => 2];
        if (empty($pasien)) {
            return ResponseFormatter::error([], 'Data pasien ini tidak ditemukan, silahkan Melakukan Registrasi Pasien Baru', 202);
        }

        $pendaftaran_nama            = $pasien->NAMA;
        $pendaftaran_nomr            = $pasien->NOMR;
        $pendaftaran_provinsi        = $pasien->KDPROVINSI;
        $pendaftaran_kota            = $pasien->KOTA;
        $pendaftaran_kecamatan       = $pasien->KDKECAMATAN;
        $pendaftaran_kelurahan       = $pasien->KELURAHAN;
        $pendaftaran_alamat          = $pasien->ALAMAT;
        $pendaftaran_jenis_kelamin   = in_array($pasien->JENISKELAMIN, ['L', 'P']) ? $jenis_kelamin[$pasien->JENISKELAMIN] : $pasien->JENISKELAMIN;
        $pendaftaran_notelp          = $request->nohp;
        $pendaftaran_tempat          = $pasien->TEMPAT;
        $pendaftaran_tgllahir        = $pasien->TGLLAHIR;
        // END DATA PASIEN

        // BEGIN SIMPAN PENDAFTARAN ONLINE
        $pendaftaran_online                  = new AgPendaftaranOnline;
        $pendaftaran_online->nama            = $pendaftaran_nama;
        $pendaftaran_online->nomr            = $pendaftaran_nomr;
        $pendaftaran_online->provinsi        = $pendaftaran_provinsi;
        $pendaftaran_online->kota            = $pendaftaran_kota;
        $pendaftaran_online->kecamatan       = $pendaftaran_kecamatan;
        $pendaftaran_online->kelurahan       = $pendaftaran_kelurahan;
        $pendaftaran_online->alamat          = $pendaftaran_alamat;
        $pendaftaran_online->jenis_kelamin   = $pendaftaran_jenis_kelamin;
        $pendaftaran_online->kodepoly        = $poli->kode;
        $pendaftaran_online->kodedokter      = $dokter->KDDOKTER;
        $pendaftaran_online->telepon         = $pendaftaran_notelp;
        $pendaftaran_online->tempat          = $pendaftaran_tempat;
        $pendaftaran_online->tanggal_lahir   = $pendaftaran_tgllahir;
        $pendaftaran_online->tanggal_daftar  = date('Y-m-d H:i:s');
        $pendaftaran_online->tanggal_periksa = $request->tanggalperiksa;
        $pendaftaran_online->nik             = $request->nik;
        $pendaftaran_online->cara_bayar      = 10;
        $pendaftaran_online->no_kartu        = $request->nomorkartu;
        $pendaftaran_online->via             = 'mobile_jkn';
        $pendaftaran_online->via_new_app     = 1;
        $pendaftaran_online->versi_baru      = 1;
        $pendaftaran_online->status_hadir    = 0;
        $pendaftaran_online->save();
        // END SIMPAN PENDAFTARAN ONLINE

        // BEGIN SIMPAN PENDAFTARAN ONLINE
        $pendaftaran_online_bpjs = new AgPendaftaranOnlineBpjs();
        $pendaftaran_online_bpjs->id_pendaftaran_online = $pendaftaran_online->id;
        $pendaftaran_online_bpjs->no_kartu              = $pendaftaran_online->no_kartu;
        $pendaftaran_online_bpjs->ppk_pelayanan         = '1133R001';
        $pendaftaran_online_bpjs->kelas_rawat           = 3;
        $pendaftaran_online_bpjs->save();
        // END SIMPAN PENDAFTARAN ONLINE

        $antrean_baru = new Antrean;
        $antrean_baru->kd_poli      = $poli->kode;
        $antrean_baru->kd_dokter    = $dokter->KDDOKTER;
        $antrean_baru->id_online    = $pendaftaran_online->id;
        $antrean_baru->no_urut      = $urut_baru;
        $antrean_baru->tgl          = $request->tanggalperiksa;
        $antrean_baru->estimasi     = date('Y-m-d H:i:s', $estimasi);
        $antrean_baru->kode_booking = $kodebooking;
        $antrean_baru->nomr         = $pendaftaran_online->nomr;
        $antrean_baru->sesi         = $sesi;
        $antrean_baru->save();

        // dispatch(new ProccessReferensi($request->nomorreferensi, $request->jeniskunjungan, $pendaftaran_online->id));
        Log::info('Response : ' . json_encode([
            'nomorantrean'     => $urut_baru,
            'angkaantrean'     => $urut_baru,
            'kodebooking'      => $kodebooking,
            'norm'             => $pasien->NOMR,
            'namapoli'         => $poli->nama,
            'namadokter'       => $dokter->NAMADOKTER,
            'estimasidilayani' => $estimasi * 1000,
            'sisakuotajkn'     => $sisaantrean - 1,
            'kuotajkn'         => $kuota,
            'sisakuotanonjkn'  => $sisaantrean - 1,
            'kuotanonjkn'      => $kuota,
            'keterangan'       => "Peserta harap 60 menit lebih awal guna pencatatan administrasi.",
        ]));

        try {
            $url = 'http://10.0.108.247:8000/api/sync-bpjs?id=' . $pendaftaran_online->id;
            $response = Http::get($url);
        } catch (\Exception $e) {

        }

        return ResponseFormatter::success([
            'nomorantrean'     => $urut_baru,
            'angkaantrean'     => $urut_baru,
            'kodebooking'      => $kodebooking,
            'norm'             => $pasien->NOMR,
            'namapoli'         => $poli->nama,
            'namadokter'       => $dokter->NAMADOKTER,
            'estimasidilayani' => $estimasi * 1000,
            'sisakuotajkn'     => $sisaantrean - 1,
            'kuotajkn'         => $kuota,
            'sisakuotanonjkn'  => $sisaantrean - 1,
            'kuotanonjkn'      => $kuota,
            'keterangan'       => "Peserta harap 60 menit lebih awal guna pencatatan administrasi.",
        ], "Ok", 200);
    }
}
