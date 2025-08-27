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
        // Diğer seeder'ları çalıştır
        $this->call([
            UserSeeder::class,
            RequesterSeeder::class,
            ApproverSeeder::class,
            PrintRequestSeeder::class,
            AuthorSeeder::class,
            PublisherSeeder::class,
            GradeSeeder::class,
            BookSeeder::class,
        ]);
    }
}
