<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risalah_pertimbangan', function (Blueprint $table): void {
            $table->string('status', 20)->default('menunggu_dokumen')->after('catatan');
            $table->unsignedBigInteger('diminta_oleh')->nullable()->after('status');
            $table->timestamp('diminta_pada')->nullable()->after('diminta_oleh');
            $table->timestamp('diterima_pada')->nullable()->after('diterima_oleh');

            $table->foreign('diminta_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['permohonan_id', 'status'], 'risalah_permohonan_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('risalah_pertimbangan', function (Blueprint $table): void {
            $table->dropForeign(['diminta_oleh']);
            $table->dropIndex('risalah_permohonan_status_index');
            $table->dropColumn(['status', 'diminta_oleh', 'diminta_pada', 'diterima_pada']);
        });
    }
};
