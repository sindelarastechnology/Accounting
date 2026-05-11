<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class AccountResolver
{
    /**
     * Resolve account ID from setting or fallback to account code
     *
     * @param string $settingKey
     * @param string $fallbackCode
     * @return int
     * @throws RuntimeException
     */
    public static function resolve(string $settingKey, string $fallbackCode): int
    {
        $cacheKey = "account_resolver_{$settingKey}";

        return Cache::remember($cacheKey, 3600, function () use ($settingKey, $fallbackCode) {
            $accountId = Setting::get($settingKey);

            if ($accountId) {
                return (int) $accountId;
            }

            $accountId = Account::where('code', $fallbackCode)->value('id');

            if (!$accountId) {
                throw new RuntimeException(
                    "Account with code '{$fallbackCode}' not found. Please set '{$settingKey}' in settings."
                );
            }

            return (int) $accountId;
        });
    }

    /**
     * Get Accounts Receivable account ID
     *
     * @return int
     */
    public static function receivable(): int
    {
        return self::resolve('ar_account_id', '1300-00-020');
    }

    /**
     * Get Accounts Payable account ID
     *
     * @return int
     */
    public static function payable(): int
    {
        return self::resolve('ap_account_id', '2100-00-020');
    }

    /**
     * Get Revenue account ID
     *
     * @return int
     */
    public static function revenue(): int
    {
        return self::resolve('revenue_account_id', '4100-00-010');
    }

    /**
     * Get Inventory account ID
     *
     * @return int
     */
    public static function inventory(): int
    {
        return self::resolve('inventory_account_id', '1400-00-010');
    }

    /**
     * Get COGS account ID
     *
     * @return int
     */
    public static function cogs(): int
    {
        return self::resolve('cogs_account_id', '5100-00-010');
    }

    /**
     * Get Tax Payable account ID
     *
     * @return int
     */
    public static function taxPayable(): int
    {
        return self::resolve('ppn_output_account_id', '2100-00-070');
    }

    /**
     * Get Tax Receivable account ID
     *
     * @return int
     */
    public static function taxReceivable(): int
    {
        return self::resolve('ppn_input_account_id', '1500-00-030');
    }

    /**
     * Get Retained Earnings account ID
     *
     * @return int
     */
    public static function retainedEarnings(): int
    {
        return self::resolve('retained_earnings_id', '3200-00-010');
    }

    /**
     * Get Income Summary account ID
     *
     * @return int
     */
    public static function incomeSummary(): int
    {
        return self::resolve('income_summary_id', '3200-00-020');
    }

    /**
     * Get PPh Payable account ID
     *
     * @return int
     */
    public static function pphPayable(): int
    {
        return self::resolve('pph_payable_account_id', '2100-00-071');
    }

    /**
     * Get PPh Prepaid account ID (asset, for PPh withheld by customer)
     *
     * @return int
     */
    public static function pphPrepaid(): int
    {
        return self::resolve('pph_prepaid_account_id', '1500-00-040');
    }

    /**
     * Get Fixed Asset account ID
     *
     * @return int
     */
    public static function fixedAsset(): int
    {
        return self::resolve('fixed_asset_account_id', '1700-00-030');
    }

    /**
     * Get Gain on Asset Disposal account ID
     *
     * @return int
     */
    public static function gainOnDisposal(): int
    {
        return self::resolve('gain_on_disposal_account_id', '4200-00-040');
    }

    /**
     * Get Loss on Asset Disposal account ID
     *
     * @return int
     */
    public static function lossOnDisposal(): int
    {
        return self::resolve('loss_on_disposal_account_id', '5200-00-010');
    }

    /**
     * Get Expense account ID (general)
     *
     * @return int
     */
    public static function expense(): int
    {
        return self::resolve('expense_account_id', '6110-00-010');
    }

    /**
     * Get PPN Input account ID
     *
     * @return int
     */
    public static function ppnInput(): int
    {
        return self::resolve('ppn_input_account_id', '1500-00-030');
    }

    /**
     * Clear all cached account resolver values
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $keys = [
            'ar_account_id',
            'ap_account_id',
            'revenue_account_id',
            'inventory_account_id',
            'cogs_account_id',
            'ppn_output_account_id',
            'ppn_input_account_id',
            'retained_earnings_id',
            'income_summary_id',
            'pph_payable_account_id',
            'pph_prepaid_account_id',
            'fixed_asset_account_id',
            'gain_on_disposal_account_id',
            'loss_on_disposal_account_id',
            'expense_account_id',
        ];

        foreach ($keys as $key) {
            Cache::forget("account_resolver_{$key}");
        }
    }
}
