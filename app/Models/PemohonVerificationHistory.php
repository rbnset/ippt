<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemohonVerificationHistory extends Model
{
    protected $fillable = [
        'pemohon_id',
        'versi_data',
        'aksi',
        'status_sebelumnya',
        'status_sesudahnya',
        'catatan',
        'snapshot',
        'dilakukan_oleh',
        'dilakukan_pada',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'dilakukan_pada' => 'datetime',
        ];
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(Pemohon::class);
    }

    public function dilakukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilakukan_oleh');
    }
}
