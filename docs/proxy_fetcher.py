from __future__ import annotations

from dataclasses import replace
from typing import Iterable

import requests

from models import ProxyRecord

SOURCE_URLS = {
    "All Proxies": "https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt",
    "HTTP Only": "https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/http.txt",
    "SOCKS5 Only": "https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/socks5.txt",
}

COUNTRY_SOURCE_TEMPLATE = (
    "https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/countries/{country}.txt"
)


def get_source_options() -> list[str]:
    return list(SOURCE_URLS.keys())


def resolve_source_url(source_name: str) -> str:
    source_name = source_name.strip()
    if source_name in SOURCE_URLS:
        return SOURCE_URLS[source_name]
    if source_name.upper().startswith("COUNTRY:"):
        _, _, country_code = source_name.partition(":")
        country = country_code.strip().upper()
        if not country:
            raise ValueError("Country code is required for COUNTRY source")
        return COUNTRY_SOURCE_TEMPLATE.format(country=country)
    raise ValueError(f"Unsupported source: {source_name}")


def fetch_proxies(source_name: str, timeout: float = 15.0) -> list[ProxyRecord]:
    url = resolve_source_url(source_name)
    response = requests.get(url, timeout=timeout)
    response.raise_for_status()
    return parse_proxy_lines(response.text.splitlines())


def parse_proxy_lines(lines: Iterable[str]) -> list[ProxyRecord]:
    records: list[ProxyRecord] = []
    for raw_line in lines:
        line = raw_line.strip()
        if not line:
            continue
        parsed = parse_proxy_line(line)
        if parsed is not None:
            records.append(parsed)
    return records


def parse_proxy_line(line: str) -> ProxyRecord | None:
    parts = [part.strip() for part in line.split("|")]
    if len(parts) != 4:
        return None

    address, protocol, country, anonymity = parts
    if ":" not in address:
        return None

    host, port_text = address.rsplit(":", 1)
    host = host.strip()
    if not host:
        return None

    try:
        port = int(port_text)
    except ValueError:
        return None

    if not 1 <= port <= 65535:
        return None

    protocol = protocol.upper()
    country = country.upper()
    anonymity = anonymity.title()

    if protocol not in {"HTTP", "SOCKS4", "SOCKS5"}:
        return None

    return ProxyRecord(
        host=host,
        port=port,
        protocol=protocol,
        country=country,
        anonymity=anonymity,
    )


def build_country_options(records: Iterable[ProxyRecord]) -> list[str]:
    countries = sorted({record.country for record in records if record.country})
    return ["All", *countries]


def filter_proxies(
    records: Iterable[ProxyRecord],
    *,
    country: str = "All",
    protocol: str = "All",
    anonymity: str = "All",
    query: str = "",
) -> list[ProxyRecord]:
    filtered: list[ProxyRecord] = []
    normalized_country = country.upper()
    normalized_protocol = protocol.upper()
    normalized_anonymity = anonymity.lower()

    for record in records:
        if country != "All" and record.country.upper() != normalized_country:
            continue
        if protocol != "All" and record.protocol.upper() != normalized_protocol:
            continue
        if anonymity != "All" and record.anonymity.lower() != normalized_anonymity:
            continue
        if not record.matches_query(query):
            continue
        filtered.append(record)

    return filtered


def clone_records(records: Iterable[ProxyRecord]) -> list[ProxyRecord]:
    return [replace(record) for record in records]

