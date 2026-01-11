<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportPreviewWidget extends Widget
{
    protected static string $view = 'filament.widgets.report-preview-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();
        $now = now();

        // Data CATKIN (Semua Kegiatan)
        $catkinData = Activity::where('user_id', $user->id)
            ->whereYear('activity_date', $now->year)
            ->whereMonth('activity_date', $now->month)
            ->orderBy('activity_date')
            ->get();

        // Data JURNAL (Khusus KBM)
        $jurnalData = Activity::where('user_id', $user->id)
            ->whereYear('activity_date', $now->year)
            ->whereMonth('activity_date', $now->month)
            ->whereHas('category', function ($q) {
                $q->where('is_teaching', true);
            })
            ->orderBy('activity_date')
            ->get();

        // Data LABUL (Grouping Kategori)
        $labulData = Activity::where('user_id', $user->id)
            ->whereYear('activity_date', $now->year)
            ->whereMonth('activity_date', $now->month)
            ->with('category')
            ->get()
            ->groupBy('category_id');

        return [
            'monthName' => $now->translatedFormat('F Y'),
            'catkinData' => $catkinData,
            'jurnalData' => $jurnalData,
            'labulData' => $labulData,
        ];
    }
}
