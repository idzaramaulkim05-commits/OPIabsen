#!/usr/bin/env python3
"""OrangePi Zero 3 IoT client with realtime camera standby.

Attendance mode:
- Kamera selalu aktif standby.
- Saat wajah terdeteksi, device langsung mencoba proses absen.
- Status ditampilkan ke LCD + LED:
  - Selamat absen (hijau)
  - Gagal/unknown (merah)

Register mode:
- Aktif hanya saat ada perintah ON REGIS dari web.
- Device menunggu RFID + wajah, lalu kirim capture registrasi ke backend.
"""

from __future__ import annotations

import os
import select
import sys
import time
from dataclasses import dataclass
from typing import Optional

import cv2
import requests

try:
    from dotenv import load_dotenv
except Exception:  # pragma: no cover
    load_dotenv = None


def load_env_file_fallback(path: str = ".env") -> None:
    if not os.path.exists(path):
        return

    with open(path, "r", encoding="utf-8") as fh:
        for line in fh:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, value = line.split("=", 1)
            key = key.strip()
            value = value.strip().strip("\"' ")
            if key and key not in os.environ:
                os.environ[key] = value


def env_bool(name: str, default: bool = False) -> bool:
    raw = os.getenv(name)
    if raw is None:
        return default
    return raw.strip().lower() in {"1", "true", "yes", "on"}


def env_int(name: str, default: int) -> int:
    raw = os.getenv(name)
    if raw is None or raw.strip() == "":
        return default
    try:
        return int(raw)
    except ValueError:
        return default


def env_float(name: str, default: float) -> float:
    raw = os.getenv(name)
    if raw is None or raw.strip() == "":
        return default
    try:
        return float(raw)
    except ValueError:
        return default


def trim16(text: str) -> str:
    return (text or "")[:16]


@dataclass
class DeviceConfig:
    device_code: str
    device_name: str
    firmware_version: str
    iot_api_url: str
    iot_health_url: str
    iot_heartbeat_url: str
    iot_command_url: str
    iot_register_capture_url: str
    device_token: str
    timeout_sec: int
    heartbeat_interval_sec: float
    command_poll_interval_sec: float

    camera_index: int
    camera_width: int
    camera_height: int
    camera_warmup_sec: float
    camera_use_v4l2: bool
    jpeg_quality: int

    face_detect_interval_sec: float
    face_event_cooldown_sec: float
    face_min_size: int
    attendance_auth_mode: str
    attendance_attempt_cooldown_sec: float
    face_retry_max_attempts: int
    rfid_face_wait_timeout_sec: float

    rfid_mode: str
    rfid_serial_port: str
    rfid_serial_baudrate: int
    rfid_cache_ttl_sec: float
    consume_rfid_after_attempt: bool

    softspi_gpio_chip: str
    softspi_led_red: int
    softspi_led_green: int
    softspi_rst: int
    softspi_sck: int
    softspi_mosi: int
    softspi_miso: int
    softspi_cs: int

    lcd_enabled: bool
    lcd_i2c_port: int
    lcd_i2c_address: int

    led_enabled: bool
    led_backend: str
    led_green_pin: str
    led_red_pin: str
    led_gpiod_chip: str
    led_gpiod_green: int
    led_gpiod_red: int

    standby_text_interval_sec: float
    camera_error_repeat_sec: float

    @staticmethod
    def from_env() -> "DeviceConfig":
        if load_dotenv is not None:
            load_dotenv()
        else:
            load_env_file_fallback(".env")

        lcd_addr_raw = os.getenv("LCD_I2C_ADDRESS", "0x27")
        try:
            lcd_addr = int(lcd_addr_raw, 0)
        except Exception:
            lcd_addr = 0x27

        return DeviceConfig(
            device_code=os.getenv("DEVICE_CODE", "orange-pi-zero3-01"),
            device_name=os.getenv("DEVICE_NAME", "OrangePi Zero3 #1"),
            firmware_version=os.getenv("DEVICE_FIRMWARE_VERSION", "iot-device-1.2.0"),
            iot_api_url=os.getenv("IOT_API_URL", "http://192.168.0.104:8080/api/iot/scan"),
            iot_health_url=os.getenv("IOT_HEALTH_URL", "http://192.168.0.104:8080/api/iot/health"),
            iot_heartbeat_url=os.getenv("IOT_HEARTBEAT_URL", "http://192.168.0.104:8080/api/iot/device/heartbeat"),
            iot_command_url=os.getenv("IOT_COMMAND_URL", "http://192.168.0.104:8080/api/iot/device/command"),
            iot_register_capture_url=os.getenv(
                "IOT_REGISTER_CAPTURE_URL",
                "http://192.168.0.104:8080/api/iot/register/capture",
            ),
            device_token=os.getenv("IOT_DEVICE_TOKEN", ""),
            timeout_sec=env_int("IOT_TIMEOUT", 15),
            heartbeat_interval_sec=env_float("HEARTBEAT_INTERVAL_SEC", 8.0),
            command_poll_interval_sec=env_float("COMMAND_POLL_INTERVAL_SEC", 2.0),
            camera_index=env_int("CAMERA_INDEX", 0),
            camera_width=env_int("CAMERA_WIDTH", 640),
            camera_height=env_int("CAMERA_HEIGHT", 480),
            camera_warmup_sec=env_float("CAMERA_WARMUP_SEC", 0.4),
            camera_use_v4l2=env_bool("CAMERA_USE_V4L2", True),
            jpeg_quality=env_int("JPEG_QUALITY", 90),
            face_detect_interval_sec=env_float("FACE_DETECT_INTERVAL_SEC", 0.15),
            face_event_cooldown_sec=env_float("FACE_EVENT_COOLDOWN_SEC", 3.0),
            face_min_size=env_int("FACE_MIN_SIZE", 60),
            attendance_auth_mode=os.getenv("ATTENDANCE_AUTH_MODE", "both").strip().lower(),
            attendance_attempt_cooldown_sec=env_float("ATTENDANCE_ATTEMPT_COOLDOWN_SEC", 2.5),
            face_retry_max_attempts=max(1, env_int("FACE_RETRY_MAX_ATTEMPTS", 3)),
            rfid_face_wait_timeout_sec=max(3.0, env_float("RFID_FACE_WAIT_TIMEOUT_SEC", 20.0)),
            rfid_mode=os.getenv("RFID_MODE", "softspi").strip().lower(),
            rfid_serial_port=os.getenv("RFID_SERIAL_PORT", "/dev/ttyUSB0"),
            rfid_serial_baudrate=env_int("RFID_SERIAL_BAUDRATE", 9600),
            rfid_cache_ttl_sec=env_float("RFID_CACHE_TTL_SEC", 8.0),
            consume_rfid_after_attempt=env_bool("CONSUME_RFID_AFTER_ATTEMPT", True),
            softspi_gpio_chip=os.getenv("RFID_SOFTSPI_GPIO_CHIP", "/dev/gpiochip1"),
            softspi_led_red=env_int("RFID_SOFTSPI_LED_RED", 69),
            softspi_led_green=env_int("RFID_SOFTSPI_LED_GREEN", 70),
            softspi_rst=env_int("RFID_SOFTSPI_RST", 71),
            softspi_sck=env_int("RFID_SOFTSPI_SCK", 230),
            softspi_mosi=env_int("RFID_SOFTSPI_MOSI", 231),
            softspi_miso=env_int("RFID_SOFTSPI_MISO", 232),
            softspi_cs=env_int("RFID_SOFTSPI_CS", 233),
            lcd_enabled=env_bool("LCD_ENABLED", False),
            lcd_i2c_port=env_int("LCD_I2C_PORT", 2),
            lcd_i2c_address=lcd_addr,
            led_enabled=env_bool("LED_ENABLED", False),
            led_backend=os.getenv("LED_BACKEND", "gpiod").strip().lower(),
            led_green_pin=os.getenv("LED_GREEN_PIN", "PC6"),
            led_red_pin=os.getenv("LED_RED_PIN", "PC5"),
            led_gpiod_chip=os.getenv("LED_GPIOD_CHIP", "/dev/gpiochip1"),
            led_gpiod_green=env_int("LED_GPIOD_GREEN", 70),
            led_gpiod_red=env_int("LED_GPIOD_RED", 69),
            standby_text_interval_sec=env_float("STANDBY_TEXT_INTERVAL_SEC", 5.0),
            camera_error_repeat_sec=env_float("CAMERA_ERROR_REPEAT_SEC", 3.0),
        )


