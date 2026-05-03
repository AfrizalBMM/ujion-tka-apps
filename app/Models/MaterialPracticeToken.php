<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialPracticeToken extends Model
{
    protected $fillable = [
        'material_id',
        'token',
        'jumlah_soal_per_paket',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'jumlah_soal_per_paket' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->token)) {
                $model->token = self::generateUniqueToken();
            }
        });
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(MaterialPracticePackage::class, 'material_practice_token_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MaterialPracticeSession::class, 'material_practice_token_id');
    }

    public static function generateUniqueToken(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = strtoupper(Str::random(8));
            if (! self::query()->where('token', $candidate)->exists()) {
                return $candidate;
            }
        }
        abort(500, 'Gagal generate token latihan materi unik.');
    }

    /**
     * Regenerate snapshot 3 paket latihan (random dari bank soal global berdasarkan material).
     *
     * - Hanya mengambil soal PG (question_type=multiple_choice)
     * - Hanya mengambil soal aktif (is_active=true)
     */
    public function regeneratePackages(): void
    {
        $jumlah = (int) ($this->jumlah_soal_per_paket ?: 10);

        DB::transaction(function () use ($jumlah): void {
            // Hapus snapshot lama
            $this->packages()->each(function (MaterialPracticePackage $package) {
                $package->questions()->detach();
                $package->delete();
            });

            $poolIds = GlobalQuestion::query()
                ->where('is_active', true)
                ->where('question_type', 'multiple_choice')
                ->forMaterial($this->material()->firstOrFail())
                ->pluck('id')
                ->values()
                ->all();

            if (count($poolIds) < $jumlah) {
                throw new \RuntimeException('Bank soal PG aktif untuk materi ini belum mencukupi untuk membuat paket latihan.');
            }

            $usedAcrossPackages = [];

            for ($paketNo = 1; $paketNo <= 3; $paketNo++) {
                $package = $this->packages()->create(['paket_no' => $paketNo]);

                $available = array_values(array_diff($poolIds, $usedAcrossPackages));
                if (count($available) < $jumlah) {
                    // Tidak cukup unik antar paket, izinkan reuse antar paket.
                    $available = $poolIds;
                    $usedAcrossPackages = [];
                }

                // Sample unik untuk paket ini
                shuffle($available);
                $picked = array_slice($available, 0, $jumlah);
                $usedAcrossPackages = array_values(array_unique(array_merge($usedAcrossPackages, $picked)));

                foreach ($picked as $index => $globalQuestionId) {
                    $package->questions()->attach($globalQuestionId, ['urutan' => $index + 1]);
                }
            }
        });
    }
}
