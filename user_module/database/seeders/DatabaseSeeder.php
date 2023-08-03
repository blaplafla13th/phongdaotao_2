<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\UserType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        Department::factory()->create([
            'name' => 'Toán - Cơ - Tin học',
        ]);
        User::factory()->create([
            'name' => 'Pham Ba Thang',
            'email' => 'phambathanghp@gmail.com',
            'phone' => '0796421201',
            'password' => bcrypt('ThangPB@1312TG'),
            'role' => UserType::Administrator,
            'department_id' => 1,
        ]);

    }
}