class RfidReader:
    def read_uid(self, timeout_sec: Optional[float] = None) -> Optional[str]:
        raise NotImplementedError

    def supports_timeout(self) -> bool:
        return False

    def close(self) -> None:
        return None


class StdinRfidReader(RfidReader):
    def read_uid(self, timeout_sec: Optional[float] = None) -> Optional[str]:
        timeout = None if timeout_sec is None else max(0.0, float(timeout_sec))
        ready, _, _ = select.select([sys.stdin], [], [], timeout)
        if not ready:
            return None

        line = sys.stdin.readline().strip()
        return line or None

    def supports_timeout(self) -> bool:
        return True


class SerialRfidReader(RfidReader):
    def __init__(self, port: str, baudrate: int) -> None:
        try:
            import serial
        except Exception as exc:  # pragma: no cover
            raise RuntimeError("pyserial belum terpasang untuk mode serial.") from exc

        self._serial = serial.Serial(port=port, baudrate=baudrate, timeout=0.12)

    def read_uid(self, timeout_sec: Optional[float] = None) -> Optional[str]:
        deadline = None if timeout_sec is None else time.time() + max(0.0, float(timeout_sec))
        while True:
            raw = self._serial.readline().decode(errors="ignore").strip()
            if raw:
                return raw
            if deadline is not None and time.time() >= deadline:
                return None

    def supports_timeout(self) -> bool:
        return True

    def close(self) -> None:
        if self._serial and self._serial.is_open:
            self._serial.close()


class Rc522RfidReader(RfidReader):
    def __init__(self) -> None:
        try:
            from mfrc522 import SimpleMFRC522
        except Exception as exc:  # pragma: no cover
            raise RuntimeError("Library mfrc522 belum terpasang untuk mode rc522.") from exc

        self._reader = SimpleMFRC522()
        self._supports_non_block = hasattr(self._reader, "read_id_no_block")
        self._gpio = None

        try:
            import OPi.GPIO as GPIO

            self._gpio = GPIO
        except Exception:
            self._gpio = None

    def read_uid(self, timeout_sec: Optional[float] = None) -> Optional[str]:
        if self._supports_non_block:
            deadline = None if timeout_sec is None else time.time() + max(0.0, float(timeout_sec))
            while True:
                uid = self._reader.read_id_no_block()
                if uid:
                    return str(uid)
                if deadline is not None and time.time() >= deadline:
                    return None
                time.sleep(0.06)

        if timeout_sec is not None:
            return None

        uid, _ = self._reader.read()
        return str(uid)

    def supports_timeout(self) -> bool:
        return self._supports_non_block

    def close(self) -> None:
        if self._gpio is not None:
            try:
                self._gpio.cleanup()
            except Exception:
                pass


