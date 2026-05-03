<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalQuestion extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'jenjang_id' => 'integer',
        'material_id' => 'integer',
        'created_by' => 'integer',
        'reading_passage' => 'string',
    ];

    public function jenjang()
    {
        return $this->belongsTo(Jenjang::class, 'jenjang_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function scopeForMaterial(Builder $query, Material $material): Builder
    {
        return $query->where(function (Builder $q) use ($material): void {
            $q->where('material_id', $material->id)
                ->orWhere(function (Builder $inner) use ($material): void {
                    $inner->whereNull('material_id');

                    // Fallback matching for unlinked questions: match by mapel + curriculum.
                    // Snapshot fields (subelement/unit/sub_unit) can vary across imports, so we don't require them here.
                    $inner->where('material_mapel', $material->mapel)
                        ->where('material_curriculum', $material->curriculum);
                });
        });
    }
}
