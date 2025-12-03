<?php

namespace Modules\Forums\Database\Seeders;

use Illuminate\Database\Seeder;

class ForumsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ForumSeeder::class,
        ]);
    }
}
