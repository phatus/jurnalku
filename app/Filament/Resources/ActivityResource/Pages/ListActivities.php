<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('download_report')
                ->label('Download Laporan')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    \Filament\Forms\Components\Select::make('month')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                            4 => 'April', 5 => 'Mei', 6 => 'Juni',
                            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ])
                        ->default(now()->month)
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('year')
                        ->label('Tahun')
                        ->numeric()
                        ->default(now()->year)
                        ->required(),
                    \Filament\Forms\Components\Select::make('type')
                        ->label('Jenis Laporan')
                        ->options([
                            'catkin' => 'Catatan Kinerja (Catkin)',
                            'jurnal' => 'Jurnal Mengajar',
                            'labul' => 'Laporan Bulanan (Labul)',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    return redirect()->route('report.download', [
                        'month' => $data['month'],
                        'year' => $data['year'],
                        'type' => $data['type'],
                    ]);
                }),
        ];
    }
}
