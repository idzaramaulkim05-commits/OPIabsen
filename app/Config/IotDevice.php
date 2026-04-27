<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class IotDevice extends BaseConfig
{
    /**
     * Token rahasia yang wajib dikirim device di header X-Device-Token.
     */
    public string $deviceToken = '';

    /**
     * Jika true, scan siswa yang lolos verifikasi akan otomatis dicatat ke tabel presensi
     * berdasarkan jadwal aktif di kelas siswa.
     */
    public bool $autoRecordSiswaAttendance = true;

    /**
     * Jika true, mode absensi wajib kombinasi RFID + wajah.
     * Request scan hanya RFID diperlakukan sebagai precheck kartu (tanpa simpan presensi).
     */
    public bool $requireDualFactorAttendance = true;

    /**
     * Batas waktu (detik) device dianggap online sejak heartbeat terakhir.
     */
    public int $deviceOnlineWindowSec = 45;

    /**
     * Batas waktu (detik) sesi registrasi device menunggu capture sebelum expire.
     */
    public int $registerSessionTimeoutSec = 300;
}
