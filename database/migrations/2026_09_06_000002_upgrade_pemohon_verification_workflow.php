<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemohon', function (Blueprint $table): void {
            $table->string('nomor_antrian', 40)->nullable()->unique()->after('id');
            $table->timestamp('diajukan_pada')->nullable()->after('status_verifikasi');
            $table->unsignedInteger('versi_data')->default(1)->after('diajukan_pada');
            $table->text('alasan_perubahan')->nullable()->after('catatan_verifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('pemohon', function (Blueprint $table): void {
            $table->dropColumn(['nomor_antrian', 'diajukan_pada', 'versi_data', 'alasan_perubahan']);
        });
    }
};
