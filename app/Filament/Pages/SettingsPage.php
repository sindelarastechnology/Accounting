<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Sistem';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.settings';

    // Company Info
    public $company_name = '';
    public $company_address = '';
    public $company_phone = '';
    public $company_email = '';
    public $company_npwp = '';
    public $company_logo = null;

    // Default Accounts
    public $ar_account_id = null;
    public $ap_account_id = null;
    public $revenue_account_id = null;
    public $cogs_account_id = null;
    public $inventory_account_id = null;
    public $equity_account_id = null;
    public $retained_earnings_id = null;
    public $income_summary_id = null;
    public $ppn_output_account_id = null;
    public $ppn_input_account_id = null;
    public $pph_payable_account_id = null;
    public $stock_gain_account_id = null;
    public $stock_loss_account_id = null;

    // Numbering
    public $invoice_prefix = 'INV';
    public $purchase_prefix = 'PO';
    public $journal_prefix = 'JNL';
    public $payment_prefix = 'PAY';
    public $expense_prefix = 'EXP';
    public $receipt_prefix = 'RCP';

    // Currency & Format
    public $currency_code = 'IDR';
    public $currency_symbol = 'Rp';
    public $date_format = 'd/m/Y';
    public $fiscal_year_start = '01';

    // Invoice Settings
    public $invoice_due_days = 30;
    public $invoice_notes_default = '';
    public $invoice_footer_text = '';
    public $auto_post_invoice = false;

    public function mount(): void
    {
        $this->company_name = Setting::get('company_name', '');
        $this->company_address = Setting::get('company_address', '');
        $this->company_phone = Setting::get('company_phone', '');
        $this->company_email = Setting::get('company_email', '');
        $this->company_npwp = Setting::get('company_npwp', '');
        $this->company_logo = Setting::get('company_logo', null);

        $this->ar_account_id = Setting::get('ar_account_id');
        $this->ap_account_id = Setting::get('ap_account_id');
        $this->revenue_account_id = Setting::get('revenue_account_id');
        $this->cogs_account_id = Setting::get('cogs_account_id');
        $this->inventory_account_id = Setting::get('inventory_account_id');
        $this->equity_account_id = Setting::get('equity_account_id');
        $this->retained_earnings_id = Setting::get('retained_earnings_id');
        $this->income_summary_id = Setting::get('income_summary_id');
        $this->ppn_output_account_id = Setting::get('ppn_output_account_id');
        $this->ppn_input_account_id = Setting::get('ppn_input_account_id');
        $this->pph_payable_account_id = Setting::get('pph_payable_account_id');
        $this->stock_gain_account_id = Setting::get('stock_gain_account_id');
        $this->stock_loss_account_id = Setting::get('stock_loss_account_id');

        $this->invoice_prefix = Setting::get('invoice_prefix', 'INV');
        $this->purchase_prefix = Setting::get('purchase_prefix', 'PO');
        $this->journal_prefix = Setting::get('journal_prefix', 'JNL');
        $this->payment_prefix = Setting::get('payment_prefix', 'PAY');
        $this->expense_prefix = Setting::get('expense_prefix', 'EXP');
        $this->receipt_prefix = Setting::get('receipt_prefix', 'RCP');

        $this->currency_code = Setting::get('currency_code', 'IDR');
        $this->currency_symbol = Setting::get('currency_symbol', 'Rp');
        $this->date_format = Setting::get('date_format', 'd/m/Y');
        $this->fiscal_year_start = Setting::get('fiscal_year_start', '01');

        $this->invoice_due_days = Setting::get('invoice_due_days', 30);
        $this->invoice_notes_default = Setting::get('invoice_notes_default', '');
        $this->invoice_footer_text = Setting::get('invoice_footer_text', '');
        $this->auto_post_invoice = Setting::get('auto_post_invoice', false);
    }

    public function save(): void
    {
        $settings = [
            'company_name' => ['value' => $this->company_name, 'type' => 'string'],
            'company_address' => ['value' => $this->company_address, 'type' => 'string'],
            'company_phone' => ['value' => $this->company_phone, 'type' => 'string'],
            'company_email' => ['value' => $this->company_email, 'type' => 'string'],
            'company_npwp' => ['value' => $this->company_npwp, 'type' => 'string'],
            'ar_account_id' => ['value' => $this->ar_account_id, 'type' => 'integer'],
            'ap_account_id' => ['value' => $this->ap_account_id, 'type' => 'integer'],
            'revenue_account_id' => ['value' => $this->revenue_account_id, 'type' => 'integer'],
            'cogs_account_id' => ['value' => $this->cogs_account_id, 'type' => 'integer'],
            'inventory_account_id' => ['value' => $this->inventory_account_id, 'type' => 'integer'],
            'equity_account_id' => ['value' => $this->equity_account_id, 'type' => 'integer'],
            'retained_earnings_id' => ['value' => $this->retained_earnings_id, 'type' => 'integer'],
            'income_summary_id' => ['value' => $this->income_summary_id, 'type' => 'integer'],
            'ppn_output_account_id' => ['value' => $this->ppn_output_account_id, 'type' => 'integer'],
            'ppn_input_account_id' => ['value' => $this->ppn_input_account_id, 'type' => 'integer'],
            'pph_payable_account_id' => ['value' => $this->pph_payable_account_id, 'type' => 'integer'],
            'stock_gain_account_id' => ['value' => $this->stock_gain_account_id, 'type' => 'integer'],
            'stock_loss_account_id' => ['value' => $this->stock_loss_account_id, 'type' => 'integer'],
            'invoice_prefix' => ['value' => $this->invoice_prefix, 'type' => 'string'],
            'purchase_prefix' => ['value' => $this->purchase_prefix, 'type' => 'string'],
            'journal_prefix' => ['value' => $this->journal_prefix, 'type' => 'string'],
            'payment_prefix' => ['value' => $this->payment_prefix, 'type' => 'string'],
            'expense_prefix' => ['value' => $this->expense_prefix, 'type' => 'string'],
            'receipt_prefix' => ['value' => $this->receipt_prefix, 'type' => 'string'],
            'date_format' => ['value' => $this->date_format, 'type' => 'string'],
            'fiscal_year_start' => ['value' => $this->fiscal_year_start, 'type' => 'string'],
            'invoice_due_days' => ['value' => $this->invoice_due_days, 'type' => 'integer'],
            'invoice_notes_default' => ['value' => $this->invoice_notes_default, 'type' => 'string'],
            'invoice_footer_text' => ['value' => $this->invoice_footer_text, 'type' => 'string'],
            'auto_post_invoice' => ['value' => $this->auto_post_invoice, 'type' => 'boolean'],
        ];

        if ($this->company_logo instanceof TemporaryUploadedFile) {
            $path = $this->company_logo->storeAs('public/logos', 'company_logo.' . $this->company_logo->getClientOriginalExtension());
            $settings['company_logo'] = ['value' => str_replace('public/', '', $path), 'type' => 'string'];
        } elseif (is_string($this->company_logo) && !empty($this->company_logo)) {
            $settings['company_logo'] = ['value' => $this->company_logo, 'type' => 'string'];
        }

        foreach ($settings as $key => $data) {
            Setting::set($key, $data['value'], $data['type']);
        }

        Notification::make()
            ->title('Pengaturan berhasil disimpan')
            ->success()
            ->send();
    }

    protected function getAccountOptions(?string $category = null): array
    {
        $query = Account::where('is_header', false)
            ->where('is_active', true)
            ->orderBy('code');

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get()
            ->mapWithKeys(fn ($a) => [$a->id => "[{$a->code}] {$a->name}"])
            ->toArray();
    }

    public function getAllAccountOptions(): array
    {
        return $this->getAccountOptions();
    }

    public function getRevenueAccountOptions(): array
    {
        return $this->getAccountOptions('revenue');
    }

    public function getCogsAccountOptions(): array
    {
        return $this->getAccountOptions('cogs');
    }

    public function getAssetAccountOptions(): array
    {
        return $this->getAccountOptions('asset');
    }

    public function getEquityAccountOptions(): array
    {
        return $this->getAccountOptions('equity');
    }

    public function getLiabilityAccountOptions(): array
    {
        return $this->getAccountOptions('liability');
    }

    public function getExpenseAccountOptions(): array
    {
        return $this->getAccountOptions('expense');
    }
}
