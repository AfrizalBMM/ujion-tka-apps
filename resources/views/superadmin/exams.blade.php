@extends('layouts.superadmin')
@section('title', 'Ujian')
@section('content')
<div class="space-y-6">
  <h1 class="text-2xl font-bold">Manajemen Ujian</h1>
  <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
    <div class="card p-4">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <form method="POST" action="{{ route('superadmin.exams.store') }}" class="flex-1">
          @csrf
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            <div>
              <label class="text-xs font-bold">Paket Soal</label>
              <select name="paket_soal_id" class="input w-full" required>
                <option value="">Pilih paket</option>
                @foreach($paketSoals as $paket)
                <option value="{{ $paket->id }}">{{ $paket->nama }} &middot; {{ $paket->jenjang?->kode }} &middot;
                  {{ $paket->tahun_ajaran }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="text-xs font-bold">Judul</label>
              <input name="judul" class="input w-full" required>
            </div>
            <div>
              <label class="text-xs font-bold">Tanggal Terbit</label>
              <input type="datetime-local" name="tanggal_terbit" class="input w-full" required>
            </div>
            <div>
              <label class="text-xs font-bold">Max Peserta</label>
              <input type="number" name="max_peserta" class="input w-full" value="50" required>
            </div>
            <div>
              <label class="text-xs font-bold">Timer (menit, opsional)</label>
              <input type="number" name="timer" class="input w-full" placeholder="Auto dari paket">
            </div>
            <div>
              <label class="text-xs font-bold">Status</label>
              <select name="status" class="input w-full" required>
                <option value="draft">Draft</option>
                <option value="terbit">Terbit</option>
              </select>
            </div>
          </div>
          <button class="btn-primary mt-3 w-full sm:w-auto" type="submit">Buat Ujian</button>
        </form>
        <button class="btn-primary mt-3 md:mt-0 w-full md:w-auto" type="button"
          onclick="document.getElementById('modal-import').classList.remove('hidden')">
          <i class="fa-solid fa-upload mr-2"></i>Import
        </button>
      </div>
    </div>

    <!-- Modal Import -->
    <div id="modal-import" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden">
      <div class="relative w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="font-bold text-lg">Import Ujian</div>
            <button class="text-gray-500 hover:text-gray-700"
              onclick="document.getElementById('modal-import').classList.add('hidden')">
              <i class="fa-solid fa-times"></i>
            </button>
          </div>
          <p class="mb-4 text-sm text-gray-600">Download template Excel, isi data ujian, lalu upload kembali.</p>
          <form method="POST" action="{{ route('superadmin.exams.import') }}" enctype="multipart/form-data"
            class="flex flex-col gap-3">
            @csrf
            <input class="input" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
            <button class="btn-primary" type="submit">
              <i class="fa-solid fa-upload mr-2"></i>Import File
            </button>
          </form>
          <a href="{{ route('superadmin.exams.template') }}" class="btn-secondary mt-2">
            <i class="fa-solid fa-file-excel mr-2"></i>Download Template Excel
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- ─── Tabel Ujian & Token Per Mapel ─────────────────────────────────────── --}}
  <div class="card p-4">
    <div class="table-container">
      <table class="table-ujion w-full min-w-[760px]">
        <thead>
          <tr>
            <th>Judul</th>
            <th>Paket</th>
            <th>Tanggal Terbit</th>
            <th>Max</th>
            <th>Token per Mapel</th>
            <th>Status</th>
            <th>Aktif</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($exams as $exam)
          <tr>
            <td>{{ $exam->judul }}</td>
            <td>{{ $exam->paketSoal?->nama ?? '-' }}</td>
            <td>{{ $exam->tanggal_terbit->format('d M Y H:i') }}</td>
            <td>{{ $exam->max_peserta }}</td>
            <td>
              @if($exam->examMapelTokens->isEmpty())
              <span class="badge-warning text-xs">Belum ada token</span>
              @else
              <div class="space-y-1.5">
                @foreach($exam->examMapelTokens as $mt)
                <div class="flex items-center gap-2">
                  <span class="text-[10px] font-bold text-textSecondary w-20 truncate">
                    {{ $mt->mapelPaket?->nama_label ?? 'Mapel' }}
                  </span>
                  <span id="token-sa-{{ $mt->id }}"
                    class="rounded-lg border border-primary/30 bg-primary/10 px-2 py-0.5 font-mono text-sm font-bold tracking-widest text-primary">
                    {{ $mt->token }}
                  </span>
                  <button type="button" id="copy-sa-{{ $mt->id }}"
                    onclick="copyMapelToken('{{ $mt->token }}', {{ $mt->id }})"
                    class="btn-secondary px-2 py-1 text-[11px]">
                    <i class="fa-solid fa-copy"></i>
                  </button>
                </div>
                @endforeach
              </div>
              @endif
            </td>
            <td>{{ $exam->status }}</td>
            <td>
              @if($exam->is_active)
              <span class="badge-success">Aktif</span>
              @else
              <span class="badge-danger">Nonaktif</span>
              @endif
            </td>
            <td>
              <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('superadmin.exams.toggle', $exam) }}">@csrf<button
                    class="btn-secondary text-xs px-2 py-1">Toggle</button></form>
                <form method="POST" action="{{ route('superadmin.exams.destroy', $exam) }}">@csrf<button
                    class="btn-danger text-xs px-2 py-1">Hapus</button></form>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script>
function copyMapelToken(token, id) {
  const doCopy = () => {
    const btn = document.getElementById('copy-sa-' + id);
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check"></i>';
    btn.classList.add('text-emerald-600');
    setTimeout(() => {
      btn.innerHTML = orig;
      btn.classList.remove('text-emerald-600');
    }, 2000);
  };

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(token).then(doCopy);
  } else {
    const textArea = document.createElement("textarea");
    textArea.value = token;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
      document.execCommand('copy');
      doCopy();
    } catch (err) {
      console.error('Fallback copy failed', err);
    }
    document.body.removeChild(textArea);
  }
}
</script>
@endpush
@endsection