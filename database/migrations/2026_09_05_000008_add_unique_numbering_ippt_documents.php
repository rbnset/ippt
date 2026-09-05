<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * nomor_bap is already declared as unique by the digital BAP migration
     * (2026_09_05_000003_upgrade_pemeriksaan_lapangan_to_digital_bap).
     * This migration is intentionally kept as a no-op for upgrade history
     * compatibility and to avoid adding the same index twice on fresh installs.
     */
    public function up(): void {}

    public function down(): void {}
};
