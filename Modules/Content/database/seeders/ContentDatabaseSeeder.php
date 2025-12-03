<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;

class ContentDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ContentSeeder::class,
        ]);
    }
}