class SoftSpiRc522RfidReader(RfidReader):
    # Register constants
    COMMAND_REG = 0x01
    COM_IRQ_REG = 0x04
    ERROR_REG = 0x06
    FIFO_DATA_REG = 0x09
    FIFO_LEVEL_REG = 0x0A
    BIT_FRAMING_REG = 0x0D
    MODE_REG = 0x11
    TX_CONTROL_REG = 0x14
    TX_AUTO_REG = 0x15
    T_MODE_REG = 0x2A
    T_PRESCALER_REG = 0x2B
    T_RELOAD_REG_H = 0x2C
    T_RELOAD_REG_L = 0x2D

    PCD_TRANSCEIVE = 0x0C
    PCD_RESETPHASE = 0x0F
    PICC_REQIDL = 0x26
    PICC_ANTICOLL = 0x93

    def __init__(self, cfg: DeviceConfig) -> None:
        try:
            import gpiod
            from gpiod import LineSettings
            from gpiod.line import Direction, Value
        except Exception as exc:  # pragma: no cover
            raise RuntimeError("Library gpiod belum terpasang untuk mode softspi.") from exc

        self._gpiod = gpiod
        self._line_settings_cls = LineSettings
        self._direction = Direction
        self._value = Value

        self.chip = cfg.softspi_gpio_chip
        self.rst = cfg.softspi_rst
        self.sck = cfg.softspi_sck
        self.mosi = cfg.softspi_mosi
        self.miso = cfg.softspi_miso
        self.cs = cfg.softspi_cs

        self._req = self._gpiod.request_lines(
            self.chip,
            consumer="smartpresence_softspi",
            config={
                self.rst: self._line_settings_cls(direction=self._direction.OUTPUT),
                self.sck: self._line_settings_cls(direction=self._direction.OUTPUT),
                self.mosi: self._line_settings_cls(direction=self._direction.OUTPUT),
                self.cs: self._line_settings_cls(direction=self._direction.OUTPUT),
                self.miso: self._line_settings_cls(direction=self._direction.INPUT),
            },
        )

        self._set_pin(self.cs, 1)
        self._set_pin(self.sck, 0)
        self._rfid_init()

    def _set_pin(self, pin: int, value: int) -> None:
        self._req.set_value(pin, self._value.ACTIVE if value else self._value.INACTIVE)

    def _get_pin(self, pin: int) -> bool:
        return self._req.get_value(pin) == self._value.ACTIVE

    def _spi_transfer_byte(self, byte: int) -> int:
        result = 0
        for _ in range(8):
            self._set_pin(self.mosi, 1 if (byte & 0x80) else 0)
            self._set_pin(self.sck, 1)
            time.sleep(0.00001)

            result <<= 1
            if self._get_pin(self.miso):
                result |= 1

            self._set_pin(self.sck, 0)
            time.sleep(0.00001)
            byte <<= 1

        return result

    def _spi_transfer(self, data: list[int]) -> list[int]:
        self._set_pin(self.cs, 0)
        result = [self._spi_transfer_byte(b & 0xFF) for b in data]
        self._set_pin(self.cs, 1)
        return result

    def _write_reg(self, reg: int, value: int) -> None:
        self._spi_transfer([((reg << 1) & 0x7E), value & 0xFF])

    def _read_reg(self, reg: int) -> int:
        return self._spi_transfer([((reg << 1) & 0x7E) | 0x80, 0])[1]

    def _set_bit_mask(self, reg: int, mask: int) -> None:
        self._write_reg(reg, self._read_reg(reg) | mask)

    def _clear_bit_mask(self, reg: int, mask: int) -> None:
        self._write_reg(reg, self._read_reg(reg) & (~mask))

    def _rfid_init(self) -> None:
        self._set_pin(self.rst, 0)
        time.sleep(0.05)
        self._set_pin(self.rst, 1)
        time.sleep(0.05)

        self._write_reg(self.COMMAND_REG, self.PCD_RESETPHASE)
        time.sleep(0.05)
        self._write_reg(self.T_MODE_REG, 0x8D)
        self._write_reg(self.T_PRESCALER_REG, 0x3E)
        self._write_reg(self.T_RELOAD_REG_L, 30)
        self._write_reg(self.T_RELOAD_REG_H, 0)
        self._write_reg(self.TX_AUTO_REG, 0x40)
        self._write_reg(self.MODE_REG, 0x3D)
        self._set_bit_mask(self.TX_CONTROL_REG, 0x03)

    def _to_card(self, command: int, send_data: list[int]) -> tuple[bool, list[int]]:
        back_data: list[int] = []
        self._write_reg(self.COM_IRQ_REG, 0x7F)
        self._set_bit_mask(self.FIFO_LEVEL_REG, 0x80)

        for data in send_data:
            self._write_reg(self.FIFO_DATA_REG, data)

        self._write_reg(self.COMMAND_REG, command)
        if command == self.PCD_TRANSCEIVE:
            self._set_bit_mask(self.BIT_FRAMING_REG, 0x80)

        i = 2000
        while True:
            irq = self._read_reg(self.COM_IRQ_REG)
            i -= 1
            if not (i != 0 and not (irq & 0x01) and not (irq & 0x30)):
                break

        self._clear_bit_mask(self.BIT_FRAMING_REG, 0x80)

        if i != 0 and (self._read_reg(self.ERROR_REG) & 0x1B) == 0:
            fifo_len = self._read_reg(self.FIFO_LEVEL_REG)
            for _ in range(fifo_len):
                back_data.append(self._read_reg(self.FIFO_DATA_REG))
            return True, back_data

        return False, back_data

    def _request_card(self) -> bool:
        self._write_reg(self.BIT_FRAMING_REG, 0x07)
        status, _ = self._to_card(self.PCD_TRANSCEIVE, [self.PICC_REQIDL])
        return status

    def _read_uid_raw(self) -> Optional[str]:
        self._write_reg(self.BIT_FRAMING_REG, 0x00)
        status, back_data = self._to_card(self.PCD_TRANSCEIVE, [self.PICC_ANTICOLL, 0x20])

        if status and len(back_data) >= 5:
            uid = back_data[:4]
            bcc = back_data[4]
            if (uid[0] ^ uid[1] ^ uid[2] ^ uid[3]) != bcc:
                return None
            return "-".join(f"{x:02X}" for x in uid)

        return None

    def read_uid(self, timeout_sec: Optional[float] = None) -> Optional[str]:
        deadline = None if timeout_sec is None else time.time() + max(0.0, float(timeout_sec))
        while True:
            if self._request_card():
                uid = self._read_uid_raw()
                if uid:
                    return uid

            if deadline is not None and time.time() >= deadline:
                return None
            time.sleep(0.02)

    def supports_timeout(self) -> bool:
        return True

    def close(self) -> None:
        if self._req is not None:
            try:
                self._req.release()
            except Exception:
                pass


