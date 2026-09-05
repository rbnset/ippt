<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_permohonan', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('permohonan_id');

            $table->unsignedBigInteger('diunggah_oleh')
                ->nullable();

            $table->string('jenis_dokumen', 50);

            $table->string('nama_file', 180);

            $table->string('lokasi_file', 255);

            $table->string('tipe_file', 10)
                ->default('pdf');

            $table->unsignedInteger('ukuran_file')
                ->nullable();

            $table->string('status', 20)
                ->default('menunggu')
                ->index();

            $table->text('catatan')
                ->nullable();

            $table->timestamps();

            // Foreign key permohonan
            $table->foreign('permohonan_id')
                ->references('id')
                ->on('permohonan')
                ->cascadeOnDelete();

            // Foreign key user
            $table->foreign('diunggah_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Index
            $table->index(
                ['permohonan_id', 'jenis_dokumen'],
                'dokumen_permohonan_jenis_index'
            );

            $table->index(
                ['permohonan_id', 'status'],
                'dokumen_permohonan_permohonan_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_permohonan');
    }
};
