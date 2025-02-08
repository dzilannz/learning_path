<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('semester')->insert([
            [
                'nama' => 'Semester 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Semester 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Semester 3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Semester 4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Semester 5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Semester 6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Semester 7',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Semester 8',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
