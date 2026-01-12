<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Section;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\SchoolSetting;

class SchoolIdentity extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Identitas Madrasah';
    protected static ?string $title = 'Identitas Madrasah';

    protected static string $view = 'filament.pages.school-identity';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SchoolSetting::firstOrNew();
        $this->form->fill($settings->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identitas Madrasah')
                    ->schema([
                        TextInput::make('school_name')
                            ->label('Nama Madrasah')
                            ->required(),
                        Textarea::make('school_address')
                            ->label('Alamat Madrasah')
                            ->rows(3),
                    ]),
                Section::make('Identitas Kepala Madrasah')
                    ->schema([
                        TextInput::make('headmaster_name')
                            ->label('Nama Kepala Madrasah')
                            ->required(),
                        TextInput::make('headmaster_nip')
                            ->label('NIP Kepala Madrasah'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = SchoolSetting::firstOrNew();
        $settings->fill($data);
        $settings->save();

        Notification::make()
            ->success()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->send();
    }
}
