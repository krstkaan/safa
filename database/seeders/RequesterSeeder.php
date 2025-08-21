<?php

namespace Database\Seeders;

use App\Models\Requester;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RequesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Create 3 requesters With name of Duanur Bulut, Elif Bal, Betül Görgülü using Requester Model.

        Requester::create([
            'name' => 'Duanur Bulut',
        ]);

        Requester::create([
            'name' => 'Elif Bal',
        ]);

        Requester::create([
            'name' => 'Betül Görgülü',
        ]);
    }
}
