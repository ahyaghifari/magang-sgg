<?php

namespace App\Filament\Widgets;

use App\Models\Journal;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class JournalActivityChartWidget extends ChartWidget
{
    protected static ?int $sort = -1;

    protected ?string $heading = 'Jurnal Masuk — 7 Hari Terakhir';

    protected ?string $maxHeight = '220px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn (int $daysAgo) => today()->subDays($daysAgo));

        $countsByDate = Journal::query()
            ->whereDate('date', '>=', $days->first())
            ->selectRaw('date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->mapWithKeys(fn ($total, $date) => [Carbon::parse($date)->toDateString() => $total]);

        return [
            'datasets' => [
                [
                    'label' => 'Jurnal',
                    'data' => $days->map(fn ($day) => $countsByDate->get($day->toDateString(), 0))->all(),
                    'borderColor' => '#042c6c',
                    'backgroundColor' => 'rgba(4, 44, 108, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('d M'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
