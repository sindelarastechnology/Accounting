<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class AuditLogPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.audit-log';

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query()->with(['user', 'auditable']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                BadgeColumn::make('event')
                    ->label('Aksi')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                        'info' => 'posted',
                        'gray' => 'void',
                        'purple' => 'period_reopened',
                    ])
                    ->searchable(),

                TextColumn::make('auditable_type')
                    ->label('Tipe')
                    ->formatStateUsing(function ($state) {
                        $class = class_basename($state);
                        return str_replace('App\\Models\\', '', $class);
                    })
                    ->toggleable(),

                TextColumn::make('auditable_id')
                    ->label('ID')
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(30)
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Aksi')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'posted' => 'Posted',
                        'void' => 'Void',
                        'period_reopened' => 'Period Reopened',
                    ]),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(User::pluck('name', 'id')->toArray()),

                SelectFilter::make('auditable_type')
                    ->label('Tipe')
                    ->options(function () {
                        return AuditLog::query()
                            ->distinct()
                            ->pluck('auditable_type')
                            ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                            ->toArray();
                    }),

                Filter::make('created_at')
                    ->label('Tanggal')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn ($q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'], fn ($q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Audit Log')
                    ->modalContent(function ($record) {
                        return view('filament.components.audit-log-detail', ['record' => $record]);
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
