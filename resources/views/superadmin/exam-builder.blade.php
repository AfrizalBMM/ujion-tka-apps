@extends('layouts.superadmin')
@section('title', 'Builder Soal Ujian')
@section('content')
<div id="builder-app" class="fixed inset-0 bg-gray-900/90 z-50 flex flex-col">
    <div class="flex items-center justify-between p-4 bg-white shadow">
        <h2 class="font-bold text-xl">Builder Soal Ujian: {{ $exam->judul }}</h2>
        <a href="{{ route('superadmin.exams.index') }}" class="btn-secondary">Keluar</a>
    </div>
    <div class="flex-1 flex overflow-hidden">
        <div class="w-1/4 bg-white p-4 overflow-y-auto border-r">
            <div class="mb-4 font-bold">Daftar Soal</div>
            <ul>
                <li v-for="(q, idx) in questions" :key="idx" :class="{'font-bold text-blue-600': idx === current}">
                    <button @click="go(idx)">Soal @{{ idx+1 }}</button>
                </li>
            </ul>
            <button class="btn-primary mt-4 w-full" @click="add">+ Tambah Soal</button>
            <form method="POST" action="{{ route('superadmin.exams.import-bank', $exam) }}" class="mt-6">
                @csrf
                <div class="font-bold mb-2">Import dari Bank Soal</div>
                <select name="question_ids[]" multiple class="input w-full h-32">
                    @foreach($bankQuestions as $bq)
                        <option value="{{ $bq->id }}">{{ $bq->pertanyaan }}</option>
                    @endforeach
                </select>
                <button class="btn-secondary mt-2 w-full">Import</button>
            </form>
        </div>
        <div class="flex-1 p-8 overflow-y-auto">
            <form @submit.prevent="save">
                <div v-if="questions.length">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="font-bold">Soal @{{ current+1 }} dari @{{ questions.length }}</span>
                        <div>
                            <button type="button" class="btn-secondary mr-2" @click="prev" :disabled="current===0">Prev</button>
                            <button type="button" class="btn-secondary" @click="next" :disabled="current===questions.length-1">Next</button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Tipe Soal</label>
                        <select v-model="questions[current].tipe" class="input w-full">
                            <option value="PG">Pilihan Ganda</option>
                            <option value="Checklist">Checklist</option>
                            <option value="Singkat">Jawaban Singkat</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Pertanyaan</label>
                        <textarea v-model="questions[current].pertanyaan" class="input w-full" rows="3"></textarea>
                    </div>
                    <div class="mb-4" v-if="['PG','Checklist'].includes(questions[current].tipe)">
                        <label class="text-xs font-bold">Opsi Jawaban</label>
                        <div v-for="(opsi, i) in questions[current].opsi" :key="i" class="flex gap-2 mb-1">
                            <input v-model="questions[current].opsi[i]" class="input flex-1">
                            <button type="button" class="btn-danger btn-xs" @click="removeOpsi(i)">Hapus</button>
                        </div>
                        <button type="button" class="btn-secondary btn-xs mt-1" @click="addOpsi">+ Opsi</button>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Jawaban Benar</label>
                        <input v-model="questions[current].jawaban_benar" class="input w-full">
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Pembahasan</label>
                        <textarea v-model="questions[current].pembahasan" class="input w-full"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Gambar (URL)</label>
                        <input v-model="questions[current].image" class="input w-full">
                        <div v-if="questions[current].image" class="mt-2"><img :src="questions[current].image" class="max-h-40"></div>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Preview Soal</label>
                        <div class="p-4 border bg-gray-50 rounded">
                            <div v-html="previewSoal(questions[current])"></div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn-danger" @click="remove(current)" v-if="questions.length>1">Hapus Soal Ini</button>
                        <button type="submit" class="btn-primary">Simpan Semua Soal</button>
                    </div>
                </div>
                <div v-else>
                    <div class="text-center text-gray-500">Belum ada soal. Klik tambah soal.</div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
<script>
new Vue({
    el: '#builder-app',
    data: {
        questions: @json($questions->map(fn($q)=>[
            'tipe'=>$q->tipe,
            'pertanyaan'=>$q->pertanyaan,
            'opsi'=>$q->opsi ?? [],
            'jawaban_benar'=>$q->jawaban_benar,
            'pembahasan'=>$q->pembahasan,
            'image'=>$q->image_path,
        ])),
        current: 0,
    },
    methods: {
        go(idx){ this.current=idx; },
        next(){ if(this.current<this.questions.length-1) this.current++; },
        prev(){ if(this.current>0) this.current--; },
        add(){ this.questions.push({tipe:'PG',pertanyaan:'',opsi:[''],jawaban_benar:'',pembahasan:'',image:''}); this.current=this.questions.length-1; },
        remove(idx){ this.questions.splice(idx,1); if(this.current>=this.questions.length) this.current=this.questions.length-1; },
        addOpsi(){ this.questions[this.current].opsi.push(''); },
        removeOpsi(i){ this.questions[this.current].opsi.splice(i,1); },
        previewSoal(q){
            let html = `<b>${q.pertanyaan}</b><br>`;
            if(['PG','Checklist'].includes(q.tipe)){
                html += '<ul>' + (q.opsi||[]).map(o=>`<li>${o}</li>`).join('') + '</ul>';
            }
            if(q.image){ html += `<img src='${q.image}' class='max-h-32'>`; }
            return html;
        },
        save(){
            fetch("{{ route('superadmin.exams.builder.save', $exam) }}",{
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body:JSON.stringify({questions:this.questions})
            }).then(()=>location.reload());
        }
    }
});
</script>
@endsection