<?php

namespace App\Controllers;

use App\Models\GuruModel;
use App\Models\IotDeviceModel;
use App\Models\JadwalModel;
use App\Models\OperatorModel;
use App\Models\PresensiModel;
use App\Models\SiswaModel;
use Config\IotDevice;

class Dashboard extends BaseController
{
    public function index()
    {
        $role = (string) session()->get('role');

        $data = [
            'role' => $role,
            'nama' => (string) session()->get('nama'),
            'stats' => [],
            'deviceSummary' => [
                'online' => 0,
                'offline' => 0,
                'total' => 0,
                'rows' => [],
                'window_sec' => 45,
            ],
        ];

        if ($role === 'admin') {
            $data['stats'] = [
                'admin' => (new OperatorModel())->countAllResults(),
                'guru' => (new GuruModel())->countAllResults(),
                'siswa' => (new SiswaModel())->countAllResults(),
                'presensi_hari_ini' => (new PresensiModel())
                    ->where('tanggal', date('Y-m-d'))
                    ->countAllResults(),
            ];
            $data['deviceSummary'] = $this->buildDeviceSummary();
        }

        if ($role === 'guru') {
            $idGuru = (int) session()->get('id_guru');
            $hari = $this->hariIndonesia(date('l'));
            $kelasJadwal = (new JadwalModel())
                ->select('kelas')
                ->where('id_guru', $idGuru)
                ->where('kelas !=', '')
                ->findColumn('kelas') ?? [];
            $kelasWali = (string) session()->get('kelas_wali');
            $kelasGuru = (int) session()->get('is_wali_kelas') === 1
                ? $this->mergeKelasList($kelasJadwal, [$kelasWali])
                : $this->mergeKelasList($kelasJadwal);

            $data['stats'] = [
                'jadwal_hari_ini' => (new JadwalModel())
                    ->where('id_guru', $idGuru)
                    ->where('hari', $hari)
                    ->countAllResults(),
                'presensi_hari_ini' => (new PresensiModel())
                    ->where('id_guru', $idGuru)
                    ->where('tanggal', date('Y-m-d'))
                    ->countAllResults(),
                'kelas_diampu' => count($kelasGuru),
                'is_wali_kelas' => (int) session()->get('is_wali_kelas'),
                'kelas_wali' => $kelasWali,
            ];
        }

        return view('dashboard', $data);
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

    private function buildDeviceSummary(): array
    {
        /** @var IotDevice $cfg */
        $cfg = config('IotDevice');
        $windowSec = max(20, (int) $cfg->deviceOnlineWindowSec);
        $now = time();

        $rows = (new IotDeviceModel())
            ->orderBy('last_seen_at', 'DESC')
            ->findAll();

        $online = 0;
        $offline = 0;
        $decorated = [];

        foreach ($rows as $row) {
            $lastSeenAt = trim((string) ($row['last_seen_at'] ?? ''));
            $lastSeenTs = $lastSeenAt !== '' ? strtotime($lastSeenAt) : false;
            $isOnline = $lastSeenTs !== false && ($now - $lastSeenTs) <= $windowSec;
            $secondsAgo = $lastSeenTs !== false ? max(0, $now - $lastSeenTs) : null;

            if ($isOnline) {
                $online++;
            } else {
                $offline++;
            }

            $decorated[] = [
                'device_code' => (string) ($row['device_code'] ?? '-'),
                'device_name' => (string) ($row['device_name'] ?? ''),
                'status_mode' => (string) ($row['status_mode'] ?? 'attendance'),
                'is_online' => $isOnline,
                'last_seen_at' => $lastSeenAt,
                'last_seen_human' => $secondsAgo === null ? 'Belum pernah heartbeat' : $this->humanizeAgo($secondsAgo),
                'last_ip' => (string) ($row['last_ip'] ?? '-'),
                'last_message' => (string) ($row['last_message'] ?? ''),
            ];
        }

        return [
            'online' => $online,
            'offline' => $offline,
            'total' => count($rows),
            'rows' => $decorated,
            'window_sec' => $windowSec,
        ];
    }

    private function humanizeAgo(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' detik lalu';
        }

        if ($seconds < 3600) {
            return floor($seconds / 60) . ' menit lalu';
        }

        if ($seconds < 86400) {
            return floor($seconds / 3600) . ' jam lalu';
        }

        return floor($seconds / 86400) . ' hari lalu';
    }
}
