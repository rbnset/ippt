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
        DB::table('pemohon')->whereNull('versi_data')->update(['versi_data' => 1]);

        if (Schema::hasColumn('pemohon', 'catatan_verifikasi')) {
            Schema::table('pemohon', function (Blueprint $table): void {
                $table->dropColumn('catatan_verifikasi');
            });
        }

        // One login account may own exactly one Pemohon record.
        $duplicates = DB::table('pemohon')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicates as $userId) {
            $keepId = DB::table('pemohon')->where('user_id', $userId)->orderBy('id')->value('id');
            DB::table('pemohon')->where('user_id', $userId)->where('id', '!=', $keepId)->update(['user_id' => null]);
        }

        Schema::table('pemohon', function (Blueprint $table): void {
            $table->unique('user_id', 'pemohon_user_unique');
            $table->string('status_verifikasi', 30)->nullable()->change();
            $table->string('nomor_telepon', 16)->nullable()->change();
            $table->string('alamat', 255)->nullable()->change();
        });

        if (Schema::hasColumn('users', 'catatan_akun')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('catatan_akun');
            });
        }
    }

    public function down(): void
    {
        Schema::table('pemohon', function (Blueprint $table): void {
            $table->dropUnique('pemohon_user_unique');
            $table->text('catatan_verifikasi')->nullable();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->text('catatan_akun')->nullable();
        });
    }
};
