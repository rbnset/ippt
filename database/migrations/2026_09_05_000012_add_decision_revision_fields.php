<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keputusan_ippt', function (Blueprint $table): void {
            $table->unsignedInteger('versi')->default(1)->after('nomor_keputusan');
            $table->foreignId('revisi_dari_id')->nullable()->after('versi')->constrained('keputusan_ippt')->nullOnDelete();
            $table->text('alasan_koreksi')->nullable()->after('revisi_dari_id');
            $table->foreignId('dikoreksi_oleh')->nullable()->after('alasan_koreksi')->constrained('users')->nullOnDelete();
            $table->timestamp('dikoreksi_pada')->nullable()->after('dikoreksi_oleh');
            $table->boolean('is_current')->default(true)->after('lokasi_file')->index();

            $table->index(['permohonan_id', 'is_current'], 'keputusan_permohonan_current_index');
        });
    }

    public function down(): void
    {
        Schema::table('keputusan_ippt', function (Blueprint $table): void {
            $table->dropIndex('keputusan_permohonan_current_index');
            $table->dropConstrainedForeignId('dikoreksi_oleh');
            $table->dropColumn(['dikoreksi_pada', 'alasan_koreksi', 'is_current', 'versi']);
            $table->dropConstrainedForeignId('revisi_dari_id');
        });
    }
};
