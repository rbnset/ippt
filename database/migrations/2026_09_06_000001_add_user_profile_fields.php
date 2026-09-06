<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_url')->nullable()->after('role');
            $table->string('status_akun', 30)->default('aktif')->after('avatar_url')->index();
            $table->text('catatan_akun')->nullable()->after('status_akun');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_url', 'status_akun', 'catatan_akun']);
        });
    }
};
