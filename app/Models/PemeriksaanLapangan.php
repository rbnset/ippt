<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemeriksaanLapangan extends Model
{
    use HasFactory;

    protected $table = 'pemeriksaan_lapangan';

    protected $fillable = [
        'permohonan_id',
        'dibuat_oleh',
        'tanggal_pemeriksaan',
        'hasil_pemeriksaan',
        'hasil',
        'lokasi_file_bap',
    ];

    protected function casts(): array
    {
        return [
            'hasil' => \App\Enums\HasilPemeriksaan::class,
            'tanggal_pemeriksaan' => 'date',
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
     * Petugas yang membuat pemeriksaan.
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'dibuat_oleh'
        );
    }
}
