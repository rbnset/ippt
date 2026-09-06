<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Pemohon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Filament\Pages\Dashboard;

class PemohonProfileController extends Controller
{
    public function edit(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(UserRole::PEMOHON), 403);

        return redirect()->to(Dashboard::getUrl());
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(UserRole::PEMOHON), 403);

        $pemohon = $user->pemohon()->first();
        if ($pemohon?->isTerverifikasi()) {
            return back()->with('info', 'Data pemohon sudah terverifikasi dan tidak dapat diubah melalui akun ini.');
        }

        $data = $request->validate([
            'jenis_pemohon' => ['required', Rule::in(['perorangan', 'badan'])],
            'nama' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'digits:16', Rule::unique('pemohon', 'nik')->ignore($pemohon?->id)],
            'nib' => ['nullable', 'digits:13', Rule::unique('pemohon', 'nib')->ignore($pemohon?->id)],
            'npwp' => ['nullable', 'string', 'max:16'],
            'nomor_telepon' => ['required', 'string', 'max:16'],
            'alamat' => ['required', 'string', 'max:255'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:100'],
        ]);

        if ($data['jenis_pemohon'] === 'perorangan') {
            if (blank($data['nik'] ?? null)) {
                return back()->withErrors(['nik' => 'NIK wajib diisi untuk pemohon perorangan.'])->withInput();
            }
            $data['nib'] = null;
        } else {
            if (blank($data['nib'] ?? null)) {
                return back()->withErrors(['nib' => 'NIB wajib diisi untuk pemohon badan.'])->withInput();
            }
            $data['nik'] = null;
        }

        $data['email'] = $user->email;
        $data['status_verifikasi'] = 'menunggu_verifikasi';
        $data['alasan_perubahan'] = null;
        $data['diverifikasi_oleh'] = null;
        $data['diverifikasi_pada'] = null;

        $pemohon ??= new Pemohon(['user_id' => $user->id]);
        $pemohon->user_id = $user->id;
        $pemohon->fill($data);
        $pemohon->save();

        $user->update(['name' => $data['nama']]);

        return redirect()->route('pemohon.profil')->with('success', 'Data pemohon berhasil dikirim dan menunggu verifikasi petugas.');
    }
}
