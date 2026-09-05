<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemeriksaan_lapangan', function (Blueprint $table) {
            $table->unsignedInteger('versi')->default(1)->after('status');
            $table->unsignedBigInteger('revisi_dari_id')->nullable()->after('versi');
            $table->text('alasan_pembaruan')->nullable()->after('revisi_dari_id');
            $table->index(['permohonan_id', 'versi'], 'pemeriksaan_permohonan_versi_index');
            $table->foreign('revisi_dari_id')
                ->references('id')
                ->on('pemeriksaan_lapangan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pemeriksaan_lapangan', function (Blueprint $table) {
            $table->dropForeign(['revisi_dari_id']);
            $table->dropIndex('pemeriksaan_permohonan_versi_index');
            $table->dropColumn(['versi', 'revisi_dari_id', 'alasan_pembaruan']);
        });
    }
};
