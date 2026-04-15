@extends('layouts.guest')

@section('content')
<div id="ujian-app" class="flex min-h-screen flex-col">
    <div class="flex flex-1 flex-col md:flex-row">
        <!-- Soal Section -->
        <div class="flex flex-1 flex-col justify-center p-4 sm:p-6">
            <div class="mb-4">
                <span class="text-sm text-gray-500">Soal <span id="soal-index">1</span> dari <span id="soal-total">10</span></span>
            </div>
            <div class="mb-4">
                <img id="soal-gambar" src="" alt="Gambar Soal" class="mb-2 hidden max-h-48 rounded border" />
                <div id="soal-teks" class="text-lg font-semibold mb-4">Teks soal akan tampil di sini.</div>
                <div id="soal-opsi" class="space-y-2">
                    <!-- Opsi jawaban akan di-render di sini -->
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-2 sm:grid-cols-3">
                <button id="btn-sebelumnya" class="rounded bg-gray-300 px-4 py-2 hover:bg-gray-400">Sebelumnya</button>
                <button id="btn-ragu" class="rounded bg-yellow-400 px-4 py-2 text-white hover:bg-yellow-500">Ragu-ragu</button>
                <button id="btn-selanjutnya" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Selanjutnya</button>
            </div>
        </div>
        <!-- Sidebar Section -->
        <div class="flex w-full flex-col border-t bg-white p-4 md:w-72 md:border-l md:border-t-0">
            <div class="mb-4 text-center">
                <span class="text-xs text-gray-500 uppercase font-bold">Sisa Waktu</span>
                <div id="timer-display" class="text-2xl font-bold text-red-600 tracking-wider">--:--:--</div>
            </div>
            <div class="flex items-center justify-between mb-4">
                <span class="font-semibold">Kontrol Teks</span>
                <div class="flex gap-2">
                    <button id="btn-text-sm" class="px-2 py-1 text-xs bg-gray-200 rounded">A-</button>
                    <button id="btn-text-lg" class="px-2 py-1 text-xs bg-gray-200 rounded">A+</button>
                </div>
            </div>
            <div class="mb-4">
                <div class="flex items-center mb-1">
                    <span class="inline-block w-4 h-4 bg-green-500 rounded mr-2"></span>
                    <span>Sudah dijawab</span>
                </div>
                <div class="flex items-center mb-1">
                    <span class="inline-block w-4 h-4 bg-yellow-400 rounded mr-2"></span>
                    <span>Ragu-ragu</span>
                </div>
                <div class="flex items-center mb-1">
                    <span class="inline-block w-4 h-4 bg-gray-400 rounded mr-2"></span>
                    <span>Belum dijawab</span>
                </div>
            </div>
            <div class="mb-4 flex flex-wrap gap-2" id="soal-list">
                <!-- List nomor soal -->
            </div>
            <button id="btn-selesai" class="mt-4 w-full rounded bg-red-600 py-2 text-white hover:bg-red-700 md:mt-auto">Selesaikan</button>
        </div>
    </div>
    <!-- Modal konfirmasi selesai -->
    <div id="modal-konfirmasi" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-sm rounded shadow bg-white p-6 mx-4">
            <h3 class="text-lg font-bold mb-4">Konfirmasi Selesai Ujian</h3>
            <p class="mb-4">Apakah Anda yakin ingin menyelesaikan ujian?</p>
            <div class="flex justify-end gap-2">
                <button id="btn-batal-modal" class="px-4 py-2 bg-gray-300 rounded">Batal</button>
                <button id="btn-konfirmasi-modal" class="px-4 py-2 bg-red-600 text-white rounded">Ya, Selesai</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const questions = @json($questions);
const waktuMulaiStr = "{{ $participant->waktu_mulai ? $participant->waktu_mulai->toIso8601String() : now()->toIso8601String() }}";
const timerMinutes = {{ $exam->timer ?? 120 }};

function openFullscreen() {
    const el = document.documentElement;
    if (el.requestFullscreen) el.requestFullscreen();
    else if (el.mozRequestFullScreen) el.mozRequestFullScreen();
    else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    else if (el.msRequestFullscreen) el.msRequestFullscreen();
}

let blurCount = 0;
function preventTabSwitch() {
    const warn = () => {
        blurCount++;
        alert(`Peringatan! Jangan keluar dari ujian! (${blurCount}/3)`);
        if (blurCount >= 3) {
            alert('Batas pelanggaran terlampaui. Ujian dihentikan paksa.');
            window.location.href = "{{ route('siswa.selesai') }}";
        }
    };
    window.onblur = warn;
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) warn();
    });
}

function initTimer() {
    const startTime = new Date(waktuMulaiStr).getTime();
    const endTime = startTime + (timerMinutes * 60000);
    const display = document.getElementById('timer-display');
    
    const interval = setInterval(function() {
        const now = new Date().getTime();
        const distance = endTime - now;
        
        if (distance <= 0) {
            clearInterval(interval);
            display.innerText = "HABIS";
            alert('Waktu ujian telah habis! Menyimpan otomatis...');
            window.location.href = "{{ route('siswa.selesai') }}";
            return;
        }
        
        let h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        let s = Math.floor((distance % (1000 * 60)) / 1000);
        
        display.innerText = (h < 10 ? "0"+h : h) + ":" + (m < 10 ? "0"+m : m) + ":" + (s < 10 ? "0"+s : s);
    }, 1000);
}

