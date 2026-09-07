<?php

namespace App\Models;

use App\Enums\StatusPermohonan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;


class Permohonan extends Model
{
    use HasFactory;

    protected $table = 'permohonan';

    protected static string $relationship = 'dokumenPermohonan';

    protected $fillable = [
        'pemohon_id',
        'diwakilkan',
        'nama_pemegang_kuasa',
        'nik_pemegang_kuasa',
        'nomor_permohonan',
        'tanggal_permohonan',
        'status',
        'lokasi_tanah',
        'luas_tanah',
        'nomor_hak',
        'penggunaan_sekarang',
        'penggunaan_dimohonkan',
        'keterangan',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'direview_teknis_oleh',
        'direview_teknis_pada',
        'disetujui_rekomendasi_oleh',
        'disetujui_rekomendasi_pada',
        'disetujui_keputusan_oleh',
        'disetujui_keputusan_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_permohonan' => 'date',
            'diwakilkan' => 'boolean',
            'luas_tanah' => 'decimal:2',
            'status' => StatusPermohonan::class,

            'diverifikasi_pada' => 'datetime',
            'direview_teknis_pada' => 'datetime',
            'disetujui_rekomendasi_pada' => 'datetime',
            'disetujui_keputusan_pada' => 'datetime',
        ];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('admin') => $query,

            $user->hasRole('pemohon') => $query->whereHas(
                'pemohon',
                fn($q) => $q->where('user_id', $user->id)
            ),

            $user->hasRole('staff') => $query->whereIn('status', StatusPermohonan::visibleForStaff()),
            $user->hasRole('tim_teknis') => $query->whereIn('status', StatusPermohonan::visibleForTimTeknis()),
            // Kabid berfungsi sebagai reviewer/approver rekomendasi teknis.
            // Ia perlu dapat membuka seluruh berkas Permohonan sebagai konteks
            // review, tetapi hak tulis tetap dikunci oleh Policy/Relation Manager.
            $user->hasRole('kabid') => $query,
            $user->hasRole('kadis') => $query->whereIn('status', StatusPermohonan::visibleForKadis()),

            default => $query->whereRaw('1 = 0'),
        };
    }


    /**
     * Pemohon.
     */
    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(Pemohon::class);
    }

    /**
     * Dokumen permohonan.
     */
    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenPermohonan::class);
    }

    /**
     * Pemeriksaan lapangan.
     */
    public function pemeriksaanLapangan(): HasMany
    {
        return $this->hasMany(
            PemeriksaanLapangan::class,
            'permohonan_id'
        );
    }

    /**
     * Rekomendasi teknis.
     */
    public function rekomendasiTeknis(): HasOne
    {
        return $this->hasOne(RekomendasiTeknis::class);
    }

    /**
     * Risalah pertimbangan teknis ATR/BPN.
     */
    public function risalahPertimbangan(): HasOne
    {
        return $this->hasOne(RisalahPertimbangan::class);
    }

    /**
     * Keputusan IPPT.
     */
    public function keputusanIppt(): HasOne
    {
        return $this->hasOne(KeputusanIppt::class);
    }

    /**
     * User yang melakukan verifikasi.
     */
    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    /**
     * User yang melakukan review teknis.
     */
    public function direviewTeknisOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direview_teknis_oleh');
    }

    /**
     * User yang menyetujui rekomendasi teknis.
     */
    public function disetujuiRekomendasiOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'disetujui_rekomendasi_oleh'
        );
    }

    /**
     * User yang menyetujui keputusan IPPT.
     */
    public function disetujuiKeputusanOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'disetujui_keputusan_oleh'
        );
    }

    public function dokumenPermohonan(): HasMany
    {
        return $this->hasMany(
            DokumenPermohonan::class,
            'permohonan_id'
        );
    }
}
