<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ManufacturingOrder;
use App\Models\User;
use Illuminate\Database\Seeder;

class DockerDemoSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('DEMO_ADMIN_EMAIL');
        $password = env('DEMO_ADMIN_PASSWORD');
        if (! $email || ! $password) {
            throw new \RuntimeException('Docker demo credentials are required.');
        }

        $admin = User::firstOrCreate(['email' => $email], [
            'name' => 'Demo Administrator',
            'password' => $password,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        if (ManufacturingOrder::exists()) {
            return;
        }

        foreach ([
            ['W-200', 40, 'Nordwerk Maschinenbau GmbH', 'fertig', 3],
            ['F-310', 30, 'Kieler Anlagenbau', 'in_arbeit', 1],
            ['G-120', 10, 'Hanse Technik', 'freigegeben', 0],
        ] as [$articleCode, $quantity, $customer, $status, $completedSteps]) {
            $article = Article::with('steps.workstation')->where('code', $articleCode)->firstOrFail();
            $order = ManufacturingOrder::create([
                'number' => 'FA-'.(1001 + ManufacturingOrder::count()),
                'article_id' => $article->id,
                'quantity' => $quantity,
                'due_date' => now()->addWeeks(2)->toDateString(),
                'customer' => $customer,
                'status' => $status,
                'created_by' => $admin->id,
            ]);

            foreach ($article->steps as $index => $step) {
                $completed = $index < $completedSteps;
                $operation = $order->operations()->create([
                    'workstation_id' => $step->workstation_id,
                    'sequence' => $step->sequence,
                    'name' => $step->name,
                    'planned_setup_minutes' => $step->setup_minutes,
                    'planned_run_minutes' => $step->minutes_per_unit * $quantity,
                    'hourly_rate' => $step->workstation->hourly_rate,
                    'status' => $completed ? 'fertig' : 'offen',
                    'good_quantity' => $completed ? $quantity : 0,
                ]);
                if ($completed) {
                    $minutes = (float) $operation->planned_setup_minutes + (float) $operation->planned_run_minutes + 8;
                    $operation->entries()->create([
                        'user_id' => $admin->id,
                        'kind' => 'run',
                        'started_at' => now()->subDays(1)->subMinutes((int) $minutes),
                        'ended_at' => now()->subDays(1),
                    ]);
                }
            }
        }
    }
}
