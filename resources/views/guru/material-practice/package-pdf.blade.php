<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Latihan Materi - Paket {{ $package->paket_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .muted { color: #6b7280; }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 6px; }
        .subtitle { margin: 0 0 14px; }
        .box { border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        .q-title { font-weight: 700; margin: 0 0 6px; }
        .passage { background: #eff6ff; padding: 8px; border-radius: 6px; margin: 8px 0; white-space: pre-line; }
        .options { margin: 8px 0 0; padding: 0; list-style: none; }
        .options li { margin: 4px 0; }
        .label { display: inline-block; width: 18px; font-weight: 700; }
        .hr { border-top: 1px solid #e5e7eb; margin: 10px 0; }
    </style>
</head>
<body>
    @php
        $material = $material;
        $labels = range('A', 'Z');
    @endphp

    <div class="box">
        <div class="title">Latihan Materi — Paket {{ $package->paket_no }}</div>
        <div class="subtitle">
            <span class="muted">Materi:</span> {{ $material->subelement }} · {{ $material->unit }} · {{ $material->sub_unit }}<br>
            <span class="muted">Token:</span> {{ $token->token }} &nbsp;&middot;&nbsp; <span class="muted">Jumlah soal:</span> {{ $package->questions->count() }}
        </div>
    </div>

    @foreach($package->questions as $i => $q)
        <div class="box">
            <div class="q-title">Soal {{ $i + 1 }}</div>

            @if($q->reading_passage)
                <div class="passage">{{ $q->reading_passage }}</div>
            @endif

            <div>{{ $q->question_text }}</div>

            @if(is_array($q->options) && count($q->options))
                <ul class="options">
                    @foreach($q->options as $idx => $opt)
                        <li><span class="label">{{ $labels[$idx] ?? ($idx + 1) }}.</span> {{ $opt }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</body>
</html>
