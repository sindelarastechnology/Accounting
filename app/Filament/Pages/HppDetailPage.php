<?php

namespace App\Filament\Pages;

use App\Models\InventoryMovement;
use App\Models\Period;
use App\Models\Product;
use App\Services\FifoCostService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class HppDetailPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'HPP per Produk';

    protected static string $view = 'filament.pages.hpp-detail';

    public ?int $period_id = null;

    public ?int $product_id = null;

    public array $productData = [];

    public array $summary = [];

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
            Select::make('product_id')
                ->label('Produk (opsional)')
                ->options(fn () => Product::where('type', 'goods')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn ($p) => [$p->id => $p->name]))
                ->searchable()
                ->nullable()
                ->live(),
        ])->columns(3);
    }

    public function getData(): array
    {
        if (!$this->period_id) {
            return ['products' => [], 'total_hpp' => 0];
        }

        $period = Period::find($this->period_id);
        if (!$period) {
            return ['products' => [], 'total_hpp' => 0];
        }

        $dateFrom = $period->start_date->format('Y-m-d');
        $dateTo = $period->end_date->format('Y-m-d');

        $products = Product::where('type', 'goods')
            ->where('is_active', true)
            ->when($this->product_id, fn ($q) => $q->where('id', $this->product_id))
            ->orderBy('name')
            ->get();

        $totalHpp = 0;
        $productRows = [];

        foreach ($products as $product) {
            // Purchases in period
            $purchases = (float) InventoryMovement::where('product_id', $product->id)
                ->where('type', 'in')
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->sum('total_cost');

            // Sales/out movements in period (COGS)
            $cogsValue = (float) InventoryMovement::where('product_id', $product->id)
                ->where('type', 'out')
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->sum(DB::raw('ABS(total_cost)'));

            $currentStockValue = FifoCostService::getCurrentStockValue($product->id);
            $currentQty = (float) $product->stock_on_hand;

            $productRows[] = [
                'product' => $product,
                'opening_qty' => 0,
                'opening_value' => 0,
                'purchases' => $purchases,
                'cogs' => $cogsValue,
                'closing_qty' => $currentQty,
                'closing_value' => $currentStockValue,
            ];

            $totalHpp += $cogsValue;
        }

        return [
            'products' => $productRows,
            'total_hpp' => $totalHpp,
            'period_name' => $period->name,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->action(fn () => $this->dispatch('print-report')),
        ];
    }
}
