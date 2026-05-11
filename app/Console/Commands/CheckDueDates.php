<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\User;
use App\Notifications\DueDateNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDueDates extends Command
{
    protected $signature = 'accounting:check-due-dates';
    protected $description = 'Check for due invoices and purchases, send notifications';

    public function handle(): void
    {
        $now = Carbon::today();
        $threshold = $now->copy()->addDays(7);

        $dueInvoices = Invoice::whereIn('status', ['posted', 'partially_paid'])
            ->where('due_date', '<=', $threshold)
            ->whereNull('deleted_at')
            ->get();

        $duePurchases = Purchase::whereIn('status', ['posted', 'partially_paid'])
            ->where('due_date', '<=', $threshold)
            ->whereNull('deleted_at')
            ->get();

        if ($dueInvoices->isEmpty() && $duePurchases->isEmpty()) {
            $this->info('Tidak ada tagihan/pembelian yang akan jatuh tempo.');
            return;
        }

        $users = User::all();

        foreach ($users as $user) {
            foreach ($dueInvoices as $invoice) {
                $daysLeft = $now->diffInDays($invoice->due_date, false);
                $isOverdue = $daysLeft < 0;
                $label = $isOverdue ? 'TERLAMBAT' : 'Akan jatuh tempo';

                $user->notify(new DueDateNotification(
                    title: "Invoice {$invoice->number} - {$label}",
                    body: match (true) {
                        $isOverdue => "Invoice {$invoice->number} sudah terlambat {$daysLeft} hari dari jatuh tempo.",
                        $daysLeft == 0 => "Invoice {$invoice->number} jatuh tempo hari ini.",
                        default => "Invoice {$invoice->number} akan jatuh tempo dalam {$daysLeft} hari.",
                    },
                    color: $isOverdue ? 'danger' : 'warning',
                    url: url("/admin/invoices/{$invoice->id}"),
                ));
            }

            foreach ($duePurchases as $purchase) {
                $daysLeft = $now->diffInDays($purchase->due_date, false);
                $isOverdue = $daysLeft < 0;
                $label = $isOverdue ? 'TERLAMBAT' : 'Akan jatuh tempo';

                $user->notify(new DueDateNotification(
                    title: "Purchase {$purchase->number} - {$label}",
                    body: match (true) {
                        $isOverdue => "Purchase {$purchase->number} sudah terlambat {$daysLeft} hari dari jatuh tempo.",
                        $daysLeft == 0 => "Purchase {$purchase->number} jatuh tempo hari ini.",
                        default => "Purchase {$purchase->number} akan jatuh tempo dalam {$daysLeft} hari.",
                    },
                    color: $isOverdue ? 'danger' : 'warning',
                    url: url("/admin/purchases/{$purchase->id}"),
                ));
            }
        }

        $total = $dueInvoices->count() + $duePurchases->count();
        $this->info("Notifikasi jatuh tempo dikirim ke {$users->count()} user untuk {$total} dokumen.");
    }
}
