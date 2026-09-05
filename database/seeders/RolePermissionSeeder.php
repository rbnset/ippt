<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Pastikan role tersedia
        |--------------------------------------------------------------------------
        */

        $roles = [
            'admin',
            'pemohon',
            'staff',
            'tim_teknis',
            'kabid',
            'kadis',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        $admin = Role::findByName('admin', 'web');

        $admin->syncPermissions(
            Permission::where('guard_name', 'web')->get()
        );

        /*
        |--------------------------------------------------------------------------
        | PEMOHON
        |--------------------------------------------------------------------------
        */

        $pemohon = Role::findByName('pemohon', 'web');

        $pemohon->syncPermissions(
            $this->permissions([
                'Pemohon' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                ],

                'Permohonan' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                ],

                'DokumenPermohonan' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                ],
            ])
        );

        /*
        |--------------------------------------------------------------------------
        | STAFF
        |--------------------------------------------------------------------------
        */

        $staff = Role::findByName('staff', 'web');

        $staff->syncPermissions(
            $this->permissions([
                'Pemohon' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                ],

                'Permohonan' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                ],

                'DokumenPermohonan' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                    'delete',
                ],
            ])
        );

        /*
        |--------------------------------------------------------------------------
        | TIM TEKNIS
        |--------------------------------------------------------------------------
        */

        $timTeknis = Role::findByName('tim_teknis', 'web');

        $timTeknis->syncPermissions(
            $this->permissions([
                'Permohonan' => [
                    'view_any',
                    'view',
                    'update',
                ],

                'DokumenPermohonan' => [
                    'view_any',
                    'view',
                ],

                'PemeriksaanLapangan' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                ],

                'RekomendasiTeknis' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                ],

                'RisalahPertimbangan' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                ],
            ])
        );

        /*
        |--------------------------------------------------------------------------
        | KABID
        |--------------------------------------------------------------------------
        */

        $kabid = Role::findByName('kabid', 'web');

        $kabid->syncPermissions(
            $this->permissions([
                'Permohonan' => [
                    'view_any',
                    'view',
                ],

                'DokumenPermohonan' => [
                    'view_any',
                    'view',
                ],

                'PemeriksaanLapangan' => [
                    'view_any',
                    'view',
                ],

                'RekomendasiTeknis' => [
                    'view_any',
                    'view',
                    'update',
                ],

                'RisalahPertimbangan' => [
                    'view_any',
                    'view',
                ],
            ])
        );

        /*
        |--------------------------------------------------------------------------
        | KADIS
        |--------------------------------------------------------------------------
        */

        $kadis = Role::findByName('kadis', 'web');

        $kadis->syncPermissions(
            $this->permissions([
                'Permohonan' => [
                    'view_any',
                    'view',
                ],

                'RekomendasiTeknis' => [
                    'view_any',
                    'view',
                ],

                'RisalahPertimbangan' => [
                    'view_any',
                    'view',
                ],

                'KeputusanIppt' => [
                    'view_any',
                    'view',
                    'create',
                    'update',
                ],
            ])
        );
    }

    /**
     * Ambil permission berdasarkan Resource dan action.
     */
    private function permissions(array $resources)
    {
        $permissionNames = [];

        foreach ($resources as $resource => $actions) {
            $resourceName = str($resource)
                ->snake()
                ->lower()
                ->toString();

            foreach ($actions as $action) {
                $permissionNames[] = "{$action}_{$resourceName}";
            }
        }

        return Permission::where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->get();
    }
}
