<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemeriksaan_lapangan', function (Blueprint $table) {
            $table->unique('nomor_bap', 'pemeriksaan_lapangan_nomor_bap_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pemeriksaan_lapangan', function (Blueprint $table) {
            $table->dropUnique('pemeriksaan_lapangan_nomor_bap_unique');
        });
    }
};