class Camera:
    def __init__(self, cfg: DeviceConfig) -> None:
        self.index = cfg.camera_index
        self.width = cfg.camera_width
        self.height = cfg.camera_height
        self.warmup_sec = cfg.camera_warmup_sec
        self.use_v4l2 = cfg.camera_use_v4l2
        self.cap: Optional[cv2.VideoCapture] = None

    def open(self) -> None:
        if self.use_v4l2:
            self.cap = cv2.VideoCapture(self.index, cv2.CAP_V4L2)
            if not self.cap or not self.cap.isOpened():
                self.cap = cv2.VideoCapture(self.index)
        else:
            self.cap = cv2.VideoCapture(self.index)

        if not self.cap or not self.cap.isOpened():
            raise RuntimeError(f"Kamera index {self.index} tidak bisa dibuka.")

        self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, float(self.width))
        self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, float(self.height))

        end_at = time.time() + self.warmup_sec
        while time.time() < end_at:
            self.cap.read()

    def read_frame(self) -> Optional[object]:
        if self.cap is None:
            return None

        ok, frame = self.cap.read()
        if not ok:
            return None
        return frame

    @staticmethod
    def encode_jpeg(frame: object, quality: int) -> bytes:
        ok, encoded = cv2.imencode(".jpg", frame, [int(cv2.IMWRITE_JPEG_QUALITY), int(quality)])
        if not ok:
            raise RuntimeError("Gagal encode frame ke JPEG.")
        return encoded.tobytes()

    def close(self) -> None:
        if self.cap is not None:
            self.cap.release()


class FaceDetector:
    def __init__(self, min_size: int = 60) -> None:
        self._min_size = max(24, int(min_size))
        self._cascade = cv2.CascadeClassifier(
            cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
        )
        if self._cascade.empty():
            raise RuntimeError("Gagal memuat model deteksi wajah.")

    def has_face(self, frame: object) -> bool:
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        faces = self._cascade.detectMultiScale(
            gray,
            scaleFactor=1.2,
            minNeighbors=5,
            minSize=(self._min_size, self._min_size),
        )
        return len(faces) > 0


class StatusDisplay:
    def __init__(self, cfg: DeviceConfig) -> None:
        self.enabled = cfg.lcd_enabled
        self._lcd = None
        self._cols = 16

        if not self.enabled:
            return

        try:
            from RPLCD.i2c import CharLCD

            self._lcd = CharLCD(
                i2c_expander="PCF8574",
                port=cfg.lcd_i2c_port,
                address=cfg.lcd_i2c_address,
                cols=16,
                rows=2,
                auto_linebreaks=False,
                charmap="A02",
            )
        except Exception as exc:
            self.enabled = False
            print(f"[WARN] LCD nonaktif: {exc}")

    def show(self, line1: str, line2: str = "") -> None:
        line1 = trim16(line1)
        line2 = trim16(line2)
        print(f"[LCD] {line1} | {line2}")

        if not self.enabled or self._lcd is None:
            return

        self._lcd.clear()
        self._lcd.cursor_pos = (0, 0)
        self._lcd.write_string(line1.ljust(self._cols))
        self._lcd.cursor_pos = (1, 0)
        self._lcd.write_string(line2.ljust(self._cols))

    def close(self) -> None:
        if self._lcd is not None:
            try:
                self._lcd.clear()
            except Exception:
                pass


class LedIndicatorBase:
    def success(self) -> None:
        return None

    def error(self) -> None:
        return None

    def info(self) -> None:
        return None

    def close(self) -> None:
        return None


