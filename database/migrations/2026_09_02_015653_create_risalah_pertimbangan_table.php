<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risalah_pertimbangan', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('permohonan_id');

            // Petugas DPMPTSP/Dinas yang menerima dokumen
            $table->unsignedBigInteger('diterima_oleh')
                ->nullable();

            $table->string('nomor_risalah', 50)
                ->nullable()
                ->unique();

            $table->date('tanggal_risalah')
                ->nullable();

            /*
             * disetujui
             * tidak_disetujui
             */
            $table->string('hasil', 30)
                ->nullable();

            // File PDF dari ATR/BPN
            $table->string('lokasi_file', 255);

            $table->text('catatan')
                ->nullable();

            $table->timestamps();

            $table->foreign('permohonan_id')
                ->references('id')
                ->on('permohonan')
                ->cascadeOnDelete();

            $table->foreign('diterima_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['permohonan_id', 'tanggal_risalah'],
                'risalah_permohonan_tanggal_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risalah_pertimbangan');
    }
};
