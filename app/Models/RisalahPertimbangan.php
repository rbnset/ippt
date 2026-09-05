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
        'dasar_penerbitan',
        'nomor_ba_peninjauan',
        'tanggal_ba_peninjauan',
        'nomor_ba_pembahasan',
        'tanggal_ba_pembahasan',
        'hasil',
        'pertimbangan_penguasaan_pemilikan',
        'ketentuan_syarat',
        'indikasi_sengketa',
        'pengakuan_hak',
        'kemampuan_tanah',
        'keterangan_lain',
        'lokasi_file',
        'lokasi_file_peta',
        'catatan',
        'status',
        'diminta_oleh',
        'diminta_pada',
        'diterima_pada',
    ];

    protected function casts(): array
    {
        return [
            'hasil' => \App\Enums\HasilRisalah::class,
            'tanggal_risalah' => 'date',
            'tanggal_ba_peninjauan' => 'date',
            'tanggal_ba_pembahasan' => 'date',
            'diminta_pada' => 'datetime',
            'diterima_pada' => 'datetime',
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
    public function dimintaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diminta_oleh');
    }

    public function diterimaOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'diterima_oleh'
        );
    }
}