class OpiLedIndicator(LedIndicatorBase):
    def __init__(self, cfg: DeviceConfig) -> None:
        self.enabled = False
        self._gpio = None
        self.green_pin = cfg.led_green_pin
        self.red_pin = cfg.led_red_pin

        try:
            import OPi.GPIO as GPIO

            GPIO.setwarnings(False)
            GPIO.setmode(GPIO.SUNXI)
            GPIO.setup(self.green_pin, GPIO.OUT)
            GPIO.setup(self.red_pin, GPIO.OUT)
            GPIO.output(self.green_pin, GPIO.LOW)
            GPIO.output(self.red_pin, GPIO.LOW)
            self._gpio = GPIO
            self.enabled = True
        except Exception as exc:
            print(f"[WARN] LED OPi nonaktif: {exc}")

    def _pulse(self, pin: str, duration: float) -> None:
        if not self.enabled or self._gpio is None:
            return
        self._gpio.output(self.green_pin, self._gpio.LOW)
        self._gpio.output(self.red_pin, self._gpio.LOW)
        self._gpio.output(pin, self._gpio.HIGH)
        time.sleep(duration)
        self._gpio.output(pin, self._gpio.LOW)

    def success(self) -> None:
        print("[LED] success")
        self._pulse(self.green_pin, 0.5)

    def error(self) -> None:
        print("[LED] error")
        self._pulse(self.red_pin, 0.6)

    def info(self) -> None:
        print("[LED] info")
        self._pulse(self.green_pin, 0.15)

    def close(self) -> None:
        if self.enabled and self._gpio is not None:
            self._gpio.output(self.green_pin, self._gpio.LOW)
            self._gpio.output(self.red_pin, self._gpio.LOW)
            self._gpio.cleanup()


class GpiodLedIndicator(LedIndicatorBase):
    def __init__(self, cfg: DeviceConfig) -> None:
        self.enabled = False
        self._req = None
        self._value_active = None
        self._value_inactive = None
        self.chip = cfg.led_gpiod_chip
        self.green_pin = cfg.led_gpiod_green
        self.red_pin = cfg.led_gpiod_red

        try:
            import gpiod
            from gpiod import LineSettings
            from gpiod.line import Direction, Value

            self._value_active = Value.ACTIVE
            self._value_inactive = Value.INACTIVE
            self._req = gpiod.request_lines(
                self.chip,
                consumer="smartpresence_led",
                config={
                    self.green_pin: LineSettings(direction=Direction.OUTPUT),
                    self.red_pin: LineSettings(direction=Direction.OUTPUT),
                },
            )
            self._set(self.green_pin, 0)
            self._set(self.red_pin, 0)
            self.enabled = True
        except Exception as exc:
            print(f"[WARN] LED gpiod nonaktif: {exc}")

    def _set(self, pin: int, value: int) -> None:
        if not self.enabled or self._req is None:
            return
        self._req.set_value(pin, self._value_active if value else self._value_inactive)

    def _pulse(self, pin: int, duration: float) -> None:
        if not self.enabled or self._req is None:
            return
        self._set(self.green_pin, 0)
        self._set(self.red_pin, 0)
        self._set(pin, 1)
        time.sleep(duration)
        self._set(pin, 0)

    def success(self) -> None:
        print("[LED] success")
        self._pulse(self.green_pin, 0.5)

    def error(self) -> None:
        print("[LED] error")
        self._pulse(self.red_pin, 0.6)

    def info(self) -> None:
        print("[LED] info")
        self._pulse(self.green_pin, 0.15)

    def close(self) -> None:
        if self.enabled and self._req is not None:
            try:
                self._set(self.green_pin, 0)
                self._set(self.red_pin, 0)
                self._req.release()
            except Exception:
                pass


def build_led_indicator(cfg: DeviceConfig) -> LedIndicatorBase:
    if not cfg.led_enabled:
        return LedIndicatorBase()

    if cfg.led_backend == "gpiod":
        return GpiodLedIndicator(cfg)

    return OpiLedIndicator(cfg)


class IotApiClient:
    def __init__(self, cfg: DeviceConfig) -> None:
        self.url = cfg.iot_api_url
        self.health_url = cfg.iot_health_url
        self.heartbeat_url = cfg.iot_heartbeat_url
        self.command_url = cfg.iot_command_url
        self.register_capture_url = cfg.iot_register_capture_url
        self.token = cfg.device_token
        self.timeout = cfg.timeout_sec

    def _headers(self) -> dict[str, str]:
        if not self.token:
            raise RuntimeError("IOT_DEVICE_TOKEN wajib diisi.")
        return {"X-Device-Token": self.token}

    @staticmethod
    def _decode_response(resp: requests.Response) -> dict:
        content_type = resp.headers.get("Content-Type", "")
        if "application/json" in content_type:
            payload = resp.json()
            if isinstance(payload, dict):
                payload["http_status"] = resp.status_code
                return payload
        return {"status": "error", "message": resp.text[:300], "http_status": resp.status_code}

    def health_check(self) -> bool:
        resp = requests.get(self.health_url, headers=self._headers(), timeout=self.timeout)
        return resp.status_code == 200

    def send_heartbeat(
        self,
        cfg: DeviceConfig,
        mode: str,
        session_token: str = "",
        message: str = "",
    ) -> dict:
        data = {
            "device_code": cfg.device_code,
            "device_name": cfg.device_name,
            "mode": mode,
            "message": message[:255],
            "firmware_version": cfg.firmware_version,
        }
        if session_token:
            data["session_token"] = session_token

        resp = requests.post(
            self.heartbeat_url,
            headers=self._headers(),
            data=data,
            timeout=self.timeout,
        )
        return self._decode_response(resp)

    def poll_command(self, cfg: DeviceConfig, mode: str, message: str = "") -> dict:
        params = {
            "device_code": cfg.device_code,
            "device_name": cfg.device_name,
            "mode": mode,
            "message": message[:255],
            "firmware_version": cfg.firmware_version,
        }
        resp = requests.get(
            self.command_url,
            headers=self._headers(),
            params=params,
            timeout=self.timeout,
        )
        return self._decode_response(resp)

    def send_scan(
        self,
        rfid_uid: str = "",
        image_bytes: Optional[bytes] = None,
        intent: str = "attendance",
    ) -> dict:
        files = None
        if image_bytes is not None:
            files = {
                "image": ("scan.jpg", image_bytes, "image/jpeg"),
            }

        data = {
            "intent": (intent or "attendance").strip().lower(),
        }
        if rfid_uid.strip():
            data["rfid_uid"] = rfid_uid.strip()

        resp = requests.post(
            self.url,
            headers=self._headers(),
            data=data,
            files=files,
            timeout=self.timeout,
        )
        return self._decode_response(resp)

    def send_register_capture(
        self,
        cfg: DeviceConfig,
        session_token: str,
        rfid_uid: str,
        image_bytes: bytes,
    ) -> dict:
        files = {
            "image": ("register.jpg", image_bytes, "image/jpeg"),
        }
        data = {
            "device_code": cfg.device_code,
            "session_token": session_token,
            "rfid_uid": rfid_uid,
        }

        resp = requests.post(
            self.register_capture_url,
            headers=self._headers(),
            data=data,
            files=files,
            timeout=self.timeout,
        )
        return self._decode_response(resp)


