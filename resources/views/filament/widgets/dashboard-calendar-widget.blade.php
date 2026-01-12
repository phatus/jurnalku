<x-filament::section>
    @php
        $data = $this->getCalendarData();
        $daysInMonth = $data['daysInMonth'];
        $startDayOfWeek = $data['startDayOfWeek'];
        $filledDays = $data['filledDays'];
        $monthName = $data['monthName'];
        
        $daysOfWeek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $currentDay = 1;
        $today = now()->day;
        $isCurrentMonth = $this->currentMonth == now()->month && $this->currentYear == now()->year;
    @endphp

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">
            Jadwal Kegiatan - {{ $monthName }}
        </h2>
        <div class="flex gap-2 text-sm">
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 bg-success-500 rounded-full"></span> Terisi
            </span>
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 bg-danger-500 rounded-full"></span> Kosong
            </span>
        </div>
    </div>

    <div class="grid grid-cols-7 gap-1 text-center text-sm border-t border-l border-gray-200 dark:border-gray-700">
        {{-- Header Days --}}
        @foreach($daysOfWeek as $day)
            <div class="py-2 font-semibold bg-gray-50 dark:bg-gray-800 border-r border-b border-gray-200 dark:border-gray-700">
                {{ $day }}
            </div>
        @endforeach

        {{-- Empty Cells before 1st of month --}}
        @for($i = 0; $i < $startDayOfWeek; $i++)
            <div class="h-24 bg-gray-50/50 border-r border-b border-gray-200 dark:border-gray-700"></div>
        @endfor

        {{-- Days Cells --}}
        @while($currentDay <= $daysInMonth)
            @php
                $isFilled = in_array($currentDay, $filledDays);
                $isToday = $isCurrentMonth && $currentDay == $today;
                
                // Color Logic
                $bgColor = 'bg-white dark:bg-gray-900'; // Default
                if ($isFilled) {
                    $bgColor = 'bg-success-50 dark:bg-success-900/10';
                } elseif ($isCurrentMonth && $currentDay < $today && !$isFilled) {
                     // Empty past days in current month are Red
                    $bgColor = 'bg-danger-50 dark:bg-danger-900/10';
                }
            @endphp

            <div class="h-24 p-2 relative border-r border-b border-gray-200 dark:border-gray-700 {{ $bgColor }} transition hover:brightness-95">
                <span class="font-medium {{ $isToday ? 'bg-primary-600 text-white w-6 h-6 rounded-full flex items-center justify-center' : '' }}">
                    {{ $currentDay }}
                </span>
                
                @if($isFilled)
                    <div class="mt-2 text-xs text-success-600 dark:text-success-400">
                        <x-heroicon-m-check-circle class="w-6 h-6 mx-auto"/>
                        <span class="block mt-1 font-semibold">Terisi</span>
                    </div>
                @elseif($isCurrentMonth && $currentDay < $today && !$isFilled)
                     <div class="mt-2 text-xs text-danger-600 dark:text-danger-400">
                        <x-heroicon-m-x-circle class="w-6 h-6 mx-auto"/>
                        <span class="block mt-1 font-semibold">Kosong</span>
                    </div>
                @endif
            </div>

            @php $currentDay++; @endphp
        @endwhile

        {{-- Empty Cells after end of month --}}
        @php
            $remainingCells = (7 - (($startDayOfWeek + $daysInMonth) % 7)) % 7;
        @endphp
        @for($j = 0; $j < $remainingCells; $j++)
             <div class="h-24 bg-gray-50/50 border-r border-b border-gray-200 dark:border-gray-700"></div>
        @endfor
    </div>
</x-filament::section>
