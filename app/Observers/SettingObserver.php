<?php

namespace App\Observers;

use App\Helpers\AccountResolver;
use App\Models\Setting;

class SettingObserver
{
    public function saved(Setting $setting): void
    {
        AccountResolver::clearCache();
    }
}
