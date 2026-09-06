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
        // Keep the first existing profile for each account and detach any legacy duplicates.
        $duplicates = DB::table('pemohon')
            ->select('user_id', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('pemohon')
                ->where('user_id', $duplicate->user_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->update(['user_id' => null]);
        }

        Schema::table('pemohon', function (Blueprint $table): void {
            $table->unique('user_id', 'pemohon_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pemohon', function (Blueprint $table): void {
            $table->dropUnique('pemohon_user_id_unique');
        });
    }
};
