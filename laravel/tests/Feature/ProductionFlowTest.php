<?php

namespace Tests\Feature;

use App\Livewire\CreateOrder;
use App\Livewire\Terminal;
use App\Models\Article;
use App\Models\ManufacturingOrder;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Workstation;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_snapshots_plan_and_calculates_quantity_dependent_time(): void
    {
        $this->seed(ProductionSeeder::class);
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        Livewire::test(CreateOrder::class)
            ->set('articleId', Article::where('code', 'W-200')->value('id'))
            ->set('quantity', 40)
            ->set('dueDate', '2026-10-14')
            ->call('save', true)
            ->assertHasNoErrors();

        $order = ManufacturingOrder::with('operations')->firstOrFail();
        $this->assertSame('freigegeben', $order->status);
        $this->assertSame('FA-1001', $order->number);
        $this->assertEquals(190, $order->operations->firstWhere('sequence', 20)->planned_minutes);
    }

    public function test_interruption_is_excluded_from_actual_production_time(): void
    {
        $this->seed(ProductionSeeder::class);
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        Livewire::test(CreateOrder::class)
            ->set('articleId', Article::where('code', 'W-200')->value('id'))
            ->set('quantity', 1)
            ->set('dueDate', '2026-10-14')
            ->call('save', true);

        $operation = ManufacturingOrder::firstOrFail()->operations()->firstOrFail();
        $station = Workstation::where('code', 'AP-10')->firstOrFail();
        Livewire::test(Terminal::class)
            ->set('workstationId', $station->id)
            ->call('start', $operation->id, 'run')
            ->set('reason', 'material')
            ->call('pause')
            ->assertHasNoErrors();

        $this->assertSame('interruption', TimeEntry::whereNull('ended_at')->firstOrFail()->kind);
        $this->assertEquals(0, $operation->fresh()->load('entries')->actual_minutes);

        Livewire::test(Terminal::class)->call('resume')->assertHasNoErrors();
        $this->assertSame('run', TimeEntry::whereNull('ended_at')->firstOrFail()->kind);
    }
}
