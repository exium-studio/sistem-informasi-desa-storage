<?php

namespace Database\Seeders\Auth;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Berkas Admin',
            'username' => 'berkas.admin',
            'email' => 'distrostudiodev@gmail.com',
            'password' => Hash::make('berkasadmin123'),
            'register_at' => Carbon::now(env('APP_TIMEZONE'))
        ]);
    }
}
