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
    ];

    /**
     * User akun pemohon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Daftar permohonan milik pemohon.
     */
    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class);
    }
}
