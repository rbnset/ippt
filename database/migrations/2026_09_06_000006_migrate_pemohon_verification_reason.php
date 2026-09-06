<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pemohon')
            ->whereNull('alasan_perubahan')
            ->whereNotNull('catatan_verifikasi')
            ->update(['alasan_perubahan' => DB::raw('catatan_verifikasi')]);
    }

    public function down(): void
    {
        // Intentionally irreversible: alasan_perubahan is now the canonical reason field.
    }
};
