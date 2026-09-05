<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekomendasi_teknis', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('permohonan_id');

            $table->string('nomor_rekomendasi', 50)
                ->nullable()
                ->unique();

            $table->date('tanggal_rekomendasi')
                ->nullable();

            /*
             * disetujui
             * tidak_disetujui
             */
            $table->string('hasil', 30)
                ->nullable();

            $table->text('pertimbangan')
                ->nullable();

            $table->text('ketentuan')
                ->nullable();

            // Penyusun rekomendasi
            $table->unsignedBigInteger('disusun_oleh')
                ->nullable();

            // Reviewer
            $table->unsignedBigInteger('direview_oleh')
                ->nullable();

            // Pejabat yang menyetujui rekomendasi
            $table->unsignedBigInteger('disetujui_oleh')
                ->nullable();

            // File PDF rekomendasi
            $table->string('lokasi_file', 255)
                ->nullable();

            /*
             * draf
             * diajukan
             * direview
             * disetujui
             * ditolak
             */
            $table->string('status', 20)
                ->default('draf')
                ->index();

            $table->timestamps();

            $table->foreign('permohonan_id')
                ->references('id')
                ->on('permohonan')
                ->cascadeOnDelete();

            $table->foreign('disusun_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('direview_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('disetujui_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['permohonan_id', 'status'],
                'rekomendasi_permohonan_status_index'
            );

            $table->index(
                ['tanggal_rekomendasi', 'status'],
                'rekomendasi_tanggal_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_teknis');
    }
};
