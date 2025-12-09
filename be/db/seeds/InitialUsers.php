<?php

declare(strict_types=1);

use App\Shared\PasswordHasherArgon2id;
use Phinx\Seed\AbstractSeed;

class InitialUsers extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $passwordHasher = new PasswordHasherArgon2id();

        $data = [
            [
                'email' => 'manager@example.com',
                'password' => $passwordHasher->hash('managerpass123'),
                'name' => 'Manager User',
                'employee_code' => '1000001',
                'role' => 'manager',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'email' => 'employee@example.com',
                'password' => $passwordHasher->hash('employeepass123'),
                'name' => 'Employee User',
                'employee_code' => '1000002',
                'role' => 'employee',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $users = $this->table('users');
        $users->insert($data)
            ->saveData();
    }
}
