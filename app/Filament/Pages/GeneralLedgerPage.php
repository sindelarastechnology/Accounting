<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Services\LedgerService;
use App\Traits\HasReportExport;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;

class GeneralLedgerPage extends Page
{
    use HasReportExport;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Buku Besar';

    protected static ?string $slug = 'reports/general-ledger';

    protected static string $view = 'filament.pages.general-ledger';

    public string $activeTab = 'general';

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?int $contact_id = null;

    public ?int $product_id = null;

    public ?int $account_id = null;

    public ?string $category_type = null;

    public bool $show_empty = false;

    public function mount(): void
    {
        $this->date_to = now()->format('Y-m-d');
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('date_from')
                ->label('Dari Tanggal')
                ->required()
                ->live(),
            DatePicker::make('date_to')
                ->label('Sampai Tanggal')
                ->required()
                ->live(),
            Select::make('category_type')
                ->label('Tipe Akun')
                ->placeholder('Semua')
                ->options([
                    'all' => 'Semua',
                    'asset' => 'Aset',
                    'liability' => 'Kewajiban',
                    'equity' => 'Modal',
                    'revenue' => 'Pendapatan',
                    'expense' => 'Biaya',
                ])
                ->nullable()
                ->live()
                ->visible(fn () => $this->activeTab === 'general'),
            Select::make('account_id')
                ->label('Akun')
                ->placeholder('Semua Akun')
                ->options(fn () => Account::where('is_header', false)->orderBy('code')->get()->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} - {$a->name}"]))
                ->searchable()
                ->nullable()
                ->live()
                ->visible(fn () => $this->activeTab === 'general'),
            Toggle::make('show_empty')
                ->label('Tampilkan akun kosong')
                ->live()
                ->visible(fn () => $this->activeTab === 'general'),
            Select::make('contact_id')
                ->label('Kontak')
                ->placeholder('Semua Kontak')
                ->options(fn () => Contact::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->live()
                ->visible(fn () => in_array($this->activeTab, ['receivable', 'payable'])),
            Select::make('product_id')
                ->label('Produk')
                ->placeholder('Semua Produk')
                ->options(fn () => Product::where('type', 'goods')->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->live()
                ->visible(fn () => $this->activeTab === 'inventory'),
        ])->columns(5);
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportExcel()),
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(fn () => $this->exportPdf()),
            Action::make('printReport')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->action(fn () => $this->printReport()),
        ];
    }

    public function getData(): array
    {
        if ($this->activeTab === 'inventory') {
            return LedgerService::getInventoryLedger(
                $this->product_id,
                $this->date_from,
                $this->date_to
            );
        }

        if ($this->activeTab === 'payable') {
            return LedgerService::getApLedger(
                $this->contact_id,
                $this->date_from,
                $this->date_to
            );
        }

        if ($this->activeTab === 'receivable') {
            return LedgerService::getArLedger(
                $this->contact_id,
                $this->date_from,
                $this->date_to
            );
        }

        return LedgerService::getGeneralLedger(
            accountId: $this->account_id,
            dateFrom: $this->date_from,
            dateTo: $this->date_to,
            categoryType: $this->category_type,
            showEmpty: $this->show_empty,
        );
    }

    protected function pdfView(): string
    {
        return 'pdf.general_ledger';
    }

    protected function pdfOrientation(): string
    {
        return 'landscape';
    }

    protected function reportTitle(): string
    {
        return 'Buku_Besar';
    }

    public function exportExcel()
    {
        $data = $this->getData();

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($this->activeTab === 'general') {
                fputcsv($handle, ['Kode Akun', 'Nama Akun', 'Kategori', 'Saldo Awal', 'Debit', 'Kredit', 'Saldo Akhir']);
                foreach ($data['accounts'] as $item) {
                    fputcsv($handle, [
                        $item['account']->code,
                        $item['account']->name,
                        $item['category_label'],
                        $item['opening_balance'],
                        $item['total_debit'],
                        $item['total_credit'],
                        $item['closing_balance'],
                    ]);
                    foreach ($item['transactions'] as $tx) {
                        fputcsv($handle, [
                            $tx['date'],
                            '',
                            $tx['description'],
                            '',
                            $tx['debit'],
                            $tx['credit'],
                            $tx['balance'],
                        ]);
                    }
                }
            } elseif (in_array($this->activeTab, ['receivable', 'payable'])) {
                fputcsv($handle, ['Kontak', 'Saldo Awal', 'Debit', 'Kredit', 'Saldo Akhir']);
                foreach ($data['contacts'] as $item) {
                    fputcsv($handle, [
                        $item['contact']->name,
                        $item['opening_balance'],
                        collect($item['transactions'])->sum('debit'),
                        collect($item['transactions'])->sum('credit'),
                        $item['closing_balance'],
                    ]);
                }
            } elseif ($this->activeTab === 'inventory') {
                fputcsv($handle, ['Produk', 'Stok Awal', 'Masuk', 'Keluar', 'Stok Akhir']);
                foreach ($data['products'] as $item) {
                    fputcsv($handle, [
                        $item['product']->name,
                        $item['opening_stock'],
                        collect($item['transactions'])->sum('qty_in'),
                        collect($item['transactions'])->sum('qty_out'),
                        $item['closing_stock'],
                    ]);
                }
            }

            fclose($handle);
        }, 'Buku_Besar_' . now()->format('Ymd') . '.csv');
    }
}
