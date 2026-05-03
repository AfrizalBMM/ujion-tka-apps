<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Latihan Materi - Paket {{ $package->paket_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; line-height: 1.45; }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 4px; }
        .subtitle { color: #6b7280; margin: 0; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
        .soft { background: #f8fafc; }
        .muted { color: #6b7280; }
        .label { font-size: 9px; font-weight: 700; color: #6b7280; text-transform: uppercase; }
        .value { font-size: 14px; font-weight: 700; margin-top: 2px; }
        .metric { width: 25%; padding: 8px; border: 1px solid #e5e7eb; }
        .question-title { font-size: 13px; font-weight: 700; margin-bottom: 6px; }
        .passage { background: #eff6ff; border-left: 3px solid #2563eb; padding: 8px; margin: 8px 0; white-space: pre-line; }
        .question-text { margin: 8px 0; font-weight: 600; }
        .options { margin: 8px 0 0; padding: 0; list-style: none; }
        .options li { margin: 5px 0; padding: 5px 7px; border: 1px solid #e5e7eb; border-radius: 5px; }
        .option-label { display: inline-block; width: 18px; font-weight: 700; }
        .chosen { background: #eef2ff; border-color: #c7d2fe !important; }
        .correct { background: #ecfdf5; border-color: #a7f3d0 !important; }
        .wrong { background: #fff1f2; border-color: #fecdd3 !important; }
        .pill { display: inline-block; padding: 3px 7px; border-radius: 999px; font-size: 9px; font-weight: 700; }
        .pill-ok { background: #dcfce7; color: #166534; }
        .pill-bad { background: #fee2e2; color: #991b1b; }
        .pill-empty { background: #f1f5f9; color: #475569; }
        .answer-line { margin-top: 8px; font-size: 10px; }
        .explanation { margin-top: 8px; padding: 8px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
    </style>
</head>
<body>
    @php
        $labels = range('A', 'Z');
        $questions = $package->questions->values();
        $answeredCount = $answersByQuestionId->filter(fn ($answer) => filled($answer?->jawaban))->count();
        $totalSoal = (int) ($attempt->total_soal ?: $questions->count());
        $correctCount = (int) $attempt->benar;
        $wrongCount = max($answeredCount - $correctCount, 0);
        $emptyCount = max($totalSoal - $answeredCount, 0);

        $formatRichText = function ($value) {
            $allowedTags = '<b><strong><i><em><u><sup><sub><br><p><ul><ol><li>';
            $clean = strip_tags((string) $value, $allowedTags);

            return preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $clean);
        };

        $optionLabel = function ($question, $value) use ($labels) {
            if (blank($value) || ! is_array($question->options)) {
                return '-';
            }

            foreach ($question->options as $idx => $option) {
                if ((string) $option === (string) $value) {
                    return ($labels[$idx] ?? (string) ($idx + 1)) . '. ' . strip_tags((string) $option);
                }
            }

            return strip_tags((string) $value);
        };
    @endphp

    <div class="box">
        <div class="title">Hasil Latihan Materi - Paket {{ $package->paket_no }}</div>
        <p class="subtitle">
            {{ $material->subelement }} &middot; {{ $material->unit }} &middot; {{ $material->sub_unit }}
        </p>
    </div>

    <div class="box soft">
        <table>
            <tr>
                <td style="width: 34%;">
                    <div class="label">Siswa</div>
                    <div class="value">{{ $session->nama }}</div>
                    <div class="muted">{{ $session->nomor_wa ?: '-' }}</div>
                </td>
                <td style="width: 22%;">
                    <div class="label">Status Paket</div>
                    <div class="value">{{ ucfirst($attempt->status) }}</div>
                </td>
                <td style="width: 22%;">
                    <div class="label">Mulai</div>
                    <div class="value" style="font-size: 11px;">{{ $attempt->waktu_mulai?->format('d M Y H:i') ?? '-' }}</div>
                </td>
                <td style="width: 22%;">
                    <div class="label">Selesai</div>
                    <div class="value" style="font-size: 11px;">{{ $attempt->waktu_selesai?->format('d M Y H:i') ?? '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table style="margin-bottom: 10px;">
        <tr>
            <td class="metric">
                <div class="label">Skor</div>
                <div class="value">{{ $attempt->skor !== null ? number_format((float) $attempt->skor, 1) : '-' }}</div>
            </td>
            <td class="metric">
                <div class="label">Benar</div>
                <div class="value">{{ $correctCount }}</div>
            </td>
            <td class="metric">
                <div class="label">Salah</div>
                <div class="value">{{ $wrongCount }}</div>
            </td>
            <td class="metric">
                <div class="label">Kosong</div>
                <div class="value">{{ $emptyCount }}</div>
            </td>
        </tr>
    </table>

    @foreach($questions as $index => $q)
        @php
            $answer = $answersByQuestionId[$q->id] ?? null;
            $studentAnswer = $answer?->jawaban;
            $isAnswered = filled($studentAnswer);
            $isCorrect = (bool) ($answer?->is_correct ?? false);
            $statusClass = $isCorrect ? 'pill-ok' : ($isAnswered ? 'pill-bad' : 'pill-empty');
            $statusLabel = $isCorrect ? 'Benar' : ($isAnswered ? 'Salah' : 'Kosong');
        @endphp

        <div class="box">
            <div class="question-title">
                Soal {{ $index + 1 }}
                <span class="pill {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>

            @if($q->reading_passage)
                <div class="passage">{!! $formatRichText($q->reading_passage) !!}</div>
            @endif

            <div class="question-text">{!! $formatRichText($q->question_text) !!}</div>

            @if(is_array($q->options) && count($q->options))
                <ul class="options">
                    @foreach($q->options as $idx => $opt)
                        @php
                            $isChosen = $isAnswered && (string) $studentAnswer === (string) $opt;
                            $isCorrectOption = filled($q->answer_key) && (string) $q->answer_key === (string) $opt;
                            $optionClass = $isCorrectOption ? 'correct' : ($isChosen ? 'wrong' : '');
                        @endphp
                        <li class="{{ trim(($isChosen ? 'chosen ' : '') . $optionClass) }}">
                            <span class="option-label">{{ $labels[$idx] ?? ($idx + 1) }}.</span>
                            {!! $formatRichText($opt) !!}
                            @if($isChosen)
                                <span class="muted">(Jawaban siswa)</span>
                            @endif
                            @if($isCorrectOption)
                                <span class="muted">(Kunci)</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="answer-line">
                <strong>Jawaban siswa:</strong> {{ $optionLabel($q, $studentAnswer) }}<br>
                <strong>Kunci jawaban:</strong> {{ $optionLabel($q, $q->answer_key) }}
            </div>

            @if($q->explanation)
                <div class="explanation">
                    <strong>Pembahasan:</strong><br>
                    {!! $formatRichText($q->explanation) !!}
                </div>
            @endif
        </div>
    @endforeach
</body>
</html>
