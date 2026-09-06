<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration is intentionally defensive because older V29 packages
        // may already have removed catatan_verifikasi before this migration runs.
        // Never query a column that no longer exists.
        if (! Schema::hasColumn('pemohon', 'alasan_perubahan')) {
            Schema::table('pemohon', function (Blueprint $table): void {
                $table->text('alasan_perubahan')->nullable();
            });
        }

        if (Schema::hasColumn('pemohon', 'catatan_verifikasi')) {
            DB::table('pemohon')
                ->whereNull('alasan_perubahan')
                ->whereNotNull('catatan_verifikasi')
                ->update(['alasan_perubahan' => DB::raw('catatan_verifikasi')]);

            Schema::table('pemohon', function (Blueprint $table): void {
                $table->dropColumn('catatan_verifikasi');
            });
        }
    }

    public function down(): void
    {
        // No-op: catatan_verifikasi is intentionally retired from the V29+ model.
    }
};
