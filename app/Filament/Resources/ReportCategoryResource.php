<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportCategoryResource\Pages;
use App\Models\ReportCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportCategoryResource extends Resource
{
    protected static ?string $model = ReportCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationLabel = 'Master Kategori';

    protected static ?string $modelLabel = 'Kategori Kegiatan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('rhk_label')
                    ->label('Isi RHK (Rencana Hasil Kerja)')
                    ->helperText('Teks yang akan muncul di Laporan Bulanan (Labul)')
                    ->required(),

                Forms\Components\Toggle::make('is_teaching')
                    ->label('Termasuk Kegiatan KBM?')
                    ->helperText('Aktifkan jika kategori ini adalah kegiatan mengajar di kelas (untuk memunculkan form Kelas/Jam).')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rhk_label')
                    ->label('RHK')
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_teaching')
                    ->label('KBM?')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportCategories::route('/'),
            'create' => Pages\CreateReportCategory::route('/create'),
            'edit' => Pages\EditReportCategory::route('/{record}/edit'),
        ];
    }
}
