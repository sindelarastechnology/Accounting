<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\ExpenseResource;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Period;
use App\Services\ExpenseService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function handleRecordCreation(array $data): Expense
    {
        $period = Period::findOrFail($data['period_id']);
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $account = Account::findOrFail($data['account_id']);
        if ($account->is_header || !$account->is_active) {
            throw new \Exception("Akun '{$account->name}' tidak valid untuk pencatatan beban.");
        }

        $year = Carbon::parse($data['date'])->format('Y');
        $lastSequence = \Illuminate\Support\Facades\DB::table('expenses')
            ->whereYear('date', $year)
            ->lockForUpdate()
            ->count();
        $sequence = $lastSequence + 1;
        $number = 'EXP-' . $year . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        return Expense::create([
            'number' => $number,
            'period_id' => $data['period_id'],
            'date' => $data['date'],
            'name' => $data['name'],
            'account_id' => $data['account_id'],
            'wallet_id' => $data['wallet_id'],
            'contact_id' => $data['contact_id'] ?? null,
            'amount' => $data['amount'],
            'receipt_number' => $data['receipt_number'] ?? null,
            'attachment' => $data['attachment'] ?? null,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? Auth::id(),
        ]);
    }
}
