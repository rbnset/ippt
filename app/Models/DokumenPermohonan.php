<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPermohonan extends Model
{
    use HasFactory;

    protected $table = 'dokumen_permohonan';

    protected $fillable = [
        'permohonan_id',
        'diunggah_oleh',
        'jenis_dokumen',
        'nama_file',
        'lokasi_file',
        'tipe_file',
        'ukuran_file',
        'status',
        'catatan',
    ];


    protected function casts(): array
    {
        return [
            'jenis_dokumen' => \App\Enums\JenisDokumen::class,
            'status' => \App\Enums\StatusDokumen::class,
            'ukuran_file' => 'integer',
        ];
    }

    /**
     * Permohonan.
     */
    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(
            Permohonan::class,
            'permohonan_id'
        );
    }

    /**
     * User yang mengunggah dokumen.
     */
    public function diunggahOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'diunggah_oleh'
        );
    }
}
