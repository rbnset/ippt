<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keputusan_ippt', function (Blueprint $table): void {
            $table->text('hasil_pertimbangan')->nullable()->after('alasan');
        });
    }

    public function down(): void
    {
        Schema::table('keputusan_ippt', function (Blueprint $table): void {
            $table->dropColumn('hasil_pertimbangan');
        });
    }
};
