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
        'hasil_pertimbangan',
        'lokasi_file',
        'versi',
        'revisi_dari_id',
        'alasan_koreksi',
        'dikoreksi_oleh',
        'dikoreksi_pada',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'jenis_keputusan' => \App\Enums\JenisKeputusan::class,
            'status' => \App\Enums\StatusPersetujuan::class,
            'tanggal_keputusan' => 'date',
            'dikoreksi_pada' => 'datetime',
            'is_current' => 'boolean',
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
    public function revisiDari(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revisi_dari_id');
    }

    public function revisi(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'revisi_dari_id');
    }

    public function dikoreksiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh');
    }

}
