<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keputusan_ippt', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('permohonan_id');

            // User yang menyusun keputusan
            $table->unsignedBigInteger('disusun_oleh')
                ->nullable();

            // Pejabat yang menyetujui keputusan
            $table->unsignedBigInteger('disetujui_oleh')
                ->nullable();

            $table->string('nomor_keputusan', 50)
                ->nullable()
                ->unique();

            $table->date('tanggal_keputusan')
                ->nullable();

            /*
             * terbit
             * tolak
             */
            $table->string('jenis_keputusan', 10);

            /*
             * draf
             * diajukan
             * disetujui
             * ditolak
             */
            $table->string('status', 20)
                ->default('draf')
                ->index();

            // Alasan jika ditolak
            $table->text('alasan')
                ->nullable();

            // File PDF keputusan
            $table->string('lokasi_file', 255)
                ->nullable();

            $table->timestamps();

            $table->foreign('permohonan_id')
                ->references('id')
                ->on('permohonan')
                ->cascadeOnDelete();

            $table->foreign('disusun_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('disetujui_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['permohonan_id', 'status'],
                'keputusan_permohonan_status_index'
            );

            $table->index(
                ['tanggal_keputusan', 'jenis_keputusan'],
                'keputusan_tanggal_jenis_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keputusan_ippt');
    }
};
