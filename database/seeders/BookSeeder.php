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
        // 1. Kitap
        $book1 = Book::create([
            'name' => 'Küçük Prens',
            'language' => 'Türkçe',
            'page_count' => 96,
            'is_donation' => false,
            'barcode' => '9786051111234',
            'shelf_code' => '3',
            'fixture_no' => 'F001',
            'author_id' => 1, // Antoine de Saint-Exupéry
            'publisher_id' => 1, // Can Yayınları
        ]);
        $book1->grades()->attach([1, 2, 3, 4]); // örnek: 1. ve 2. sınıflara uygun

        // 2. Kitap
        $book2 = Book::create([
            'name' => 'Sineklerin Tanrısı',
            'language' => 'Türkçe',
            'page_count' => 224,
            'is_donation' => false,
            'barcode' => '9786051111235',
            'shelf_code' => '4',
            'fixture_no' => 'F002',
            'author_id' => 2, // William Golding
            'publisher_id' => 1, // Can Yayınları
        ]);
        $book2->grades()->attach([5, 6, 7, 8]); // örnek: 5. sınıfa uygun

        // 3. Kitap
        $book3 = Book::create([
            'name' => '1984',
            'language' => 'Türkçe',
            'page_count' => 328,
            'is_donation' => false,
            'barcode' => '9786051111236',
            'shelf_code' => '5',
            'fixture_no' => 'F003',
            'author_id' => 3, // George Orwell
            'publisher_id' => 2, // Penguin Random House
        ]);
        $book3->grades()->attach([5, 6, 7, 8]); // örnek: 5. sınıfa uygun
    }
}
