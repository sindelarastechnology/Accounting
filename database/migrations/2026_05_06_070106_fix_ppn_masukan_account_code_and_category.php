<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $oldAccount = DB::table('accounts')->where('code', '2210')->first();
        if (!$oldAccount) {
            return;
        }

        $parentAssetId = DB::table('accounts')->where('code', '1000')->value('id');

        DB::table('accounts')
            ->where('code', '2210')
            ->update([
                'code' => '1600',
                'name' => 'PPN Masukan',
                'category' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $parentAssetId,
            ]);

        $oldAccountId = $oldAccount->id;
        $newAccountId = DB::table('accounts')->where('code', '1600')->value('id');

        DB::table('tax_rules')
            ->where('debit_account_id', $oldAccountId)
            ->update(['debit_account_id' => $newAccountId]);

        DB::table('tax_rules')
            ->where('credit_account_id', $oldAccountId)
            ->update(['credit_account_id' => $newAccountId]);

        DB::table('settings')
            ->where('key', 'ppn_input_account_id')
            ->update(['value' => $newAccountId]);
    }

    public function down(): void
    {
        $parentLiabId = DB::table('accounts')->where('code', '2000')->value('id');

        DB::table('accounts')
            ->where('code', '1600')
            ->update([
                'code' => '2210',
                'name' => 'Hutang PPN Masukan',
                'category' => 'liability',
                'normal_balance' => 'debit',
                'parent_id' => $parentLiabId,
            ]);
    }
};
