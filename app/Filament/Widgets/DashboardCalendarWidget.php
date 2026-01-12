<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardCalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-calendar-widget';
    
    protected static ?int $sort = 2;
    
    // Allow column span to be full width
    protected int | string | array $columnSpan = 'full';

    public $currentMonth;
    public $currentYear;

    public function mount()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
    }

    public function getCalendarData()
    {
        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $startOfMonth->daysInMonth;
        
        // Fetch activity dates for current user/month
        $activities = Activity::query()
            ->where('user_id', Auth::id())
            ->whereYear('activity_date', $this->currentYear)
            ->whereMonth('activity_date', $this->currentMonth)
            ->pluck('activity_date')
            ->map(function ($date) {
                return Carbon::parse($date)->day; // Get just the day number
            })
            ->toArray();

        $calendar = [];
        
        // Logic to build simple grid array
        // We need to know which day of week the 1st starts on (0=Sunday, 6=Saturday)
        $startDayOfWeek = $startOfMonth->dayOfWeek;

        return [
            'daysInMonth' => $daysInMonth,
            'startDayOfWeek' => $startDayOfWeek,
            'filledDays' => $activities,
            'monthName' => $startOfMonth->translatedFormat('F Y'),
        ];
    }
}
