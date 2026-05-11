@php
    $listrik = App\Models\Account::where('code', '6300')->value('id');
    $gaji = App\Models\Account::where('code', '6100')->value('id');
    $sewa = App\Models\Account::where('code', '6200')->value('id');
    $transportasi = App\Models\Account::where('code', '6500')->value('id');
    $perlengkapan = App\Models\Account::where('code', '6400')->value('id');
    $admin = App\Models\Account::where('code', '6800')->value('id');
@endphp
<div class="flex gap-1 flex-wrap items-center">
    <span class="text-xs text-gray-500 mr-1">Cepat pilih:</span>
    <button type="button"
        x-on:click="$wire.$set('data.account_id', '{{ $listrik }}')"
        class="px-2 py-1 text-xs border rounded hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-500">
        Listrik
    </button>
    <button type="button"
        x-on:click="$wire.$set('data.account_id', '{{ $listrik }}')"
        class="px-2 py-1 text-xs border rounded hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-500">
        Air
    </button>
    <button type="button"
        x-on:click="$wire.$set('data.account_id', '{{ $admin }}')"
        class="px-2 py-1 text-xs border rounded hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-500">
        Internet
    </button>
    <button type="button"
        x-on:click="$wire.$set('data.account_id', '{{ $transportasi }}')"
        class="px-2 py-1 text-xs border rounded hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-500">
        Transportasi
    </button>
    <button type="button"
        x-on:click="$wire.$set('data.account_id', '{{ $perlengkapan }}')"
        class="px-2 py-1 text-xs border rounded hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-500">
        Perlengkapan
    </button>
    <button type="button"
        x-on:click="$wire.$set('data.account_id', '{{ $gaji }}')"
        class="px-2 py-1 text-xs border rounded hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-500">
        Gaji
    </button>
    <button type="button"
        x-on:click="$wire.$set('data.account_id', '{{ $admin }}')"
        class="px-2 py-1 text-xs border rounded hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-500">
        Lainnya
    </button>
</div>
