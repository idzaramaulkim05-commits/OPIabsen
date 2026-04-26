<?php

namespace App\Controllers;

use App\Models\JadwalModel;
use App\Models\PresensiModel;
use App\Models\SiswaModel;

class Presensi extends BaseController
{
    public function index()
    {
        $role = (string) session()->get('role');
        $idGuru = (int) session()->get('id_guru');
        $hariIni = $this->hariIndonesia(date('l'));
        $jamSekarang = date('H:i:s');

        $jadwalAktif = [];
        if ($role === 'guru') {
            $jadwalAktif = (new JadwalModel())
                ->where('id_guru', $idGuru)
                ->where('hari', $hariIni)
                ->where('jam_mulai <=', $jamSekarang)
                ->where('jam_selesai >=', $jamSekarang)
                ->orderBy('jam_mulai', 'ASC')
                ->findAll();
        }

        $jadwalDipilih = null;
        $siswa = [];
        $presensiMap = [];

        $requestedJadwal = (int) $this->request->getGet('jadwal');
        if ($requestedJadwal > 0) {
            foreach ($jadwalAktif as $j) {
                if ((int) $j['id_jadwal'] === $requestedJadwal) {
                    $jadwalDipilih = $j;
                    break;
                }
            }
        }

        if ($jadwalDipilih === null && $jadwalAktif !== []) {
            $jadwalDipilih = $jadwalAktif[0];
        }

        if ($jadwalDipilih) {
            $siswaModel = new SiswaModel();
            $siswa = $siswaModel->where('kelas', $jadwalDipilih['kelas'])->orderBy('nama', 'ASC')->findAll();

            $presensiRows = (new PresensiModel())
                ->where('id_jadwal', $jadwalDipilih['id_jadwal'])
                ->where('tanggal', date('Y-m-d'))
                ->findAll();

            foreach ($presensiRows as $row) {
                $presensiMap[(int) $row['id_siswa']] = $row;
            }
        }

        return view('presensi', [
            'hariIni' => $hariIni,
            'jamSekarang' => $jamSekarang,
            'jadwalAktif' => $jadwalAktif,
            'jadwalDipilih' => $jadwalDipilih,
            'siswa' => $siswa,
            'presensiMap' => $presensiMap,
        ]);
    }

