<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemeriksaan_lapangan', function (Blueprint $table) {
            $table->string('status', 20)->default('draf')->after('hasil');
            $table->string('nomor_bap', 50)->nullable()->unique()->after('status');
            $table->string('generated_bap_path', 255)->nullable()->after('nomor_bap');
            $table->time('waktu_mulai')->nullable()->after('tanggal_pemeriksaan');
            $table->time('waktu_selesai')->nullable()->after('waktu_mulai');
            $table->string('nama_tim', 500)->nullable()->after('dibuat_oleh');
            $table->string('cuaca', 100)->nullable()->after('nama_tim');
            $table->decimal('latitude', 10, 7)->nullable()->after('cuaca');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->text('alamat_lokasi')->nullable()->after('longitude');
            $table->json('checklist')->nullable()->after('alamat_lokasi');
            $table->text('kondisi_eksisting')->nullable()->after('checklist');
            $table->text('temuan')->nullable()->after('kondisi_eksisting');
            $table->text('kesimpulan')->nullable()->after('temuan');
            $table->text('rekomendasi')->nullable()->after('kesimpulan');
            $table->json('foto_lapangan')->nullable()->after('rekomendasi');
            $table->unsignedBigInteger('difinalisasi_oleh')->nullable()->after('foto_lapangan');
            $table->timestamp('difinalisasi_pada')->nullable()->after('difinalisasi_oleh');
        });

        Schema::table('pemeriksaan_lapangan', function (Blueprint $table) {
            $table->foreign('difinalisasi_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        if (Schema::hasColumn('pemeriksaan_lapangan', 'lokasi_file_bap')) {
            Schema::table('pemeriksaan_lapangan', function (Blueprint $table) {
                $table->dropColumn('lokasi_file_bap');
            });
        }
    }

    public function down(): void
    {
        Schema::table('pemeriksaan_lapangan', function (Blueprint $table) {
            $table->dropForeign(['difinalisasi_oleh']);
            $table->dropColumn([
                'status', 'nomor_bap', 'generated_bap_path', 'waktu_mulai', 'waktu_selesai', 'nama_tim',
                'cuaca', 'latitude', 'longitude', 'alamat_lokasi', 'checklist',
                'kondisi_eksisting', 'temuan', 'kesimpulan', 'rekomendasi',
                'foto_lapangan', 'difinalisasi_oleh', 'difinalisasi_pada',
            ]);
            $table->string('lokasi_file_bap', 255)->nullable();
        });
    }
};
