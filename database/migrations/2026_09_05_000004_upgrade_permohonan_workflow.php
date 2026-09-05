<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table): void {
            $table->boolean('diwakilkan')->default(false)->after('pemohon_id');
            $table->string('nama_pemegang_kuasa', 150)->nullable()->after('diwakilkan');
            $table->string('nik_pemegang_kuasa', 16)->nullable()->after('nama_pemegang_kuasa');
        });
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table): void {
            $table->dropColumn([
                'diwakilkan',
                'nama_pemegang_kuasa',
                'nik_pemegang_kuasa',
            ]);
        });
    }
};
