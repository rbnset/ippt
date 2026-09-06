<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pemohon extends Model
{
    use HasFactory;

    protected $table = 'pemohon';

    protected $casts = [
        'diverifikasi_pada' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'jenis_pemohon',
        'nama',
        'nik',
        'nib',
        'npwp',
        'nomor_telepon',
        'email',
        'alamat',
        'kelurahan',
        'kecamatan',
        'kota',
        'status_verifikasi',
        'catatan_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_pada',
    ];

    /**
     * User akun pemohon.
     */
    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Daftar permohonan milik pemohon.
     */
    public function isTerverifikasi(): bool
    {
        return $this->status_verifikasi === 'terverifikasi';
    }

    public function isEditableByPemohon(): bool
    {
        return in_array($this->status_verifikasi, ['menunggu_verifikasi', 'perlu_perbaikan'], true);
    }

    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class);
    }
}
