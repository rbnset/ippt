<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekomendasi_teknis', function (Blueprint $table) {
            $table->text('dasar_hukum')->nullable()->after('tanggal_rekomendasi');
            $table->string('nomor_bap_referensi', 100)->nullable()->after('dasar_hukum');
            $table->text('kesesuaian_tata_ruang')->nullable()->after('pertimbangan');
            $table->text('arahan_teknis')->nullable()->after('kesesuaian_tata_ruang');
            $table->text('catatan_review')->nullable()->after('ketentuan');
            $table->string('generated_pdf_path', 255)->nullable()->after('lokasi_file');
            $table->timestamp('diajukan_pada')->nullable()->after('generated_pdf_path');
            $table->timestamp('disetujui_pada')->nullable()->after('diajukan_pada');
            $table->timestamp('ditolak_pada')->nullable()->after('disetujui_pada');
            $table->unsignedBigInteger('ditolak_oleh')->nullable()->after('ditolak_pada');

            $table->foreign('ditolak_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rekomendasi_teknis', function (Blueprint $table) {
            $table->dropForeign(['ditolak_oleh']);
            $table->dropColumn([
                'dasar_hukum', 'nomor_bap_referensi', 'kesesuaian_tata_ruang', 'arahan_teknis',
                'catatan_review', 'generated_pdf_path', 'diajukan_pada', 'disetujui_pada',
                'ditolak_pada', 'ditolak_oleh',
            ]);
        });
    }
};
