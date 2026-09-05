<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pemohon_id');

            $table->string('nomor_permohonan', 30)->unique();

            $table->date('tanggal_permohonan');

            /*
             * Status:
             * diajukan
             * verifikasi
             * dikembalikan
             * proses_teknis
             * rekomendasi
             * menunggu_risalah
             * keputusan
             * selesai
             * ditolak
             */
            $table->string('status', 30)
                ->default('diajukan')
                ->index();

            // Data tanah
            $table->string('lokasi_tanah', 255);

            $table->decimal('luas_tanah', 10, 2);

            $table->string('nomor_hak', 50)
                ->nullable()
                ->index();

            // Penggunaan tanah
            $table->string('penggunaan_sekarang', 100);

            $table->string('penggunaan_dimohonkan', 100);

            $table->text('keterangan')->nullable();

            /*
             * Verifikasi berkas oleh petugas
             */
            $table->unsignedBigInteger('diverifikasi_oleh')
                ->nullable();

            $table->timestamp('diverifikasi_pada')
                ->nullable();

            /*
             * Review teknis
             */
            $table->unsignedBigInteger('direview_teknis_oleh')
                ->nullable();

            $table->timestamp('direview_teknis_pada')
                ->nullable();

            /*
             * Persetujuan rekomendasi teknis
             */
            $table->unsignedBigInteger('disetujui_rekomendasi_oleh')
                ->nullable();

            $table->timestamp('disetujui_rekomendasi_pada')
                ->nullable();

            /*
             * Persetujuan keputusan IPPT oleh DPMPTSP
             */
            $table->unsignedBigInteger('disetujui_keputusan_oleh')
                ->nullable();

            $table->timestamp('disetujui_keputusan_pada')
                ->nullable();

            $table->timestamps();

            // Relasi pemohon
            $table->foreign('pemohon_id')
                ->references('id')
                ->on('pemohon')
                ->cascadeOnDelete();

            // User verifikasi
            $table->foreign('diverifikasi_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // User review teknis
            $table->foreign('direview_teknis_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // User persetujuan rekomendasi
            $table->foreign('disetujui_rekomendasi_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // User persetujuan keputusan
            $table->foreign('disetujui_keputusan_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Index
            $table->index(
                ['pemohon_id', 'status'],
                'permohonan_pemohon_status_index'
            );

            $table->index(
                ['tanggal_permohonan', 'status'],
                'permohonan_tanggal_status_index'
            );

            $table->index(
                ['penggunaan_sekarang', 'penggunaan_dimohonkan'],
                'permohonan_penggunaan_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan');
    }
};
