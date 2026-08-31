<?php

namespace Database\Seeders;

use Database\Seeders\Support\SyntheticSeed;
use Illuminate\Database\Seeder;

class EmployeeDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SyntheticSeed::guard(['local', 'testing']);
        // Document records require real private files. Feature tests use Storage::fake
        // so this demo seeder intentionally leaves existing metadata untouched.
    }
}
