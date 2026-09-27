<?php

namespace App\Http\Controllers;

use App\Models\ManufacturingOrder;
use App\Models\TimeEntry;
use App\Models\Workstation;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function dashboard()
    {
        $stations = Workstation::with(['activeEntries' => fn ($query) => $query->with('operation.order')->latest('started_at')])->orderBy('code')->get();
        $counts = ManufacturingOrder::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $recent = TimeEntry::with(['operation.order', 'user'])->whereNotNull('ended_at')->latest('ended_at')->limit(8)->get();
        return view('production.dashboard', compact('stations', 'counts', 'recent'));
    }

    public function costing(Request $request)
    {
        $orders = ManufacturingOrder::with('article')->where('status', 'fertig')->orderByDesc('id')->get();
        $selectedId = $request->integer('order') ?: $orders->first()?->id;
        $order = $selectedId ? ManufacturingOrder::with(['article', 'operations.entries', 'operations.workstation'])->where('status', 'fertig')->findOrFail($selectedId) : null;
        return view('production.costing', compact('orders', 'order'));
    }

    public function reports(Request $request)
    {
        $entries = TimeEntry::with(['operation.order.article', 'operation.workstation'])
            ->whereNotNull('ended_at')->get();
        $production = $entries->whereIn('kind', ['setup', 'run']);
        $interruptions = $entries->where('kind', 'interruption')->groupBy('reason');
        if ($request->boolean('csv')) {
            $lines = ["Grund;Minuten;Anzahl"];
            foreach ($interruptions as $reason => $items) {
                $lines[] = implode(';', [$reason, number_format($items->sum('minutes'), 2, ',', ''), $items->count()]);
            }
            return response("\xEF\xBB\xBF".implode("\r\n", $lines)."\r\n", 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="unterbrechungen.csv"',
            ]);
        }
        return view('production.reports', compact('production', 'interruptions'));
    }
}
