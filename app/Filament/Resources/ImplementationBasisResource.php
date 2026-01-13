<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImplementationBasisResource\Pages;
use App\Models\ImplementationBasis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImplementationBasisResource extends Resource
{
    protected static ?string $model = ImplementationBasis::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Dasar Pelaksanaan';
    
    protected static ?string $modelLabel = 'Dasar Pelaksanaan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Dasar Pelaksanaan')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
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
            'index' => Pages\ListImplementationBases::route('/'),
            'create' => Pages\CreateImplementationBasis::route('/create'),
            'edit' => Pages\EditImplementationBasis::route('/{record}/edit'),
        ];
    }
}
