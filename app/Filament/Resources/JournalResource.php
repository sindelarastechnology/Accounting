<?php

namespace App\Filament\Resources;

use App\Exceptions\AccountingImbalanceException;
use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\JournalResource\Pages;
use App\Models\Account;
use App\Models\Journal;
use App\Models\Period;
use App\Models\Wallet;
use App\Services\JournalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?string $navigationLabel = 'Jurnal Umum';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Jurnal')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->live(),
                                Forms\Components\Select::make('period_id')
                                    ->label('Periode')
                                    ->options(function () {
                                        $periods = Period::where('is_closed', false)->orderBy('start_date')->pluck('name', 'id');
                                        if ($periods->isEmpty()) {
                                            $periods = Period::orderBy('start_date')->pluck('name', 'id');
                                        }
                                        return $periods;
                                    })
                                    ->searchable()
                                    ->default(function () {
                                        return Period::where('is_closed', false)
                                            ->where('start_date', '<=', now())
                                            ->where('end_date', '>=', now())
                                            ->value('id')
                                            ?? Period::where('is_closed', false)->value('id');
                                    })
                                    ->helperText('Hanya periode aktif yang ditampilkan.')
                                    ->required(),
                                Forms\Components\TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Baris Jurnal')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(6)
                                    ->schema([
                                        Forms\Components\Select::make('account_id')
                                            ->label('Akun')
                                            ->options(function () {
                                                return Account::detail()
                                                    ->active()
                                                    ->orderBy('code')
                                                    ->get()
                                                    ->mapWithKeys(fn ($a) => [
                                                        $a->id => "[{$a->code}] {$a->name}",
                                                    ]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->reactive()
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('debit_amount')
                                            ->label('Debit')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->prefix('Rp')
                                            ->reactive()
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                if ((float) ($state ?? 0) > 0) {
                                                    $set('credit_amount', 0);
                                                }
                                            })
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('credit_amount')
                                            ->label('Kredit')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->prefix('Rp')
                                            ->reactive()
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                if ((float) ($state ?? 0) > 0) {
                                                    $set('debit_amount', 0);
                                                }
                                            })
                                            ->columnSpan(1),
                                        Forms\Components\Select::make('wallet_id')
                                            ->label('Dompet')
                                            ->options(fn () => Wallet::active()->pluck('name', 'id'))
                                            ->searchable()
                                            ->nullable()
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('description')
                                            ->label('Keterangan')
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->minItems(2)
                            ->defaultItems(2)
                            ->addActionLabel('Tambah Baris')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('total_debit')
                                    ->label('Total Debit')
                                    ->content(function (Forms\Get $get) {
                                        $lines = $get('lines') ?? [];
                                        $total = collect($lines)->sum(fn ($line) => (float) ($line['debit_amount'] ?? 0));
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    })
                                    ->reactive(),
                                Forms\Components\Placeholder::make('total_credit')
                                    ->label('Total Kredit')
                                    ->content(function (Forms\Get $get) {
                                        $lines = $get('lines') ?? [];
                                        $total = collect($lines)->sum(fn ($line) => (float) ($line['credit_amount'] ?? 0));
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    })
                                    ->reactive(),
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('balance_warning')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                $lines = $get('lines') ?? [];
                                $totalDebit = collect($lines)->sum(fn ($line) => (float) ($line['debit_amount'] ?? 0));
                                $totalCredit = collect($lines)->sum(fn ($line) => (float) ($line['credit_amount'] ?? 0));

                                if (abs($totalDebit - $totalCredit) > 0.0001) {
                                    return new HtmlString('<span style="color: #dc2626; font-weight: bold; font-size: 1.1em;">⚠ Jurnal tidak balance! Debit ≠ Kredit</span>');
                                }
                                if (count($lines) >= 2 && $totalDebit > 0) {
                                    return new HtmlString('<span style="color: #16a34a; font-weight: bold;">✓ Jurnal balance</span>');
                                }
                                return '';
                            })
                            ->reactive()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. Jurnal')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual' => 'info',
                        'sale', 'opening' => 'success',
                        'purchase', 'expense' => 'warning',
                        'payment' => 'primary',
                        'stock_opname' => 'secondary',
                        'closing' => 'dark',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Journal::sources()[$state] ?? ucfirst($state)),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'normal', 'opening' => 'success',
                        'reversal' => 'warning',
                        'void' => 'danger',
                        'closing' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Journal::types()[$state] ?? ucfirst($state)),
                Tables\Columns\TextColumn::make('lines')
                    ->label('Total')
                    ->getStateUsing(function (Journal $record) {
                        $total = $record->lines->sum('debit_amount');
                        return 'Rp ' . number_format((float) $total, 0, ',', '.');
                    }),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->label('Sumber')
                    ->options(Journal::sources())
                    ->multiple()
                    ->default('manual'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(Journal::types()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('void')
                    ->label('Void')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Journal $record) => $record->type === 'normal' || $record->type === 'opening')
                    ->requiresConfirmation()
                    ->modalHeading('Void Jurnal')
                    ->modalDescription('Jurnal ini akan dibatalkan dengan membuat jurnal reversal.')
                    ->modalSubmitActionLabel('Void Jurnal')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Void')
                            ->required()
                            ->minLength(10)
                            ->rows(3)
                            ->placeholder('Jelaskan alasan pembatalan jurnal ini (min. 10 karakter)'),
                    ])
                    ->action(function (Journal $record, array $data) {
                        try {
                            JournalService::voidJournal($record, $data['reason']);

                            Notification::make()
                                ->title('Jurnal berhasil di-void')
                                ->success()
                                ->send();
                        } catch (PeriodClosedException $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        } catch (InvalidAccountException $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'view' => Pages\ViewJournal::route('/{record}'),
        ];
    }
}
