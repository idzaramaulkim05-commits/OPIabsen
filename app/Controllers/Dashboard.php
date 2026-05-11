<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;
use App\Models\OperatorModel;
use Config\IotDevice;

class Dashboard extends BaseController
{
    private $client;

    public function __construct()
    {
        $this->client = new LaravelApiClient();
    }

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
            $guruList = $this->safeList($this->client->get('guru'));
            $siswaList = $this->safeList($this->client->get('siswa'));
            $presensiList = $this->safeList($this->client->get('presensi'));

            $data['stats'] = [
                'admin' => (new OperatorModel())->countAllResults(),
                'guru' => count($guruList),
                'siswa' => count($siswaList),
                'presensi_hari_ini' => $this->countPresensiByDate($presensiList, date('Y-m-d')),
            ];
            $data['deviceSummary'] = $this->buildDeviceSummary();
        }

        if ($role === 'guru') {
            $kelasWali = (string) session()->get('kelas_wali');
            $hari = $this->hariIndonesia(date('l'));
            $idGuru = (int) session()->get('id_guru');

            $presensiList = $this->safeList($this->client->get('presensi'));

            $data['stats'] = [
                'jadwal_hari_ini' => $this->countJadwalHariIni($hari, $idGuru, $kelasWali),
                'presensi_hari_ini' => $this->countPresensiByDateAndKelas($presensiList, date('Y-m-d'), $kelasWali),
                'kelas_diampu' => $kelasWali !== '' ? 1 : 0,
                'is_wali_kelas' => $kelasWali !== '' ? 1 : 0,
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

        $rows = $this->safeList($this->client->get('iot-admin/devices'));

        $online = 0;
        $offline = 0;
        $decorated = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $isOnline = (bool) ($row['is_online'] ?? false);
            $online += $isOnline ? 1 : 0;
            $offline += $isOnline ? 0 : 1;

            $decorated[] = [
                'device_code' => (string) ($row['device_code'] ?? '-'),
                'device_name' => (string) ($row['device_name'] ?? ''),
                'status_mode' => (string) ($row['status_mode'] ?? 'attendance'),
                'is_online' => $isOnline,
                'last_seen_at' => (string) ($row['last_seen_at'] ?? ''),
                'last_seen_human' => (string) ($row['last_seen_human'] ?? '-'),
                'last_ip' => (string) ($row['last_ip'] ?? '-'),
                'last_message' => (string) ($row['last_message'] ?? ''),
            ];
        }

        return [
            'online' => $online,
            'offline' => $offline,
            'total' => count($decorated),
            'rows' => $decorated,
            'window_sec' => $windowSec,
        ];
    }

    private function countPresensiByDate(array $presensi, string $tanggal): int
    {
        $count = 0;

        foreach ($presensi as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ($this->extractPresensiDate($item) === $tanggal) {
                $count++;
            }
        }

        return $count;
    }

    private function countPresensiByDateAndKelas(array $presensi, string $tanggal, string $kelasWali): int
    {
        if ($kelasWali === '') {
            return 0;
        }

        $count = 0;

        foreach ($presensi as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ($this->extractPresensiDate($item) !== $tanggal) {
                continue;
            }

            if ($this->resolvePresensiKelas($item) === $kelasWali) {
                $count++;
            }
        }

        return $count;
    }

    private function extractPresensiDate(array $item): string
    {
        $tanggal = trim((string) ($item['tanggal'] ?? ''));
        if ($this->validDate($tanggal)) {
            return $tanggal;
        }

        $createdAt = (string) ($item['created_at'] ?? '');
        if (strlen($createdAt) >= 10) {
            $candidate = substr($createdAt, 0, 10);
            if ($this->validDate($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private function resolvePresensiKelas(array $item): string
    {
        $kelas = trim((string) ($item['kelas'] ?? ''));
        if ($kelas !== '') {
            return $kelas;
        }

        $siswa = is_array($item['siswa'] ?? null) ? $item['siswa'] : [];
        return trim((string) ($siswa['kelas'] ?? ''));
    }

    private function countJadwalHariIni(string $hari, int $idGuru, string $kelasWali): int
    {
        $jadwalList = $this->safeList($this->client->get('jadwal'));
        $count = 0;

        foreach ($jadwalList as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ((string) ($item['hari'] ?? '') !== $hari) {
                continue;
            }

            $match = false;
            if ($idGuru > 0 && isset($item['id_guru']) && (int) $item['id_guru'] === $idGuru) {
                $match = true;
            }
            if (! $match && $kelasWali !== '' && isset($item['kelas']) && (string) $item['kelas'] === $kelasWali) {
                $match = true;
            }

            if ($match) {
                $count++;
            }
        }

        return $count;
    }

    private function safeList($response): array
    {
        if (! is_array($response)) {
            return [];
        }

        if (array_key_exists('message', $response)) {
            return [];
        }

        return array_values($response);
    }

    private function validDate(string $date): bool
    {
        $date = trim($date);
        if ($date === '') {
            return false;
        }

        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }
}