function saveAnswer(qId, jawab, ragu) {
    fetch("{{ route('siswa.api.save_answer') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ question_id: qId, jawaban: jawab, ragu_ragu: ragu })
    }).catch(console.error);
}

document.addEventListener('DOMContentLoaded', function() {
    // Bisa disesuaikan kalau pengguna merasa terganggu dipaksa FS
    // openFullscreen();
    preventTabSwitch();
    initTimer();

    const soalTeks = document.getElementById('soal-teks');
    document.getElementById('btn-text-sm').onclick = () => {
        soalTeks.classList.remove('text-lg', 'text-2xl');
        soalTeks.classList.add('text-base');
    };
    document.getElementById('btn-text-lg').onclick = () => {
        soalTeks.classList.remove('text-base', 'text-lg');
        soalTeks.classList.add('text-2xl');
    };

    document.getElementById('btn-selesai').onclick = () => document.getElementById('modal-konfirmasi').classList.remove('hidden');
    document.getElementById('btn-batal-modal').onclick = () => document.getElementById('modal-konfirmasi').classList.add('hidden');
    document.getElementById('btn-konfirmasi-modal').onclick = () => window.location.href = "{{ route('siswa.selesai') }}";

    const soalTotal = questions.length;
    const soalList = document.getElementById('soal-list');
    let currentSoal = 0;

    function renderSoalList() {
        soalList.innerHTML = '';
        for (let i = 0; i < soalTotal; i++) {
            const btn = document.createElement('button');
            btn.textContent = i + 1;
            
            let isRagu = questions[i].peserta_ragu;
            let isJawab = questions[i].peserta_jawaban;
            
            let bgClass = 'bg-gray-300 text-gray-700'; // belum
            if (isRagu) { bgClass = 'bg-yellow-400 text-white'; }
            else if (isJawab) { bgClass = 'bg-green-500 text-white'; }
            
            btn.className = `w-8 h-8 rounded font-bold ${bgClass}`;
            if (i === currentSoal) btn.classList.add('ring-2', 'ring-blue-500');
            
            btn.onclick = () => { currentSoal = i; updateSoal(); };
            soalList.appendChild(btn);
        }
    }

    function updateSoal() {
        if (soalTotal === 0) return;
        const q = questions[currentSoal];
        document.getElementById('soal-index').textContent = currentSoal + 1;
        document.getElementById('soal-total').textContent = soalTotal;
        document.getElementById('soal-teks').innerHTML = q.pertanyaan;
        
        const img = document.getElementById('soal-gambar');
        if (q.image_path) {
            img.src = '/storage/' + q.image_path;
            img.classList.remove('hidden');
        } else {
            img.classList.add('hidden');
        }

        const opsiContainer = document.getElementById('soal-opsi');
        opsiContainer.innerHTML = '';
        
        if (q.tipe === 'multiple_choice' || q.tipe === 'PG' && q.opsi) {
            // Asumsi opsi berupa object: { "A": "Teks opsi", "B": "...." }
            const opsisArray = typeof q.opsi === 'string' ? JSON.parse(q.opsi) : q.opsi;
            for (const [key, val] of Object.entries(opsisArray)) {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-3 p-3 border rounded cursor-pointer hover:bg-gray-50';
                
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = `opsi_${q.id}`;
                radio.value = key;
                radio.className = 'w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500';
                if (q.peserta_jawaban === key) radio.checked = true;
                
                const label = document.createElement('label');
                label.className = 'flex-1 cursor-pointer';
                label.innerHTML = `<strong>${key}.</strong> ${val}`;
                
                div.onclick = () => {
                    radio.checked = true;
                    q.peserta_jawaban = key;
                    if(q.peserta_ragu) { q.peserta_ragu = false; }
                    renderSoalList();
                    saveAnswer(q.id, key, false);
                };
                
                div.appendChild(radio);
                div.appendChild(label);
                opsiContainer.appendChild(div);
            }
        } else {
            const input = document.createElement('textarea');
            input.className = 'w-full p-2 border rounded';
            input.rows = 4;
            input.placeholder = 'Ketik jawaban Anda...';
            input.value = q.peserta_jawaban || '';
            input.onblur = (e) => {
                q.peserta_jawaban = e.target.value;
                renderSoalList();
                saveAnswer(q.id, e.target.value, q.peserta_ragu);
            };
            opsiContainer.appendChild(input);
        }
        
        renderSoalList();
    }

    document.getElementById('btn-sebelumnya').onclick = () => { if (currentSoal > 0) { currentSoal--; updateSoal(); } };
    document.getElementById('btn-selanjutnya').onclick = () => { if (currentSoal < soalTotal - 1) { currentSoal++; updateSoal(); } };
    
    document.getElementById('btn-ragu').onclick = () => {
        const q = questions[currentSoal];
        q.peserta_ragu = !q.peserta_ragu;
        renderSoalList();
        saveAnswer(q.id, q.peserta_jawaban, q.peserta_ragu);
    };

    updateSoal();
});
</script>
@endpush
