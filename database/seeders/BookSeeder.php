<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Kitap - İlkokul seviyesi
        Book::create([
            'name' => 'Küçük Prens',
            'language' => 'Türkçe',
            'page_count' => 96,
            'is_donation' => false,
            'barcode' => '9786051111234',
            'shelf_code' => '3',
            'fixture_no' => 'F001',
            'level' => 'ilkokul',
            'author_id' => 1, // Antoine de Saint-Exupéry
            'publisher_id' => 1, // Can Yayınları
        ]);

        // 2. Kitap - Ortaokul seviyesi
        Book::create([
            'name' => 'Sineklerin Tanrısı',
            'language' => 'Türkçe',
            'page_count' => 224,
            'is_donation' => false,
            'barcode' => '9786051111235',
            'shelf_code' => '4',
            'fixture_no' => 'F002',
            'level' => 'ortaokul',
            'author_id' => 2, // William Golding
            'publisher_id' => 1, // Can Yayınları
        ]);

        // 3. Kitap - Ortaokul seviyesi
        Book::create([
            'name' => '1984',
            'language' => 'Türkçe',
            'page_count' => 328,
            'is_donation' => false,
            'barcode' => '9786051111236',
            'shelf_code' => '5',
            'fixture_no' => 'F003',
            'level' => 'ortaokul',
            'author_id' => 3, // George Orwell
            'publisher_id' => 2, // Penguin Random House
        ]);

        // 4. Kitap - Ortak (tüm seviyeler için uygun)
        Book::create([
            'name' => 'Türk Masalları',
            'language' => 'Türkçe',
            'page_count' => 156,
            'is_donation' => true,
            'barcode' => '9786051111237',
            'shelf_code' => '1',
            'fixture_no' => 'F004',
            'level' => 'ortak',
            'author_id' => 1, // Antoine de Saint-Exupéry
            'publisher_id' => 1, // Can Yayınları
        ]);

        // 5. Kitap - İlkokul seviyesi
        Book::create([
            'name' => 'Kedi ile Köpek',
            'language' => 'Türkçe',
            'page_count' => 48,
            'is_donation' => false,
            'barcode' => '9786051111238',
            'shelf_code' => '2',
            'fixture_no' => 'F005',
            'level' => 'ilkokul',
            'author_id' => 2, // William Golding
            'publisher_id' => 2, // Penguin Random House
        ]);
    }
}
