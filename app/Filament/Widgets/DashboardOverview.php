<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DashboardOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        $now = now();

        $monthlyTotal = Activity::where('user_id', $user->id)
            ->whereMonth('activity_date', $now->month)
            ->whereYear('activity_date', $now->year)
            ->count();

        $teachingTotal = Activity::where('user_id', $user->id)
            ->whereMonth('activity_date', $now->month)
            ->whereYear('activity_date', $now->year)
            ->whereHas('category', function ($q) {
                $q->where('is_teaching', true);
            })
            ->count();

        return [
            Stat::make('Total Kegiatan Bulan Ini', $monthlyTotal)
                ->description('Seluruh kegiatan tercatat')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),
            
            Stat::make('Kegiatan KBM Bulan Ini', $teachingTotal)
                ->description('Jurnal Mengajar')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
        ];
    }
}
