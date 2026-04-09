from __future__ import annotations

import concurrent.futures
import threading
import time
from collections.abc import Callable, Iterable
from datetime import datetime

import requests

from models import ProxyRecord, VALIDATION_STATUS_INVALID, VALIDATION_STATUS_VALID

TEST_ENDPOINTS = (
    "https://httpbin.org/ip",
    "https://api.ipify.org?format=json",
    "https://ifconfig.me/all.json",
)


def build_requests_proxy(record: ProxyRecord) -> dict[str, str]:
    if record.protocol == "HTTP":
        proxy_url = f"http://{record.address}"
        return {"http": proxy_url, "https": proxy_url}
    if record.protocol == "SOCKS4":
        proxy_url = f"socks4://{record.address}"
        return {"http": proxy_url, "https": proxy_url}
    if record.protocol == "SOCKS5":
        proxy_url = f"socks5://{record.address}"
        return {"http": proxy_url, "https": proxy_url}
    raise ValueError(f"Unsupported protocol: {record.protocol}")


def extract_detected_ip(response: requests.Response) -> str | None:
    try:
        payload = response.json()
    except ValueError:
        return None

    if isinstance(payload, dict):
        if isinstance(payload.get("origin"), str):
            return payload["origin"]
        if isinstance(payload.get("ip"), str):
            return payload["ip"]
    return None


def validate_proxy(
    record: ProxyRecord,
    *,
    timeout: float = 8.0,
    test_endpoints: Iterable[str] = TEST_ENDPOINTS,
) -> ProxyRecord:
    proxies = build_requests_proxy(record)
    last_error: str | None = None

    for endpoint in test_endpoints:
        started = time.perf_counter()
        try:
            response = requests.get(endpoint, proxies=proxies, timeout=timeout)
            response.raise_for_status()
            elapsed_ms = int((time.perf_counter() - started) * 1000)
            record.status = VALIDATION_STATUS_VALID
            record.response_time_ms = elapsed_ms
            record.detected_ip = extract_detected_ip(response)
            record.error_message = None
            record.last_checked_at = datetime.now()
            return record
        except requests.RequestException as exc:
            last_error = str(exc)

    record.status = VALIDATION_STATUS_INVALID
    record.response_time_ms = None
    record.detected_ip = None
    record.error_message = last_error or "Unknown validation error"
    record.last_checked_at = datetime.now()
    return record


def validate_proxies(
    records: list[ProxyRecord],
    *,
    timeout: float = 8.0,
    max_workers: int = 20,
    cancel_event: threading.Event | None = None,
    progress_callback: Callable[[int, int, ProxyRecord], None] | None = None,
) -> None:
    if not records:
        return

    cancel_event = cancel_event or threading.Event()
    completed = 0
    total = len(records)

    with concurrent.futures.ThreadPoolExecutor(max_workers=max_workers) as executor:
        future_to_record: dict[concurrent.futures.Future[ProxyRecord], ProxyRecord] = {}
        for record in records:
            if cancel_event.is_set():
                break
            future = executor.submit(validate_proxy, record, timeout=timeout)
            future_to_record[future] = record

        for future in concurrent.futures.as_completed(future_to_record):
            record = future_to_record[future]
            try:
                future.result()
            except Exception as exc:  # pragma: no cover - defensive path
                record.status = VALIDATION_STATUS_INVALID
                record.response_time_ms = None
                record.detected_ip = None
                record.error_message = str(exc)
                record.last_checked_at = datetime.now()
            completed += 1
            if progress_callback is not None:
                progress_callback(completed, total, record)
            if cancel_event.is_set():
                for pending_future in future_to_record:
                    pending_future.cancel()
                break