    public function simpan()
    {
        $idGuru = (int) session()->get('id_guru');
        $idJadwal = (int) $this->request->getPost('id_jadwal');
        $idSiswa = (int) $this->request->getPost('id_siswa');
        $status = trim((string) $this->request->getPost('status'));
        $catatan = trim((string) $this->request->getPost('catatan')) ?: null;

        if (! in_array($status, ['hadir', 'izin', 'sakit', 'alpa'], true)) {
            return redirect()->back()->with('error', 'Status presensi tidak valid.');
        }

        $jadwal = $this->jadwalAktifById($idGuru, $idJadwal);
        if (! $jadwal) {
            return redirect()->back()->with('error', 'Presensi hanya dapat diinput saat jadwal mengajar aktif.');
        }

        $siswa = (new SiswaModel())->find($idSiswa);
        if (! $siswa || $siswa['kelas'] !== $jadwal['kelas']) {
            return redirect()->back()->with('error', 'Siswa tidak sesuai dengan kelas pada jadwal aktif.');
        }

        $model = new PresensiModel();
        $tanggal = date('Y-m-d');
        $existing = $model
            ->where('id_siswa', $idSiswa)
            ->where('id_jadwal', $idJadwal)
            ->where('tanggal', $tanggal)
            ->first();

        $payload = [
            'id_siswa' => $idSiswa,
            'id_guru' => $idGuru,
            'id_jadwal' => $idJadwal,
            'kelas' => $jadwal['kelas'],
            'tanggal' => $tanggal,
            'jam' => date('H:i:s'),
            'status' => $status,
            'metode' => 'manual',
            'catatan' => $catatan,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $model->update($existing['id_presensi'], $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $model->insert($payload);
        }

        return redirect()->to('/presensi?jadwal=' . $idJadwal)->with('success', 'Presensi berhasil disimpan.');
    }

    public function riwayat()
    {
        $role = (string) session()->get('role');
        $mulai = (string) ($this->request->getGet('mulai') ?: date('Y-m-01'));
        $akhir = (string) ($this->request->getGet('akhir') ?: date('Y-m-d'));
        $kelasFilter = trim((string) $this->request->getGet('kelas'));

        $builder = db_connect()->table('presensi p')
            ->select('p.*, s.nama as nama_siswa, s.no_induk, g.nama as nama_guru')
            ->join('siswa s', 's.id = p.id_siswa')
            ->join('guru g', 'g.id_guru = p.id_guru')
            ->where('p.tanggal >=', $mulai)
            ->where('p.tanggal <=', $akhir);

        if ($role === 'guru') {
            if ((int) session()->get('is_wali_kelas') !== 1) {
                return redirect()->to('/dashboard')->with('error', 'Hanya guru wali kelas yang dapat melihat laporan presensi.');
            }

            $kelasWali = (string) session()->get('kelas_wali');
            $builder->where('p.kelas', $kelasWali);
            $kelasFilter = $kelasWali;
        }

        if ($role === 'admin' && $kelasFilter !== '') {
            $builder->where('p.kelas', $kelasFilter);
        }

        $rows = $builder
            ->orderBy('p.tanggal', 'DESC')
            ->orderBy('p.kelas', 'ASC')
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();

        return view('laporan_presensi', [
            'rows' => $rows,
            'mulai' => $mulai,
            'akhir' => $akhir,
            'kelasFilter' => $kelasFilter,
            'role' => $role,
        ]);
    }

    public function cetak()
    {
        $role = (string) session()->get('role');
        $mulai = (string) ($this->request->getGet('mulai') ?: date('Y-m-01'));
        $akhir = (string) ($this->request->getGet('akhir') ?: date('Y-m-d'));
        $kelasFilter = trim((string) $this->request->getGet('kelas'));

        $builder = db_connect()->table('presensi p')
            ->select('p.*, s.nama as nama_siswa, s.no_induk, g.nama as nama_guru')
            ->join('siswa s', 's.id = p.id_siswa')
            ->join('guru g', 'g.id_guru = p.id_guru')
            ->where('p.tanggal >=', $mulai)
            ->where('p.tanggal <=', $akhir);

        if ($role === 'guru') {
            if ((int) session()->get('is_wali_kelas') !== 1) {
                return redirect()->to('/dashboard')->with('error', 'Hanya guru wali kelas yang dapat mencetak laporan.');
            }

            $kelasWali = (string) session()->get('kelas_wali');
            $builder->where('p.kelas', $kelasWali);
            $kelasFilter = $kelasWali;
        }

        if ($role === 'admin' && $kelasFilter !== '') {
            $builder->where('p.kelas', $kelasFilter);
        }

        $rows = $builder
            ->orderBy('p.tanggal', 'DESC')
            ->orderBy('p.kelas', 'ASC')
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();

        return view('laporan_presensi_cetak', [
            'rows' => $rows,
            'mulai' => $mulai,
            'akhir' => $akhir,
            'kelasFilter' => $kelasFilter,
        ]);
    }

    private function jadwalAktifById(int $idGuru, int $idJadwal): ?array
    {
        $hariIni = $this->hariIndonesia(date('l'));
        $jamSekarang = date('H:i:s');

        $jadwal = (new JadwalModel())
            ->where('id_jadwal', $idJadwal)
            ->where('id_guru', $idGuru)
            ->where('hari', $hariIni)
            ->where('jam_mulai <=', $jamSekarang)
            ->where('jam_selesai >=', $jamSekarang)
            ->first();

        return $jadwal ?: null;
    }

    private function hariIndonesia(string $englishDay): string
    {
        $map = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        return $map[$englishDay] ?? $englishDay;
    }
}
