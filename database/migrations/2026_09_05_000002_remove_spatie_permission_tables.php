<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aman untuk database baru maupun database pengembangan yang pernah memakai Spatie Permission.
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        // Tidak membuat ulang tabel pihak ketiga. Jika rollback diperlukan,
        // gunakan backup/schema sebelumnya.
    }
};
