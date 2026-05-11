<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Role;

class UserManagementPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Manajemen User';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.user-management';

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->with('roles'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('roles.name')
                    ->label('Role')
                    ->getStateUsing(fn ($record) => $record->roles->pluck('name')->join(', ') ?: '-')
                    ->colors([
                        'success' => 'super_admin',
                        'primary' => 'accountant',
                        'warning' => 'cashier',
                        'gray' => 'viewer',
                    ])
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(Role::pluck('name', 'name')->toArray())
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('roles', fn ($q) => $q->where('name', $data['value']));
                        }
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('changeRole')
                    ->label('Ubah Role')
                    ->icon('heroicon-o-shield-check')
                    ->form([
                        \Filament\Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options(Role::pluck('name', 'name')->toArray())
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->syncRoles([$data['role']]);
                        Notification::make()
                            ->title('Role berhasil diubah')
                            ->body("User {$record->name} sekarang memiliki role: {$data['role']}")
                            ->success()
                            ->send();
                    }),

                \Filament\Tables\Actions\Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password')
                    ->modalDescription('Email reset password akan dikirim ke user ini. Lanjutkan?')
                    ->modalSubmitActionLabel('Kirim Email Reset')
                    ->action(function (User $record) {
                        $record->sendPasswordResetNotification(
                            \Illuminate\Support\Str::random(60)
                        );
                        Notification::make()
                            ->title('Email reset dikirim')
                            ->body('Link reset password telah dikirim ke ' . $record->email)
                            ->success()
                            ->send();
                    }),

                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }
}
