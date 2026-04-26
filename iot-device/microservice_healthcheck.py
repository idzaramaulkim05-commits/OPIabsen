#!/usr/bin/env python3
"""Simple health check for all SmartPresence microservices."""

from __future__ import annotations

import os
import sys

import requests

try:
    from dotenv import load_dotenv
except Exception:
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


def check(url: str, headers: dict | None = None, timeout: int = 5) -> tuple[bool, str]:
    try:
        resp = requests.get(url, headers=headers, timeout=timeout)
        ok = 200 <= resp.status_code < 300
        return ok, f"HTTP {resp.status_code}"
    except Exception as exc:
        return False, str(exc)


def main() -> int:
    if load_dotenv is not None:
        load_dotenv()
    else:
        load_env_file_fallback(".env")

    ci_health = os.getenv("IOT_HEALTH_URL", "http://192.168.0.104:8080/api/iot/health")
    ci_token = os.getenv("IOT_DEVICE_TOKEN", "") or os.getenv("iotDevice.deviceToken", "")

    gateway_url = os.getenv("FACE_GATEWAY_URL", "http://192.168.0.104:8000/api/attendance")
    gateway_token = os.getenv("FACE_GATEWAY_BEARER_TOKEN", "") or os.getenv("faceGateway.bearerToken", "absensiiot2026-token")

    engine_url = os.getenv("FACE_ENGINE_HEALTH_URL", "http://192.168.0.104:8001/health")

    checks = [
        ("CodeIgniter IoT", ci_health, {"X-Device-Token": ci_token} if ci_token else {}),
        ("Laravel Gateway", gateway_url, {"Authorization": f"Bearer {gateway_token}"} if gateway_token else {}),
        ("FastAPI Engine", engine_url, {}),
    ]

    print("=== Microservice Health Check ===")
    failed = 0
    for name, url, headers in checks:
        ok, detail = check(url, headers=headers)
        status = "OK" if ok else "FAIL"
        print(f"[{status}] {name:<18} -> {url} ({detail})")
        if not ok:
            failed += 1

    if failed:
        print(f"\nTotal gagal: {failed}")
        return 1

    print("\nSemua microservice online.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
