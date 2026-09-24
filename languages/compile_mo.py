#!/usr/bin/env python3
from __future__ import annotations

import struct
import sys
from pathlib import Path


def po_unescape(s: str) -> str:
    out: list[str] = []
    i = 0
    while i < len(s):
        if s[i] == "\\" and i + 1 < len(s):
            n = s[i + 1]
            out.append({"n": "\n", "t": "\t", '"': '"', "\\": "\\"}.get(n, n))
            i += 2
        else:
            out.append(s[i])
            i += 1
    return "".join(out)


def extract_quoted_segments(fragment: str) -> list[str]:
    parts: list[str] = []
    i = 0
    n = len(fragment)
    while i < n:
        while i < n and fragment[i] in " \t":
            i += 1
        if i >= n:
            break
        if fragment[i] != '"':
            raise ValueError("expected opening quote in " + repr(fragment[:80]))
        i += 1
        start = i
        while i < n:
            if fragment[i] == "\\":
                i += 2
                continue
            if fragment[i] == '"':
                parts.append(po_unescape(fragment[start:i]))
                i += 1
                break
            i += 1
        else:
            raise ValueError("unterminated string")
    return parts


def read_msg_block(lines: list[str], i: int, kw: str) -> tuple[str, int]:
    line = lines[i]
    st = line.strip()
    if not st.startswith(kw + " "):
        raise ValueError(f"expected {kw}, got {line!r}")
    idx = line.find(kw) + len(kw)
    chunks = extract_quoted_segments(line[idx:])
    i += 1
    while i < len(lines) and lines[i].strip().startswith('"'):
        chunks.extend(extract_quoted_segments(lines[i].strip()))
        i += 1
    return "".join(chunks), i


def parse_po(path: Path) -> list[tuple[bytes, bytes]]:
    lines = path.read_text(encoding="utf-8").splitlines()
    entries: list[tuple[bytes, bytes]] = []
    i = 0
    while i < len(lines):
        st = lines[i].strip()
        if not st or st.startswith("#"):
            i += 1
            continue
        if not st.startswith("msgid "):
            i += 1
            continue
        msgid, i = read_msg_block(lines, i, "msgid")
        while i < len(lines):
            s = lines[i].strip()
            if s.startswith("msgstr "):
                break
            i += 1
        else:
            break
        msgstr, i = read_msg_block(lines, i, "msgstr")
        entries.append((msgid.encode("utf-8"), msgstr.encode("utf-8")))
    return entries


def write_mo(entries: list[tuple[bytes, bytes]], out: Path) -> None:
    entries = sorted(entries, key=lambda e: e[0])
    n = len(entries)
    header_size = 28
    table_size = 8 * n
    o_tab_off = header_size
    t_tab_off = header_size + table_size
    data_off = header_size + 2 * table_size

    orig_lens_offs: list[tuple[int, int]] = []
    trans_lens_offs: list[tuple[int, int]] = []
    pos = data_off
    orig_blob = bytearray()
    for msgid, msgstr in entries:
        orig_lens_offs.append((len(msgid), pos))
        orig_blob.extend(msgid)
        orig_blob.append(0)
        pos += len(msgid) + 1

    trans_blob = bytearray()
    for msgid, msgstr in entries:
        trans_lens_offs.append((len(msgstr), pos))
        trans_blob.extend(msgstr)
        trans_blob.append(0)
        pos += len(msgstr) + 1

    data = bytearray()
    data.extend(struct.pack("<7I", 0x950412DE, 0, n, o_tab_off, t_tab_off, 0, 0))
    for length, offset in orig_lens_offs:
        data.extend(struct.pack("<II", length, offset))
    for length, offset in trans_lens_offs:
        data.extend(struct.pack("<II", length, offset))
    data.extend(orig_blob)
    data.extend(trans_blob)
    out.write_bytes(data)


def main() -> None:
    root = Path(__file__).resolve().parent
    po = root / "score-ticker-gl_ES.po"
    mo = root / "score-ticker-gl_ES.mo"
    if not po.is_file():
        print("missing", po, file=sys.stderr)
        sys.exit(1)
    entries = parse_po(po)
    write_mo(entries, mo)
    print("Wrote", mo, "entries:", len(entries))


if __name__ == "__main__":
    main()
