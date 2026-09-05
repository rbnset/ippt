<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekomendasiTeknis extends Model
{
    use HasFactory;

    protected $table = 'rekomendasi_teknis';

    protected $fillable = [
        'permohonan_id',
        'nomor_rekomendasi',
        'tanggal_rekomendasi',
        'hasil',
        'pertimbangan',
        'ketentuan',
        'disusun_oleh',
        'direview_oleh',
        'disetujui_oleh',
        'lokasi_file',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hasil' => \App\Enums\HasilRekomendasi::class,
            'status' => \App\Enums\StatusPersetujuan::class,
            'tanggal_rekomendasi' => 'date',
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
     * User yang menyusun rekomendasi.
     */
    public function disusunOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'disusun_oleh'
        );
    }

    /**
     * User yang melakukan review.
     */
    public function direviewOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'direview_oleh'
        );
    }

    /**
     * User yang menyetujui rekomendasi.
     */
    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'disetujui_oleh'
        );
    }
}
