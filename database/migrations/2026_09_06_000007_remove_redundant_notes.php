<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pemohon', 'catatan_verifikasi')) {
            Schema::table('pemohon', function (Blueprint $table): void {
                $table->dropColumn('catatan_verifikasi');
            });
        }

        if (Schema::hasColumn('users', 'catatan_akun')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('catatan_akun');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pemohon', 'catatan_verifikasi')) {
            Schema::table('pemohon', function (Blueprint $table): void {
                $table->text('catatan_verifikasi')->nullable()->after('status_verifikasi');
            });
        }

        if (! Schema::hasColumn('users', 'catatan_akun')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->text('catatan_akun')->nullable()->after('status_akun');
            });
        }
    }
};
