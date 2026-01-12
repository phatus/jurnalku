<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use App\Models\ReportCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Kegiatan Harian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => Auth::id())
                    ->required(),

                Forms\Components\DatePicker::make('activity_date')
                    ->label('Tanggal Kegiatan')
                    ->default(now())
                    ->required(),

                Forms\Components\Select::make('category_id')
                    ->label('Kategori Kegiatan')
                    ->relationship('category', 'name')
                    ->live()
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->label('Uraian Pekerjaan')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('output_result')
                    ->label('Hasil Pekerjaan / Output')
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('reference_source')
                    ->label('Dasar Pelaksanaan (Optional)')
                    ->maxLength(255),

                Forms\Components\TextInput::make('evidence_link')
                    ->label('Link Bukti (Google Drive)')
                    ->url()
                    ->regex('/^https:\/\/drive\.google\.com\/.+/')
                    ->validationMessages([
                        'regex' => 'Link harus berasal dari Google Drive (https://drive.google.com/...)',
                    ])
                    ->suffixIcon('heroicon-m-globe-alt')
                    ->maxLength(255),

                Forms\Components\Section::make('Detail KBM (Kegiatan Belajar Mengajar)')
                    ->description('Isi detail ini khusus untuk kegiatan KBM.')
                    ->schema([
                        Forms\Components\TextInput::make('class_name')
                            ->label('Kelas')
                            ->placeholder('Contoh: 7E')
                            ->maxLength(255)
                            ->required(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('period_start')
                                    ->label('Jam Ke-')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('period_end')
                                    ->label('Sampai Jam Ke-')
                                    ->numeric()
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('topic')
                            ->label('Materi Pembelajaran')
                            ->required(),

                        Forms\Components\Textarea::make('student_outcome')
                            ->label('Hasil / Ketuntasan Siswa')
                            ->required(),
                    ])
                    ->visible(function (Get $get) {
                        $categoryId = $get('category_id');
                        if (! $categoryId) {
                            return false;
                        }
                        
                        $category = ReportCategory::find($categoryId);
                        return $category ? $category->is_teaching : false;
                    })
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('activity_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Uraian')
                    ->limit(50),
                Tables\Columns\IconColumn::make('evidence_link')
                    ->label('Eviden')
                    ->icon('heroicon-o-link')
                    ->url(fn ($record) => $record->evidence_link)
                    ->openUrlInNewTab()
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
