<x-filament::widget>
    <x-filament::section>
        <div x-data="{ activeTab: 'catkin' }" class="space-y-4">
            {{-- Header & Tabs --}}
            <div class="flex items-center justify-between border-b pb-2">
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">
                    Preview Laporan: {{ $monthName }}
                </h2>
                <div class="flex space-x-2">
                    <button 
                        @click="activeTab = 'catkin'"
                        :class="activeTab === 'catkin' 
                            ? 'bg-primary-600 text-white shadow-md ring-1 ring-primary-600' 
                            : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
                    >
                        Catkin
                    </button>
                    <button 
                        @click="activeTab = 'jurnal'"
                        :class="activeTab === 'jurnal' 
                            ? 'bg-primary-600 text-white shadow-md ring-1 ring-primary-600' 
                            : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
                    >
                        Jurnal Mengajar
                    </button>
                    <button 
                        @click="activeTab = 'labul'"
                        :class="activeTab === 'labul' 
                            ? 'bg-primary-600 text-white shadow-md ring-1 ring-primary-600' 
                            : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
                    >
                        Laporan Bulanan
                    </button>
                </div>
            </div>

            {{-- Content CATKIN --}}
            <div x-show="activeTab === 'catkin'" class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border rounded-lg">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-2 border">No</th>
                            <th class="px-4 py-2 border">Tanggal</th>
                            <th class="px-4 py-2 border">Uraian Kegiatan</th>
                            <th class="px-4 py-2 border">Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($catkinData as $index => $item)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-4 py-2 border">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 border whitespace-nowrap">{{ \Carbon\Carbon::parse($item->activity_date)->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-2 border">{{ $item->description }}</td>
                            <td class="px-4 py-2 border">{{ $item->output_result ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center">Belum ada kegiatan bulan ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Content JURNAL --}}
            <div x-show="activeTab === 'jurnal'" class="overflow-x-auto" style="display: none;">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border rounded-lg">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-2 border">Tgl</th>
                            <th class="px-4 py-2 border">Kls</th>
                            <th class="px-4 py-2 border">Jam</th>
                            <th class="px-4 py-2 border">Materi</th>
                            <th class="px-4 py-2 border">Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jurnalData as $item)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-4 py-2 border whitespace-nowrap">{{ \Carbon\Carbon::parse($item->activity_date)->translatedFormat('d M') }}</td>
                            <td class="px-4 py-2 border">{{ $item->class_name }}</td>
                            <td class="px-4 py-2 border whitespace-nowrap">{{ $item->period_start }} - {{ $item->period_end }}</td>
                            <td class="px-4 py-2 border">{{ $item->topic }}</td>
                            <td class="px-4 py-2 border">{{ $item->student_outcome }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center">Belum ada KBM bulan ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Content LABUL --}}
            <div x-show="activeTab === 'labul'" class="overflow-x-auto" style="display: none;">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border rounded-lg">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-2 border">No</th>
                            <th class="px-4 py-2 border">Kegiatan</th>
                            <th class="px-4 py-2 border">Volume</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($labulData as $categoryId => $activities)
                        @php
                            $category = $activities->first()->category;
                        @endphp
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-4 py-2 border">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 border">
                                <div class="font-bold">{{ $category->name }}</div>
                                <div class="text-xs text-gray-500">{{ Str::limit($category->rhk_label, 50) }}</div>
                            </td>
                            <td class="px-4 py-2 border">{{ $activities->count() }} Kegiatan</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-center">Data kosong.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </x-filament::section>
</x-filament::widget>
