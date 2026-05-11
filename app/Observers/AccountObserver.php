<?php

namespace App\Observers;

use App\Helpers\AccountResolver;
use App\Models\Account;

class AccountObserver
{
    public function saved(Account $account): void
    {
        AccountResolver::clearCache();
    }

    public function deleted(Account $account): void
    {
        AccountResolver::clearCache();
    }
}
