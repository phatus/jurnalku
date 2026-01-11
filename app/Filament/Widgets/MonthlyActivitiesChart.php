<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MonthlyActivitiesChart extends ChartWidget
{
    protected static ?string $heading = 'Statistik Kegiatan Tahun Ini';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // SQLite compatible query
        $data = Activity::selectRaw("strftime('%m', activity_date) as month, COUNT(*) as count")
            ->where('user_id', Auth::id())
            ->whereYear('activity_date', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Fill missing months with 0
        $counts = [];
        for ($i = 1; $i <= 12; $i++) {
            // strftime returns "01", "02", so we format key carefully
            $key = str_pad($i, 2, '0', STR_PAD_LEFT);
            $counts[] = $data[$key] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Kegiatan',
                    'data' => $counts,
                    'backgroundColor' => '#f59e0b', // Amber color to match theme
                ],
            ],
            'labels' => [
                'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 
                'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
