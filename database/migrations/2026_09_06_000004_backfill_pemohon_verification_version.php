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
        // Existing databases may contain legacy Pemohon rows created before
        // versi_data was introduced, or rows where the column was explicitly NULL.
        DB::table('pemohon')
            ->whereNull('versi_data')
            ->update(['versi_data' => 1]);

        // Keep the column itself defensive as well. Existing rows are repaired
        // before changing the nullability.
        if (Schema::hasColumn('pemohon', 'versi_data')) {
            Schema::table('pemohon', function (Blueprint $table): void {
                $table->unsignedInteger('versi_data')->default(1)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        // Data backfill is intentionally irreversible. The schema remains
        // compatible with the verification workflow when this migration is rolled back.
    }
};
