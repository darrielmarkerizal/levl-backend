<?php

namespace Modules\Common\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Common\Models\LevelConfig;

class LevelConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Define levels 1 to 100
        $levels = [];
        
        for ($i = 1; $i <= 100; $i++) {
            // Formula: 100 * 1.1^(level-1)
            $xpRequired = (int) (100 * pow(1.1, $i - 1));
            
            $levels[] = [
                'level' => $i,
                'name' => 'Level ' . $i,
                'xp_required' => $xpRequired,
                'rewards' => null, // JSON Field
            ];
        }

        foreach ($levels as $level) {
            LevelConfig::updateOrCreate(
                ['level' => $level['level']],
                $level
            );
        }
    }
}
