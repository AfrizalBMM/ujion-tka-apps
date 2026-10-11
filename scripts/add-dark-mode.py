#!/usr/bin/env python3
"""
Tambah varian dark: ke 4 halaman Superadmin yang bolong.
Mapping mengikuti konvensi yang sudah dipakai WaKoneksi.vue & Guru/*.
"""
import re, sys

FILES = [
    'resources/js/Pages/Superadmin/ExamAnalysis.vue',
    'resources/js/Pages/Superadmin/ExamBuilder.vue',
    'resources/js/Pages/Superadmin/ExamDetail.vue',
    'resources/js/Pages/Superadmin/Exams.vue',
]

# base token -> dark variant (konvensi dari WaKoneksi.vue / AnalisisSiswa.vue)
MAPPING = [
    # hover variants dulu (lebih panjang) supaya tidak dobel proses
    ('hover:text-slate-700', 'dark:hover:text-slate-200'),
    ('hover:bg-slate-100',   'dark:hover:bg-slate-800'),
    ('hover:bg-rose-50',     'dark:hover:bg-rose-900/30'),

    # teks slate
    ('text-slate-900', 'dark:text-slate-100'),
    ('text-slate-800', 'dark:text-slate-100'),
    ('text-slate-700', 'dark:text-slate-200'),
    ('text-slate-600', 'dark:text-slate-300'),
    ('text-slate-500', 'dark:text-slate-400'),
    ('text-slate-400', 'dark:text-slate-500'),
    ('text-slate-300', 'dark:text-slate-600'),

    # border slate
    ('border-slate-100', 'dark:border-slate-800'),
    ('border-slate-200', 'dark:border-slate-800'),
    ('border-slate-300', 'dark:border-slate-700'),

    # background slate — mapping dari konvensi AnalisisSiswa/Chat/WaKoneksi
    ('bg-slate-50',  'dark:bg-slate-900'),
    ('bg-slate-100', 'dark:bg-slate-800'),

    # overlay fullscreen ExamBuilder — di dark harus lebih rapat
    ('bg-slate-900/90', 'dark:bg-slate-950/95'),

    # background putih — konvensi WaKoneksi (halaman Superadmin acuan)
    ('bg-white', 'dark:bg-slate-900'),

    # warna aksen
    ('text-blue-700',    'dark:text-blue-400'),
    ('text-indigo-600',  'dark:text-indigo-400'),
    ('text-emerald-600', 'dark:text-emerald-400'),
    ('text-green-600',   'dark:text-green-400'),
    ('text-rose-500',    'dark:text-rose-400'),
    ('bg-rose-50',       'dark:bg-rose-900/30'),
]


def apply(line: str) -> str:
    """Tambah dark variant tepat setelah token aslinya, kalau belum ada."""
    if 'class="' not in line:
        return line

    for base, dark in MAPPING:
        # cocokkan token sebagai kata utuh, bukan bagian dari token lain
        pat = re.compile(r'(?<![\w:/-])' + re.escape(base) + r'(?![\w/-])')
        if not pat.search(line):
            continue
        # sudah ada varian dark-nya?
        if re.search(r'(?<![\w-])' + re.escape(dark) + r'(?![\w-])', line):
            continue
        line = pat.sub(lambda m, d=dark: m.group(0) + ' ' + d, line)

    return line


def main():
    total = 0
    for path in FILES:
        with open(path) as f:
            lines = f.readlines()

        changed = 0
        out = []
        for line in lines:
            new = apply(line)
            if new != line:
                changed += 1
            out.append(new)

        if changed:
            with open(path, 'w') as f:
                f.writelines(out)
        total += changed
        print(f'{path}: {changed} baris diubah')

    print(f'TOTAL: {total} baris')


if __name__ == '__main__':
    main()
