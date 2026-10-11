<?php

namespace Tests\Feature;

use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\Material;
use App\Models\PaketSoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Inertia;
use Tests\TestCase;

class BankSoalMapelFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private PaketSoal $paket;

    private MapelPaket $mapel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $sd = Jenjang::firstOrCreate(['kode' => 'SD'], ['nama' => 'Sekolah Dasar', 'urutan' => 1]);

        $this->paket = PaketSoal::create([
            'jenjang_id' => $sd->id,
            'nama' => 'Paket SD Mapel Filter',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
            'created_by' => $this->superadmin->id,
        ]);

        $this->mapel = MapelPaket::create([
            'paket_soal_id' => $this->paket->id,
            'nama_mapel' => MapelPaket::NAMA_BAHASA_INDONESIA,
            'kategori_komponen' => 'akademik',
            'mode_penilaian' => 'score',
            'kode_komponen' => 'BIN',
            'is_wajib' => true,
            'jumlah_soal' => 30,
            'durasi_menit' => 75,
            'urutan' => 1,
        ]);
    }

    private function gq(string $label, ?string $mapel, ?string $curriculum = null): GlobalQuestion
    {
        return GlobalQuestion::create([
            'jenjang_id' => $this->paket->jenjang_id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Soal '.$label,
            'options' => ['1', '2', '3'],
            'answer_key' => '2',
            'is_active' => true,
            'material_mapel' => $mapel,
            'material_curriculum' => $curriculum,
        ]);
    }

    private function inertiaHeaders(string $url): array
    {
        $this->get($url);

        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
        ];
    }

    public function test_normalize_mapel_key_menyeragamkan_underscore_dan_kapital(): void
    {
        $this->assertSame('bahasa indonesia', GlobalQuestion::normalizeMapelKey('Bahasa Indonesia'));
        $this->assertSame('bahasa indonesia', GlobalQuestion::normalizeMapelKey('bahasa_indonesia'));
        $this->assertSame('bahasa indonesia', GlobalQuestion::normalizeMapelKey('  Bahasa_Indonesia  '));
        $this->assertSame('', GlobalQuestion::normalizeMapelKey(null));
    }

    public function test_scope_where_material_mapel_match_lintas_format(): void
    {
        $underscore = $this->gq('bhs underscore', 'bahasa_indonesia');
        $titleCase = $this->gq('bhs title case', 'Bahasa Indonesia');
        $lower = $this->gq('mat lower', 'matematika');

        $found = GlobalQuestion::whereMaterialMapel('Bahasa Indonesia')->pluck('id')->all();

        $this->assertContains($underscore->id, $found);
        $this->assertContains($titleCase->id, $found);
        $this->assertNotContains($lower->id, $found, 'mapel berbeda tidak boleh ikut match');

        $this->assertSame([$lower->id], GlobalQuestion::whereMaterialMapel('Matematika')->pluck('id')->all());
    }

    public function test_scope_tidak_match_null_mapel_dan_huruf_berbeda(): void
    {
        $nullMapel = $this->gq('mapel kosong', null);
        $numerasi = $this->gq('numerasi', 'Numerasi');

        $found = GlobalQuestion::whereMaterialMapel('Matematika')->pluck('id')->all();

        $this->assertNotContains($nullMapel->id, $found);
        $this->assertNotContains($numerasi->id, $found);
    }

    public function test_bank_builder_menampilkan_soal_walau_format_mapel_beda(): void
    {
        $underscore = $this->gq('bhs underscore', 'bahasa_indonesia');
        $titleCase = $this->gq('bhs title case', 'Bahasa Indonesia');
        $lain = $this->gq('numerasi', 'Numerasi');

        $url = route('superadmin.soal.bank-builder', [$this->paket, $this->mapel]).'?material_mapel='.urlencode('Bahasa Indonesia');

        $response = $this->actingAs($this->superadmin)->get($url, $this->inertiaHeaders($url));
        $response->assertOk();

        $ids = collect($response->json('props.bankSoals'))->pluck('id')->all();

        $this->assertContains($underscore->id, $ids);
        $this->assertContains($titleCase->id, $ids);
        $this->assertNotContains($lain->id, $ids);
    }

    public function test_global_question_index_filter_mapel_lintas_format(): void
    {
        $underscore = $this->gq('bhs underscore', 'bahasa_indonesia');
        $lain = $this->gq('mat', 'Matematika');

        $url = route('superadmin.global-questions.index').'?material_mapel='.urlencode('Bahasa Indonesia');

        $response = $this->actingAs($this->superadmin)->get($url, $this->inertiaHeaders($url));
        $response->assertOk();

        $ids = collect($response->json('props.globalQuestions.data'))->pluck('id')->all();

        $this->assertContains($underscore->id, $ids);
        $this->assertNotContains($lain->id, $ids);
    }

    public function test_material_bank_count_menghitung_mapel_format_beda(): void
    {
        Material::create([
            'jenjang' => 'SD',
            'mapel' => 'Bahasa Indonesia',
            'curriculum' => 'Merdeka',
            'subelement' => 'Literasi',
            'unit' => 'Teks Narasi',
            'sub_unit' => 'Ide pokok',
        ]);

        $this->gq('bhs underscore', 'bahasa_indonesia', 'Merdeka');

        $url = route('superadmin.materials.index');

        $response = $this->actingAs($this->superadmin)->get($url, $this->inertiaHeaders($url));
        $response->assertOk();

        $row = collect($response->json('props.materials'))->firstWhere('mapel', 'Bahasa Indonesia');

        $this->assertNotNull($row);
        $this->assertSame(1, $row['bank_question_count']);
    }

    public function test_guru_bank_soal_mapel_underscore_terlihat(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'jenjang' => 'SD',
        ]);

        $this->paket->update(['created_by' => $guru->id]);

        $underscore = $this->gq('bhs underscore', 'bahasa_indonesia');

        $url = route('guru.soal.index', [$this->paket, $this->mapel]);

        $response = $this->actingAs($guru)->get($url, $this->inertiaHeaders($url));
        $response->assertOk();

        $ids = collect($response->json('props.bankSoals'))->pluck('id')->all();

        $this->assertContains($underscore->id, $ids);
    }
}
