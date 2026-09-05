<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risalah_pertimbangan', function (Blueprint $table) {
            $table->text('dasar_penerbitan')->nullable()->after('tanggal_risalah');
            $table->string('nomor_ba_peninjauan', 100)->nullable()->after('dasar_penerbitan');
            $table->date('tanggal_ba_peninjauan')->nullable()->after('nomor_ba_peninjauan');
            $table->string('nomor_ba_pembahasan', 100)->nullable()->after('tanggal_ba_peninjauan');
            $table->date('tanggal_ba_pembahasan')->nullable()->after('nomor_ba_pembahasan');
            $table->text('pertimbangan_penguasaan_pemilikan')->nullable()->after('hasil');
            $table->text('ketentuan_syarat')->nullable()->after('pertimbangan_penguasaan_pemilikan');
            $table->text('indikasi_sengketa')->nullable()->after('ketentuan_syarat');
            $table->text('pengakuan_hak')->nullable()->after('indikasi_sengketa');
            $table->text('kemampuan_tanah')->nullable()->after('indikasi_sengketa');
            $table->text('keterangan_lain')->nullable()->after('kemampuan_tanah');
            $table->string('lokasi_file_peta', 255)->nullable()->after('lokasi_file');
        });
    }

    public function down(): void
    {
        Schema::table('risalah_pertimbangan', function (Blueprint $table) {
            $table->dropColumn([
                'dasar_penerbitan',
                'nomor_ba_peninjauan',
                'tanggal_ba_peninjauan',
                'nomor_ba_pembahasan',
                'tanggal_ba_pembahasan',
                'pertimbangan_penguasaan_pemilikan',
                'ketentuan_syarat',
                'indikasi_sengketa',
                'pengakuan_hak',
                'kemampuan_tanah',
                'keterangan_lain',
                'lokasi_file_peta',
            ]);
        });
    }
};