def build_rfid_reader(cfg: DeviceConfig) -> RfidReader:
    if cfg.rfid_mode == "softspi":
        return SoftSpiRc522RfidReader(cfg)

    if cfg.rfid_mode == "rc522":
        return Rc522RfidReader()

    if cfg.rfid_mode == "serial":
        return SerialRfidReader(cfg.rfid_serial_port, cfg.rfid_serial_baudrate)

    return StdinRfidReader()


def is_recent_rfid(last_uid: str, last_time: float, ttl_sec: float, now: float) -> bool:
    return bool(last_uid) and (now - last_time) <= ttl_sec


def build_fail_line(message: str) -> str:
    lower = message.lower()
    if "gateway face recognition" in lower or ("gateway" in lower and "face" in lower):
        return "Backend wajah off"
    if "gateway" in lower and ("akses" in lower or "connection" in lower or "connect" in lower):
        return "Backend wajah off"
    if "unknown" in lower or "tidak dikenal" in lower or "tidak cocok" in lower:
        return "Wajah tidak dikenal"
    if "rfid" in lower and "tidak" in lower:
        return "RFID tidak valid"
    if message:
        return trim16(message)
    return "Verifikasi gagal"


def resolve_attendance_auth_mode(raw: str) -> str:
    return "both"


def main() -> int:
    cfg = DeviceConfig.from_env()

    print("=== SmartPresence IoT Device Service ===")
    print(f"Device  : {cfg.device_code} ({cfg.device_name})")
    print(f"IOT API : {cfg.iot_api_url}")
    print(f"RFID    : {cfg.rfid_mode}")
    attendance_auth_mode = resolve_attendance_auth_mode(cfg.attendance_auth_mode)
    if (cfg.attendance_auth_mode or "").strip().lower() != "both":
        print("[WARN] ATTENDANCE_AUTH_MODE dipaksa ke 'both' (wajib RFID + wajah).")
    print(f"AUTH    : {attendance_auth_mode}")

    camera = Camera(cfg)
    face_detector = FaceDetector(cfg.face_min_size)
    display = StatusDisplay(cfg)
    led = build_led_indicator(cfg)
    rfid = build_rfid_reader(cfg)
    api = IotApiClient(cfg)

    mode = "attendance"
    active_session_token = ""
    active_session_id: Optional[int] = None
    active_session_status = ""
    last_heartbeat_at = 0.0
    last_command_poll_at = 0.0
    last_mode_key = ""
    last_message = "Device ready"

    rfid_cache_uid = ""
    rfid_cache_at = 0.0
    face_retry_attempts = 0
    last_rfid_event_uid = ""
    last_rfid_event_at = 0.0
    last_face_check_at = 0.0
    last_face_event_at = 0.0
    last_attendance_attempt_at = 0.0
    last_standby_at = 0.0
    last_camera_error_at = 0.0

    read_timeout = 0.02 if rfid.supports_timeout() else None

    def clear_attendance_rfid_session() -> None:
        nonlocal rfid_cache_uid, rfid_cache_at, face_retry_attempts
        rfid_cache_uid = ""
        rfid_cache_at = 0.0
        face_retry_attempts = 0

    try:
        camera.open()

        if api.health_check():
            print("[OK] Health check endpoint IoT berhasil.")
            display.show("Backend online", "Ready")
        else:
            print("[WARN] Health check endpoint IoT gagal.")
            display.show("Backend warning", "Check service")

        if cfg.rfid_mode == "stdin":
            print("[WARN] RFID_MODE=stdin kurang cocok untuk realtime kamera. Pakai softspi/serial/rc522.")

        while True:
            now = time.time()

            if now - last_command_poll_at >= max(0.5, cfg.command_poll_interval_sec):
                try:
                    cmd = api.poll_command(cfg, mode=mode, message=last_message)
                    cmd_mode = str(cmd.get("mode") or "attendance").strip().lower()
                    register_session = cmd.get("register_session")

                    if cmd_mode == "register" and isinstance(register_session, dict):
                        incoming_token = str(register_session.get("session_token") or "").strip()
                        incoming_id_raw = register_session.get("id_session")
                        incoming_id = int(incoming_id_raw) if str(incoming_id_raw or "").isdigit() else None
                        incoming_status = str(register_session.get("status") or "").strip().lower()

                        if incoming_token:
                            if incoming_token != active_session_token:
                                print(f"[MODE] Register ON (session #{incoming_id or '-'})")
                            mode = "register"
                            active_session_token = incoming_token
                            active_session_id = incoming_id
                            active_session_status = incoming_status or "waiting_device"
                            if active_session_status == "captured":
                                last_message = "Menunggu simpan admin"
                            else:
                                last_message = "Mode register aktif"
                    elif mode == "register":
                        print("[MODE] Register OFF (ditutup server)")
                        mode = "attendance"
                        active_session_token = ""
                        active_session_id = None
                        active_session_status = ""
                        clear_attendance_rfid_session()
                        last_message = "Kembali ke mode absensi"
                except Exception as exc:
                    print(f"[WARN] Poll command gagal: {exc}")
                finally:
                    last_command_poll_at = now

            if now - last_heartbeat_at >= max(2.0, cfg.heartbeat_interval_sec):
                try:
                    api.send_heartbeat(
                        cfg,
                        mode=mode,
                        session_token=active_session_token,
                        message=last_message,
                    )
                except Exception as exc:
                    print(f"[WARN] Heartbeat gagal: {exc}")
                finally:
                    last_heartbeat_at = now

            mode_key = f"{mode}:{active_session_id or 0}:{active_session_status}"
            if mode_key != last_mode_key:
                if mode == "register":
                    if active_session_status == "captured":
                        display.show("REGIS MENUNGGU", "Simpan di admin")
                    else:
                        display.show("MODE REGISTRASI", f"SID {active_session_id or '-'}")
                else:
                    display.show("Kamera Standby", "Tap RFID dulu")
                last_mode_key = mode_key
                last_standby_at = now

            uid = rfid.read_uid(timeout_sec=read_timeout)
            if uid:
                if uid == last_rfid_event_uid and (now - last_rfid_event_at) < 1.5:
                    pass
                else:
                    last_rfid_event_uid = uid
                    last_rfid_event_at = now
                    print(f"[RFID] {uid}")
                    display.show("RFID Terbaca", trim16(uid))
                    led.info()
                    last_message = f"RFID terbaca: {uid}"

                    if mode == "attendance":
                        try:
                            precheck = api.send_scan(rfid_uid=uid, image_bytes=None, intent="precheck")
                        except Exception as exc:
                            msg = f"Gagal validasi RFID: {exc}"
                            print(f"[ERROR] {msg}")
                            display.show("Kartu Gagal", "API error")
                            led.error()
                            last_message = msg
                            clear_attendance_rfid_session()
                            time.sleep(0.05)
                        else:
                            status = str(precheck.get("status") or "").lower()
                            message = str(precheck.get("message") or "")
                            identity = (
                                precheck.get("identity")
                                if isinstance(precheck.get("identity"), dict)
                                else {}
                            )
                            name = str(identity.get("name") or "")
                            print(
                                "[RFID-PRECHECK] "
                                f"status={status} http={precheck.get('http_status')} msg={message}"
                            )

                            if status == "verified":
                                rfid_cache_uid = uid
                                rfid_cache_at = now
                                face_retry_attempts = 0
                                display.show("Kartu Valid", "Arahkan wajah")
                                led.info()
                                last_message = f"RFID valid: {name or uid}. Menunggu wajah."
                            else:
                                clear_attendance_rfid_session()
                                display.show("Kartu TidakValid", "Daftar dulu")
                                led.error()
                                last_message = f"RFID tidak valid: {message}"
                    else:
                        rfid_cache_uid = uid
                        rfid_cache_at = now

            frame = camera.read_frame()
            if frame is None:
                if now - last_camera_error_at >= max(1.0, cfg.camera_error_repeat_sec):
                    display.show("Kamera Error", "Cek webcam")
                    led.error()
                    last_camera_error_at = now
                    last_message = "Kamera error"
                time.sleep(0.05)
                continue

            if now - last_face_check_at >= max(0.05, cfg.face_detect_interval_sec):
                last_face_check_at = now
                has_face = face_detector.has_face(frame)

                if has_face and (now - last_face_event_at) >= max(0.5, cfg.face_event_cooldown_sec):
                    last_face_event_at = now
                    recent_rfid = is_recent_rfid(rfid_cache_uid, rfid_cache_at, cfg.rfid_cache_ttl_sec, now)

                    try:
                        image_bytes = Camera.encode_jpeg(frame, cfg.jpeg_quality)
                    except Exception as exc:
                        msg = f"Gagal encode wajah: {exc}"
                        print(f"[ERROR] {msg}")
                        display.show("Gagal Kamera", "Capture error")
                        led.error()
                        last_message = msg
                        time.sleep(0.05)
                        continue

                    if mode == "register" and active_session_token:
                        if active_session_status == "captured":
                            display.show("Data Sudah Ada", "Simpan di admin")
                            led.info()
                            last_message = "Menunggu simpan admin"
                            if cfg.consume_rfid_after_attempt:
                                clear_attendance_rfid_session()
                        elif not recent_rfid:
                            display.show("Mode Regis", "Tap kartu dulu")
                            led.error()
                            last_message = "Regis gagal: RFID belum ada"
                        else:
                            try:
                                result = api.send_register_capture(
                                    cfg,
                                    active_session_token,
                                    rfid_cache_uid,
                                    image_bytes,
                                )
                            except Exception as exc:
                                msg = f"Gagal kirim regis: {exc}"
                                print(f"[ERROR] {msg}")
                                display.show("Regis Gagal", "API error")
                                led.error()
                                last_message = msg
                                time.sleep(0.05)
                                continue

                            status = str(result.get("status") or "").lower()
                            message = str(result.get("message") or "")
                            print(f"[REG] status={status} http={result.get('http_status')} msg={message}")

                            if status == "captured":
                                display.show("Regis Berhasil", trim16(rfid_cache_uid))
                                led.success()
                                last_message = "Capture berhasil, tunggu admin simpan"
                                active_session_status = "captured"
                            elif "simpan" in message.lower() and "admin" in message.lower():
                                display.show("REGIS MENUNGGU", "Simpan di admin")
                                led.info()
                                last_message = "Menunggu simpan admin"
                                active_session_status = "captured"
                            else:
                                display.show("Regis Gagal", trim16(message) if message else "Coba ulang")
                                led.error()
                                last_message = f"Capture registrasi gagal: {message}"

                            if cfg.consume_rfid_after_attempt:
                                clear_attendance_rfid_session()
                    else:
                        if (now - last_attendance_attempt_at) < max(0.8, cfg.attendance_attempt_cooldown_sec):
                            continue

                        recent_rfid_for_face = is_recent_rfid(
                            rfid_cache_uid,
                            rfid_cache_at,
                            cfg.rfid_face_wait_timeout_sec,
                            now,
                        )
                        if not recent_rfid_for_face:
                            if rfid_cache_uid:
                                display.show("Sesi Kartu Habis", "Tap kartu ulang")
                                led.error()
                                last_message = "Sesi kartu habis sebelum verifikasi wajah."
                                clear_attendance_rfid_session()
                            else:
                                display.show("Wajah Terbaca", "Tap RFID dulu")
                                led.error()
                                last_message = "Wajah terbaca tanpa RFID valid."
                            continue

                        last_attendance_attempt_at = now
                        try:
                            result = api.send_scan(
                                rfid_uid=rfid_cache_uid,
                                image_bytes=image_bytes,
                                intent="attendance",
                            )
                        except Exception as exc:
                            msg = f"Gagal kirim absen: {exc}"
                            print(f"[ERROR] {msg}")
                            face_retry_attempts += 1
                            if face_retry_attempts < cfg.face_retry_max_attempts:
                                display.show(
                                    f"Wajah Gagal {face_retry_attempts}/{cfg.face_retry_max_attempts}",
                                    "Coba lagi",
                                )
                                led.error()
                                last_message = (
                                    f"Verifikasi wajah gagal ({face_retry_attempts}/"
                                    f"{cfg.face_retry_max_attempts}): {exc}"
                                )
                                rfid_cache_at = now
                                time.sleep(0.05)
                                continue

                            display.show("Gagal Absen", "Wajah tidak valid")
                            led.error()
                            last_message = (
                                "Gagal absen: verifikasi wajah tetap gagal setelah "
                                f"{cfg.face_retry_max_attempts} percobaan."
                            )
                            clear_attendance_rfid_session()
                            time.sleep(0.05)
                            continue

                        status = str(result.get("status") or "").lower()
                        message = str(result.get("message") or "")
                        identity = result.get("identity") if isinstance(result.get("identity"), dict) else {}
                        name = str(identity.get("name") or "")
                        auth_mode_resp = str(result.get("auth_mode") or "")
                        print(
                            f"[ABSEN] mode={auth_mode_resp or attendance_auth_mode} "
                            f"status={status} http={result.get('http_status')} msg={message}"
                        )

                        if status == "verified":
                            display.show("Selamat Absen", trim16(name or "Terverifikasi"))
                            led.success()
                            last_message = f"Selamat absen: {name or 'terverifikasi'}"
                            clear_attendance_rfid_session()
                        else:
                            face_retry_attempts += 1
                            if face_retry_attempts < cfg.face_retry_max_attempts:
                                display.show(
                                    f"Wajah Gagal {face_retry_attempts}/{cfg.face_retry_max_attempts}",
                                    "Arahkan ulang",
                                )
                                led.error()
                                last_message = (
                                    f"Wajah belum cocok ({face_retry_attempts}/"
                                    f"{cfg.face_retry_max_attempts}): {message}"
                                )
                                rfid_cache_at = now
                            else:
                                display.show("Gagal Absen", build_fail_line(message))
                                led.error()
                                last_message = (
                                    "Gagal absen: wajah tidak cocok setelah "
                                    f"{cfg.face_retry_max_attempts} percobaan."
                                )
                                clear_attendance_rfid_session()

            recent_rfid_now = is_recent_rfid(
                rfid_cache_uid,
                rfid_cache_at,
                cfg.rfid_face_wait_timeout_sec,
                now,
            )
            if now - last_standby_at >= max(1.0, cfg.standby_text_interval_sec):
                if mode == "register":
                    if active_session_status == "captured":
                        display.show("REGIS MENUNGGU", "Simpan di admin")
                    elif is_recent_rfid(rfid_cache_uid, rfid_cache_at, cfg.rfid_cache_ttl_sec, now):
                        display.show("Mode Regis ON", "Arahkan wajah")
                    else:
                        display.show("Mode Regis ON", "Tap kartu RFID")
                else:
                    if recent_rfid_now:
                        display.show(
                            f"RFID Valid {face_retry_attempts}/{cfg.face_retry_max_attempts}",
                            "Arahkan wajah",
                        )
                    else:
                        display.show("Kamera Standby", "Tap RFID dulu")
                last_standby_at = now

            time.sleep(0.03)

    except KeyboardInterrupt:
        print("\n[INFO] Service dihentikan user.")
        return 0
    except Exception as exc:
        print(f"[FATAL] {exc}")
        return 1
    finally:
        rfid.close()
        camera.close()
        display.close()
        led.close()


if __name__ == "__main__":
    sys.exit(main())
