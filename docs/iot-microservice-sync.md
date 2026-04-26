# Sinkronisasi Microservice IoT Absensi

Dokumen ini merangkum sinkronisasi antara:
1. CodeIgniter (`D:\TugasAkhir-presensi`)
2. Laravel Gateway (`D:\face recog\laravel-gateway`)
3. FastAPI Face Engine (`D:\face recog\laravel-gateway\fastapi-engine`)
4. Device OrangePi (`iot-device/device_service.py`)

## 1) Konfigurasi token dan URL

### CodeIgniter `.env`
- `faceGateway.baseURL=http://127.0.0.1:8000/api`
- `faceGateway.bearerToken=absensiiot2026-token`
- `iotDevice.deviceToken=orange-pi-zero3-token`
- `iotDevice.deviceOnlineWindowSec=45`
- `iotDevice.registerSessionTimeoutSec=300`
- Saat testing LAN:
  - Device OrangePi: `192.168.0.101`
  - Server aplikasi: `192.168.0.104`

### Laravel Gateway `.env`
- `FACE_GATEWAY_BEARER_TOKEN=absensiiot2026-token`
- `FACE_ENGINE_BASE_URL=http://127.0.0.1:8001`
- `FACE_ENGINE_TOKEN=internal-face-engine-token`

### FastAPI Engine `.env`
- `FACE_ENGINE_TOKEN=internal-face-engine-token`

### Device `.env`
- `DEVICE_CODE=orange-pi-zero3-01`
- `DEVICE_NAME=OrangePi Zero3 #1`
- `IOT_API_URL=http://192.168.0.104:8080/api/iot/scan`
- `IOT_HEALTH_URL=http://192.168.0.104:8080/api/iot/health`
- `IOT_HEARTBEAT_URL=http://192.168.0.104:8080/api/iot/device/heartbeat`
- `IOT_COMMAND_URL=http://192.168.0.104:8080/api/iot/device/command`
- `IOT_REGISTER_CAPTURE_URL=http://192.168.0.104:8080/api/iot/register/capture`
- `IOT_DEVICE_TOKEN=orange-pi-zero3-token`

## 2) Endpoint utama

### CodeIgniter
- `GET /api/iot/health`
- `POST /api/iot/scan` (mendukung `rfid_uid` saja, `image` saja, atau keduanya)
- `POST /api/iot/device/heartbeat`
- `GET /api/iot/device/command`
- `POST /api/iot/register/capture`

### Laravel Gateway
- `POST /api/face/register`
- `POST /api/face/attendance`

### FastAPI
- `GET /health`
- `POST /v1/register`
- `POST /v1/attendance`

## 3) Jalankan service

### CodeIgniter
```bash
cd D:\TugasAkhir-presensi
php spark serve --host=0.0.0.0 --port=8080
```

### Laravel Gateway
```bash
cd "D:\face recog\laravel-gateway"
php artisan serve --host=0.0.0.0 --port=8000
```

### FastAPI Engine
```bash
cd "D:\face recog\laravel-gateway\fastapi-engine"
.venv\Scripts\python.exe -m uvicorn app.main:app --host 0.0.0.0 --port 8001
```

### Device service
```bash
cd D:\TugasAkhir-presensi\iot-device
python device_service.py
```

## 4) Cek kesehatan sinkronisasi

```bash
cd D:\TugasAkhir-presensi
python iot-device/microservice_healthcheck.py
```

## 5) Alur `ON REGIS` end-to-end

1. Admin membuka halaman registrasi dan memilih device online.
2. Klik `ON REGIS`, server membuat session registrasi untuk device.
3. Device polling command endpoint lalu masuk mode register.
4. Device kirim hasil `rfid_uid + image` ke `/api/iot/register/capture`.
5. Halaman admin polling status session lalu autofill data.
6. Admin pilih siswa/guru dan simpan; wajah disinkronkan ke Laravel gateway.
