<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Test kullanıcısı oluştur
        User::factory()->create([
            'name' => 'Ofis Kullanıcısı',
            'email' => 'ofis@example.com',
        ]);

        // Diğer seeder'ları çalıştır
        $this->call([
            RequesterSeeder::class,
            ApproverSeeder::class,
            PrintRequestSeeder::class,
        ]);
    }
}
