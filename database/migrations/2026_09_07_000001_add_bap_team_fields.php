<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemeriksaan_lapangan', function (Blueprint $table): void {
            $table->unsignedBigInteger('ketua_tim_id')->nullable()->after('dibuat_oleh');
            $table->json('anggota_tim_ids')->nullable()->after('ketua_tim_id');
        });

        Schema::table('pemeriksaan_lapangan', function (Blueprint $table): void {
            $table->foreign('ketua_tim_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pemeriksaan_lapangan', function (Blueprint $table): void {
            $table->dropForeign(['ketua_tim_id']);
            $table->dropColumn(['ketua_tim_id', 'anggota_tim_ids']);
        });
    }
};
