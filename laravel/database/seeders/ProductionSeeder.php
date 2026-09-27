<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Workstation;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['AP-10', 'Säge', 45],
            ['AP-20', 'CNC-Drehmaschine', 85],
            ['AP-30', 'CNC-Fräsmaschine', 90],
            ['AP-40', 'Messplatz', 50],
            ['AP-50', 'Montage', 55],
        ] as [$code, $name, $rate]) {
            Workstation::firstOrCreate(['code' => $code], ['name' => $name, 'hourly_rate' => $rate, 'active' => true]);
        }

        foreach ([
            ['W-200', 'Antriebswelle Ø40', [[10, 'Sägen', 'AP-10', 10, 1.5], [20, 'Drehen', 'AP-20', 30, 4], [30, 'Prüfen', 'AP-40', 0, 1]]],
            ['F-310', 'Flansch DN80', [[10, 'Sägen', 'AP-10', 10, 1], [20, 'Drehen', 'AP-20', 25, 3], [30, 'Fräsen', 'AP-30', 20, 2], [40, 'Prüfen', 'AP-40', 0, 1]]],
            ['G-120', 'Lagergehäuse', [[10, 'Sägen', 'AP-10', 10, 1], [20, 'Fräsen', 'AP-30', 30, 8], [30, 'Prüfen', 'AP-40', 0, 1]]],
            ['B-050', 'Distanzbuchse', [[10, 'Sägen', 'AP-10', 5, 0.5], [20, 'Drehen', 'AP-20', 20, 1.5]]],
        ] as [$code, $name, $steps]) {
            $article = Article::firstOrCreate(['code' => $code], ['name' => $name]);
            foreach ($steps as [$sequence, $stepName, $stationCode, $setup, $perUnit]) {
                $article->steps()->firstOrCreate(['sequence' => $sequence], [
                    'name' => $stepName,
                    'workstation_id' => Workstation::where('code', $stationCode)->value('id'),
                    'setup_minutes' => $setup,
                    'minutes_per_unit' => $perUnit,
                ]);
            }
        }
    }
}
