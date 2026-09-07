<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risalah_pertimbangan', function (Blueprint $table): void {
            $table->string('lokasi_file', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing intake records may intentionally have no external file yet
        // while the application is in the "menunggu_dokumen" state. We therefore
        // do not make this column required again automatically on rollback.
    }
};
