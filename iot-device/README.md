# OrangePi Zero 3 IoT Client

Service Python ini dipakai untuk 2 mode:
1. Mode absensi harian realtime (`kamera standby + RFID + wajah` ke `/api/iot/scan`)
2. Mode registrasi alat (`ON REGIS` dari web admin)

## Ringkasan wiring dari dokumentasi
Sumber: `Dokumentasi_Absensi_OrangePi_Zero3.pdf`

- LCD I2C 16x2
  - SDA: PH5
  - SCL: PH4
  - Bus: `/dev/i2c-2`
  - Address: `0x27`
- RC522
  - SDA(SS): PH9
  - SCK: PH6
  - MOSI: PH7
  - MISO: PH8
  - RST: PC7
- LED
  - Hijau: PC6
  - Merah: PC5

## Instalasi
```bash
cd iot-device
python -m venv .venv
source .venv/bin/activate  # Linux
pip install -r requirements.txt
cp .env.example .env
```

## Konfigurasi `.env`
Minimal:
- `DEVICE_CODE` (unik tiap alat)
- `IOT_DEVICE_TOKEN`
- `IOT_API_URL`, `IOT_HEALTH_URL`
- `IOT_HEARTBEAT_URL`, `IOT_COMMAND_URL`, `IOT_REGISTER_CAPTURE_URL`

Profil testing saat ini:
- OrangePi (device): `192.168.0.101`
- Server API/microservice: `192.168.0.104`

## Jalankan
```bash
python device_service.py
```

Perilaku utama mode absensi:
- Kamera selalu standby (tidak menunggu ON REGIS).
- Wajib kombinasi `RFID + wajah` (2 faktor):
  - kartu RFID harus valid/terdaftar dulu
  - setelah kartu valid, device mencoba verifikasi wajah sampai maksimal 3 kali
  - jika 3 kali gagal, presensi dinyatakan gagal
- LCD dan LED memberi feedback:
  - sukses: `Selamat Absen` + LED hijau
  - gagal/unknown: `Gagal Absen` + LED merah

Pengaturan metode absensi di `.env`:
- `ATTENDANCE_AUTH_MODE=both` (wajib 2 faktor)
- `FACE_RETRY_MAX_ATTEMPTS=3`
- `RFID_FACE_WAIT_TIMEOUT_SEC=20`

## Mode RFID
- `RFID_MODE=softspi` untuk RC522 software SPI via `gpiod` (disarankan untuk OrangePi)
- `RFID_MODE=serial` untuk reader serial (`RFID_SERIAL_PORT`)
- `RFID_MODE=rc522` untuk RC522 via GPIO
- `RFID_MODE=stdin` untuk testing manual

## Alur mode registrasi (ON REGIS)
1. Admin pilih alat di halaman registrasi web, klik `ON REGIS`.
2. Device polling endpoint command, masuk mode registrasi.
3. Device ambil RFID + foto wajah, kirim ke `/api/iot/register/capture`.
4. Halaman admin polling status session lalu autofill RFID + preview wajah.
5. Admin pilih siswa/guru lalu simpan.

## Cek sinkronisasi microservice
```bash
python microservice_healthcheck.py
```
