<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\InventoryMovement;
use App\Models\Journal;
use App\Models\Setting;
use App\Models\Wallet;
use App\Observers\AccountObserver;
use App\Observers\InventoryMovementObserver;
use App\Observers\JournalObserver;
use App\Observers\SettingObserver;
use App\Observers\WalletObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Account::observe(AccountObserver::class);
        Wallet::observe(WalletObserver::class);
        Journal::observe(JournalObserver::class);
        InventoryMovement::observe(InventoryMovementObserver::class);
        Setting::observe(SettingObserver::class);

        Relation::morphMap([
            'invoices' => \App\Models\Invoice::class,
            'purchases' => \App\Models\Purchase::class,
        ]);
    }
}
