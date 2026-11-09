<?php

namespace Tests\Feature\Concerns;

use App\Models\Role;
use App\Models\User;

/**
 * Bantuan untuk tes: membuat role + user dan meniru sesi login CleanHub (is_login, role, user_id).
 */
trait SesiRole
{
    protected User $admin;

    protected function siapkanRoleDanAdmin(): void
    {
        foreach (['admin' => 'Admin', 'petugas' => 'Petugas', 'masyarakat' => 'Masyarakat'] as $nama => $label) {
            Role::create(['name' => $nama, 'display_name' => $label]);
        }

        $this->admin = User::create([
            'role_id' => 1, 'nama_lengkap' => 'Admin', 'username' => 'admin',
            'email' => 'a@a.test', 'password' => bcrypt('secret1'),
        ]);
    }

    protected function sebagai(string $role)
    {
        return $this->withSession([
            'is_login' => true,
            'role'     => $role,
            'user_id'  => $this->admin->id,
            'nama'     => 'Tester',
        ]);
    }
}
