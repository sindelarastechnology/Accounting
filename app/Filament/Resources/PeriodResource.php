<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PeriodResource\Pages;
use App\Models\Journal;
use App\Models\Period;
use App\Models\User;
use App\Services\PeriodService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PeriodResource extends Resource
{
    protected static ?string $model = Period::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Periode';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Periode')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Contoh: Januari 2026'),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->native(false),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->after('start_date')
                    ->native(false),

                Forms\Components\Toggle::make('is_closed')
                    ->label('Ditutup')
                    ->disabled()
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Periode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('is_closed')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $record->is_closed ? 'Ditutup' : 'Aktif')
                    ->colors([
                        'success' => 'Aktif',
                        'danger' => 'Ditutup',
                    ]),

                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Ditutup Pada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('closedBy.name')
                    ->label('Ditutup Oleh')
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_closed')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'closed' => 'Ditutup',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value']) {
                        'active' => $query->where('is_closed', false),
                        'closed' => $query->where('is_closed', true),
                        default => $query,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('closePeriod')
                    ->label('Tutup Periode')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Tutup Periode')
                    ->modalDescription(fn ($record) => "Apakah Anda yakin ingin menutup periode \"{$record->name}\"?")
                    ->modalContent(fn () => view('filament.components.close-period-modal-content'))
                    ->modalSubmitActionLabel('Ya, Tutup Periode')
                    ->hidden(fn ($record) => $record->is_closed)
                    ->action(function ($record) {
                        try {
                            PeriodService::closePeriod($record, auth()->id());
                            Notification::make()
                                ->title('Periode berhasil ditutup')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menutup periode')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reopenPeriod')
                    ->label('Buka Kembali Periode')
                    ->icon('heroicon-o-lock-open')
                    ->color('danger')
                    ->modalHeading('Buka Kembali Periode')
                    ->modalDescription(fn ($record) => "Membuka kembali periode \"{$record->name}\" akan mem-void semua jurnal penutup. Gunakan hanya jika benar-benar diperlukan.")
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Membuka Kembali')
                            ->required()
                            ->minLength(20)
                            ->rows(4)
                            ->placeholder('Jelaskan alasan Anda membuka kembali periode ini...'),
                    ])
                    ->modalSubmitActionLabel('Ya, Buka Kembali')
                    ->hidden(fn ($record) => !$record->is_closed)
                    ->action(function ($record, array $data) {
                        try {
                            PeriodService::reopenPeriod($record, auth()->id(), $data['reason']);
                            Notification::make()
                                ->title('Periode berhasil dibuka kembali')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal membuka kembali periode')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('createNextPeriod')
                    ->label('Buat Periode Berikutnya')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->action(function ($record) {
                        $nextPeriod = PeriodService::getNextPeriod($record);
                        if ($nextPeriod) {
                            Notification::make()
                                ->title('Periode berikutnya sudah ada')
                                ->body("Periode \"{$nextPeriod->name}\" sudah ada.")
                                ->info()
                                ->send();
                            return;
                        }

                        try {
                            $newPeriod = PeriodService::createNextPeriod($record);
                            Notification::make()
                                ->title('Periode berhasil dibuat')
                                ->body("Periode \"{$newPeriod->name}\" telah dibuat.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal membuat periode')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->is_closed),

                Tables\Actions\DeleteAction::make()
                    ->hidden(function ($record) {
                        return Journal::where('period_id', $record->id)->exists();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPeriods::route('/'),
        ];
    }
}
