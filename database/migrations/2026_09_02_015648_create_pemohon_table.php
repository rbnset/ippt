<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemohon', function (Blueprint $table) {
            $table->id();

            // Relasi ke users, boleh kosong jika pemohon didaftarkan oleh petugas
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // perorangan / badan
            $table->string('jenis_pemohon', 10);

            $table->string('nama', 150);

            // Identitas perorangan
            $table->string('nik', 16)->nullable()->unique();

            // Identitas badan
            $table->string('nib', 13)->nullable()->unique();
            $table->string('npwp', 16)->nullable()->index();

            $table->string('nomor_telepon', 16);
            $table->string('email', 150)->nullable();

            $table->string('alamat', 255);
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kota', 100)->default('Yogyakarta');

            $table->timestamps();

            // Relasi ke users
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemohon');
    }
};
