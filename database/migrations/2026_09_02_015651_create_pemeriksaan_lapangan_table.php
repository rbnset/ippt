<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemeriksaan_lapangan', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('permohonan_id');

            // Petugas yang membuat pemeriksaan
            $table->unsignedBigInteger('dibuat_oleh');

            $table->date('tanggal_pemeriksaan')
                ->nullable();

            $table->text('hasil_pemeriksaan')
                ->nullable();

            /*
             * sesuai
             * tidak_sesuai
             */
            $table->string('hasil', 20)
                ->nullable();

            // File PDF BAP
            $table->string('lokasi_file_bap', 255)
                ->nullable();

            $table->timestamps();

            $table->foreign('permohonan_id')
                ->references('id')
                ->on('permohonan')
                ->cascadeOnDelete();

            $table->foreign('dibuat_oleh')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index(
                ['permohonan_id', 'tanggal_pemeriksaan'],
                'pemeriksaan_permohonan_tanggal_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_lapangan');
    }
};
