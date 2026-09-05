<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekomendasiTeknis extends Model
{
    use HasFactory;

    protected $table = 'rekomendasi_teknis';

    protected $fillable = [
        'permohonan_id', 'nomor_rekomendasi', 'tanggal_rekomendasi', 'dasar_hukum', 'nomor_bap_referensi',
        'hasil', 'pertimbangan', 'kesesuaian_tata_ruang', 'arahan_teknis', 'ketentuan', 'catatan_review',
        'disusun_oleh', 'direview_oleh', 'disetujui_oleh', 'ditolak_oleh', 'lokasi_file', 'generated_pdf_path',
        'status', 'diajukan_pada', 'disetujui_pada', 'ditolak_pada',
    ];

    protected function casts(): array
    {
        return [
            'hasil' => \App\Enums\HasilRekomendasi::class,
            'status' => \App\Enums\StatusPersetujuan::class,
            'tanggal_rekomendasi' => 'date',
            'diajukan_pada' => 'datetime',
            'disetujui_pada' => 'datetime',
            'ditolak_pada' => 'datetime',
        ];
    }

    public function permohonan(): BelongsTo { return $this->belongsTo(Permohonan::class); }
    public function disusunOleh(): BelongsTo { return $this->belongsTo(User::class, 'disusun_oleh'); }
    public function direviewOleh(): BelongsTo { return $this->belongsTo(User::class, 'direview_oleh'); }
    public function disetujuiOleh(): BelongsTo { return $this->belongsTo(User::class, 'disetujui_oleh'); }
    public function ditolakOleh(): BelongsTo { return $this->belongsTo(User::class, 'ditolak_oleh'); }
}
