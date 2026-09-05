<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeputusanIppt extends Model
{
    protected $table = 'keputusan_ippt';

    protected $fillable = [
        'permohonan_id',
        'disusun_oleh',
        'disetujui_oleh',
        'nomor_keputusan',
        'tanggal_keputusan',
        'jenis_keputusan',
        'status',
        'alasan',
        'lokasi_file',
    ];

    protected function casts(): array
    {
        return [
            'jenis_keputusan' => \App\Enums\JenisKeputusan::class,
            'status' => \App\Enums\StatusPersetujuan::class,
            'tanggal_keputusan' => 'date',
        ];
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(
            Permohonan::class,
            'permohonan_id'
        );
    }

    public function disusunOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'disusun_oleh'
        );
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'disetujui_oleh'
        );
    }
}
