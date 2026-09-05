<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RisalahPertimbangan extends Model
{
    use HasFactory;

    protected $table = 'risalah_pertimbangan';

    protected $fillable = [
        'permohonan_id',
        'diterima_oleh',
        'nomor_risalah',
        'tanggal_risalah',
        'hasil',
        'lokasi_file',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'hasil' => \App\Enums\HasilRisalah::class,
            'tanggal_risalah' => 'date',
        ];
    }


    /**
     * Permohonan.
     */
    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    /**
     * User yang menerima risalah.
     */
    public function diterimaOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'diterima_oleh'
        );
    }
}
