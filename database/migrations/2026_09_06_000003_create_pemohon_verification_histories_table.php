<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemohon_verification_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pemohon_id')->constrained('pemohon')->cascadeOnDelete();
            $table->unsignedInteger('versi_data');
            $table->string('aksi', 40);
            $table->string('status_sebelumnya', 40)->nullable();
            $table->string('status_sesudahnya', 40);
            $table->text('catatan')->nullable();
            $table->json('snapshot')->nullable();
            $table->foreignId('dilakukan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dilakukan_pada');
            $table->timestamps();

            $table->index(['pemohon_id', 'versi_data'], 'pvh_pemohon_versi_index');
            $table->index(['status_sesudahnya', 'dilakukan_pada'], 'pvh_status_dilakukan_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemohon_verification_histories');
    }
};
