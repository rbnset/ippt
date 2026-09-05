<?php

namespace App\Models;

use App\Enums\HasilPemeriksaan;
use App\Enums\StatusPemeriksaan;
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
        'waktu_mulai',
        'waktu_selesai',
        'nama_tim',
        'cuaca',
        'latitude',
        'longitude',
        'alamat_lokasi',
        'checklist',
        'kondisi_eksisting',
        'hasil_pemeriksaan',
        'temuan',
        'kesimpulan',
        'rekomendasi',
        'hasil',
        'foto_lapangan',
        'status',
        'nomor_bap',
        'generated_bap_path',
        'difinalisasi_oleh',
        'difinalisasi_pada',
    ];

    protected function casts(): array
    {
        return [
            'hasil' => HasilPemeriksaan::class,
            'status' => StatusPemeriksaan::class,
            'tanggal_pemeriksaan' => 'date',
            'checklist' => 'array',
            'foto_lapangan' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'difinalisasi_pada' => 'datetime',
        ];
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function difinalisasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'difinalisasi_oleh');
    }

    public function isFinal(): bool
    {
        return $this->status === StatusPemeriksaan::Final;
    }
}
