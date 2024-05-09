<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\React::factory(50)->create();

    }
}
