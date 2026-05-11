<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Services\RatioService;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;

class FinancialRatiosPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Rasio Keuangan';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.financial-ratios';

    public ?int $period_id = null;

    public function mount(): void
    {
        $this->period_id = Period::where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->value('id')
            ?? Period::orderBy('start_date', 'desc')->value('id');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('period_id')
                ->label('Periode')
                ->options(fn () => Period::orderBy('start_date', 'desc')->pluck('name', 'id'))
                ->searchable()
                ->live(),
        ]);
    }

    public function getData(): array
    {
        return RatioService::calculateAll($this->period_id);
    }

    public function getCategoryColor(string $category): string
    {
        return match ($category) {
            'liquidity' => 'info',
            'profitability' => 'success',
            'solvency' => 'warning',
            'efficiency' => 'gray',
            default => 'primary',
        };
    }

    public function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'liquidity' => 'Likuiditas',
            'profitability' => 'Profitabilitas',
            'solvency' => 'Solvabilitas',
            'efficiency' => 'Efisiensi',
            default => $category,
        };
    }

    public function getCategoryIcon(string $category): string
    {
        return match ($category) {
            'liquidity' => 'heroicon-o-calculator',
            'profitability' => 'heroicon-o-arrow-trending-up',
            'solvency' => 'heroicon-o-shield-check',
            'efficiency' => 'heroicon-o-clock',
            default => 'heroicon-o-chart-bar',
        };
    }
}
