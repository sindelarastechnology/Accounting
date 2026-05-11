<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Account;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Kontak';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Kontak')
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(150),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipe')
                            ->options(Contact::types())
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(30),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('city')
                            ->label('Kota')
                            ->maxLength(80),
                        Forms\Components\TextInput::make('tax_number')
                            ->label('NPWP / No. Pajak')
                            ->maxLength(30),
                    ]),
                Forms\Components\Textarea::make('address')
                    ->label('Alamat')
                    ->columnSpanFull()
                    ->rows(3),
                Forms\Components\TextInput::make('contact_person')
                    ->label('Contact Person')
                    ->maxLength(100)
                    ->helperText('Nama orang yang bisa dihubungi'),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('ar_account_id')
                            ->label('Akun Piutang')
                            ->options(fn () => Account::byCategory('asset')->detail()->active()->pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Akun piutang untuk customer ini'),
                        Forms\Components\Select::make('ap_account_id')
                            ->label('Akun Hutang')
                            ->options(fn () => Account::byCategory('liability')->detail()->active()->pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Akun hutang untuk supplier ini'),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->required(),
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'customer' => 'success',
                        'supplier' => 'danger',
                        'both' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Contact::types()[$state] ?? $state),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Kota')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(Contact::types()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
