<x-filament-panels::page>
    <div x-data="{ active: 'overview', tab: 'general' }" x-cloak class="flex flex-col -mx-6 -mb-6 min-h-[calc(100vh-8rem)]">

        {{-- Navigation: Category Tabs + Section Pills --}}
        <div class="shrink-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            {{-- Title bar --}}
            <div class="px-4 lg:px-6 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h1 class="text-base font-bold text-gray-900 dark:text-white">Panduan Penggunaan</h1>
            </div>

            {{-- Category tabs --}}
            <div class="px-4 lg:px-6 pt-2 pb-1 flex gap-2 overflow-x-auto hide-scrollbar">
                <button @click="tab='general'; active='overview'" :class="tab==='general' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 ring-1 ring-primary-300 dark:ring-primary-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-800'" class="px-4 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap">General</button>
                <button @click="tab='modul'; active='penjualan'" :class="tab==='modul' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 ring-1 ring-primary-300 dark:ring-primary-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-800'" class="px-4 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap">Modul</button>

            </div>

            {{-- General sections --}}
            <div x-show="tab==='general'" class="px-4 lg:px-6 pb-3 pt-1 flex flex-wrap gap-1.5">
                <button @click="active='overview'" :class="active==='overview' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300 ring-1 ring-primary-300 dark:ring-primary-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Gambaran Umum</button>
                <button @click="active='start'" :class="active==='start' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 ring-1 ring-emerald-300 dark:ring-emerald-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Persiapan Awal</button>
                <button @click="active='flow'" :class="active==='flow' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 ring-1 ring-blue-300 dark:ring-blue-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Alur Transaksi</button>
                <button @click="active='coa'" :class="active==='coa' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300 ring-1 ring-indigo-300 dark:ring-indigo-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Daftar Akun</button>
            </div>

            {{-- Modul sections --}}
            <div x-show="tab==='modul'" class="px-4 lg:px-6 pb-3 pt-1 flex flex-wrap gap-1.5">
                <button @click="active='penjualan'" :class="active==='penjualan' ? 'bg-sky-50 text-sky-700 dark:bg-sky-900/20 dark:text-sky-300 ring-1 ring-sky-300 dark:ring-sky-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Penjualan</button>
                <button @click="active='pembelian'" :class="active==='pembelian' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 ring-1 ring-emerald-300 dark:ring-emerald-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Pembelian</button>
                <button @click="active='kas'" :class="active==='kas' ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300 ring-1 ring-purple-300 dark:ring-purple-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Kas & Bank</button>
                <button @click="active='jurnal'" :class="active==='jurnal' ? 'bg-slate-50 text-slate-700 dark:bg-slate-900/20 dark:text-slate-300 ring-1 ring-slate-300 dark:ring-slate-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Jurnal Manual</button>
                <button @click="active='periode'" :class="active==='periode' ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300 ring-1 ring-amber-300 dark:ring-amber-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Periode & Closing</button>
                <button @click="active='akrual'" :class="active==='akrual' ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300 ring-1 ring-rose-300 dark:ring-rose-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Akrual & Prepaid</button>
                <button @click="active='rekon'" :class="active==='rekon' ? 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300 ring-1 ring-cyan-300 dark:ring-cyan-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Rekonsiliasi Bank</button>
                <button @click="active='pajak'" :class="active==='pajak' ? 'bg-orange-50 text-orange-700 dark:bg-orange-900/20 dark:text-orange-300 ring-1 ring-orange-300 dark:ring-orange-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Pajak</button>
                <button @click="active='aset'" :class="active==='aset' ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-300 ring-1 ring-teal-300 dark:ring-teal-600' : 'bg-gray-100/70 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium transition-all">Aset Tetap</button>
            </div>


        </div>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto bg-gray-50/50 dark:bg-gray-900/50 min-w-0">
            <div class="sticky top-0 z-10 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 px-4 lg:px-6 py-2.5 flex items-center flex-wrap gap-1">
                <template x-if="active==='overview'"><span class="text-xs font-medium text-primary-600 bg-primary-50 dark:bg-primary-900/20 dark:text-primary-400 px-2 py-0.5 rounded-full">Gambaran Umum</span></template>
                <template x-if="active==='start'"><span class="text-xs font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 dark:text-emerald-400 px-2 py-0.5 rounded-full">Persiapan Awal</span></template>
                <template x-if="active==='flow'"><span class="text-xs font-medium text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400 px-2 py-0.5 rounded-full">Alur Transaksi</span></template>
                <template x-if="active==='coa'"><span class="text-xs font-medium text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 px-2 py-0.5 rounded-full">Daftar Akun</span></template>
                <template x-if="active==='penjualan'"><span class="text-xs font-medium text-sky-600 bg-sky-50 dark:bg-sky-900/20 dark:text-sky-400 px-2 py-0.5 rounded-full">Penjualan</span></template>
                <template x-if="active==='pembelian'"><span class="text-xs font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 dark:text-emerald-400 px-2 py-0.5 rounded-full">Pembelian</span></template>
                <template x-if="active==='kas'"><span class="text-xs font-medium text-purple-600 bg-purple-50 dark:bg-purple-900/20 dark:text-purple-400 px-2 py-0.5 rounded-full">Kas & Bank</span></template>
                <template x-if="active==='jurnal'"><span class="text-xs font-medium text-slate-600 bg-slate-50 dark:bg-slate-900/20 dark:text-slate-400 px-2 py-0.5 rounded-full">Jurnal Manual</span></template>
                <template x-if="active==='periode'"><span class="text-xs font-medium text-amber-600 bg-amber-50 dark:bg-amber-900/20 dark:text-amber-400 px-2 py-0.5 rounded-full">Periode & Closing</span></template>
                <template x-if="active==='akrual'"><span class="text-xs font-medium text-rose-600 bg-rose-50 dark:bg-rose-900/20 dark:text-rose-400 px-2 py-0.5 rounded-full">Akrual & Prepaid</span></template>
                <template x-if="active==='rekon'"><span class="text-xs font-medium text-cyan-600 bg-cyan-50 dark:bg-cyan-900/20 dark:text-cyan-400 px-2 py-0.5 rounded-full">Rekonsiliasi Bank</span></template>
                <template x-if="active==='pajak'"><span class="text-xs font-medium text-orange-600 bg-orange-50 dark:bg-orange-900/20 dark:text-orange-400 px-2 py-0.5 rounded-full">Pajak</span></template>
                <template x-if="active==='aset'"><span class="text-xs font-medium text-teal-600 bg-teal-50 dark:bg-teal-900/20 dark:text-teal-400 px-2 py-0.5 rounded-full">Aset Tetap</span></template>

            </div>
            <div class="px-4 lg:px-6 py-6">
                {{-- ==================== OVERVIEW ==================== --}}
                <div x-show="active==='overview'">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Gambaran Umum</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-3xl">Onezie Accounting — sistem akuntansi <strong>double-entry</strong> berbasis web untuk UKM Indonesia. Mencakup manajemen invoice, persediaan FIFO, multi-pajak, dan 14+ laporan keuangan.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                        <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-100 to-primary-50 dark:from-primary-900/30 dark:to-primary-800/20 flex items-center justify-center shrink-0 shadow-sm">
                                    <x-filament::icon name="heroicon-o-scale" class="w-5 h-5 text-primary-600 dark:text-primary-400"/>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white">Double-Entry</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">Setiap transaksi dicatat sebagai debit & kredit seimbang. Menjamin akurasi data keuangan.</p>
                                </div>
                            </div>
                        </div>
                        <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-100 to-emerald-50 dark:from-emerald-900/30 dark:to-emerald-800/20 flex items-center justify-center shrink-0 shadow-sm">
                                    <x-filament::icon name="heroicon-o-cube" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"/>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white">FIFO Inventory</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">HPP otomatis dengan metode First-In-First-Out. Tidak perlu hitung manual.</p>
                                </div>
                            </div>
                        </div>
                        <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900/30 dark:to-blue-800/20 flex items-center justify-center shrink-0 shadow-sm">
                                    <x-filament::icon name="heroicon-o-calculator" class="w-5 h-5 text-blue-600 dark:text-blue-400"/>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white">Multi-Pajak</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">PPN 11%, PPh 23, PPh 21, PPh 4(2) — terintegrasi penuh di invoice & pembelian.</p>
                                </div>
                            </div>
                        </div>
                        <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-100 to-purple-50 dark:from-purple-900/30 dark:to-purple-800/20 flex items-center justify-center shrink-0 shadow-sm">
                                    <x-filament::icon name="heroicon-o-clock" class="w-5 h-5 text-purple-600 dark:text-purple-400"/>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white">Periode & Closing</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">Closing checklist 10 langkah. Jurnal penutup dan carry forward saldo otomatis.</p>
                                </div>
                            </div>
                        </div>
                        <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-100 to-amber-50 dark:from-amber-900/30 dark:to-amber-800/20 flex items-center justify-center shrink-0 shadow-sm">
                                    <x-filament::icon name="heroicon-o-chart-bar" class="w-5 h-5 text-amber-600 dark:text-amber-400"/>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white">14+ Laporan</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">Neraca, Laba-Rugi, Arus Kas, Aging, Buku Besar, Rasio Keuangan, Dashboard Pajak.</p>
                                </div>
                            </div>
                        </div>
                        <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md hover:border-red-300 dark:hover:border-red-700 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-100 to-red-50 dark:from-red-900/30 dark:to-red-800/20 flex items-center justify-center shrink-0 shadow-sm">
                                    <x-filament::icon name="heroicon-o-shield-check" class="w-5 h-5 text-red-600 dark:text-red-400"/>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white">Audit Trail</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">Semua perubahan tercatat — siapa, kapan, dan apa yang diubah. Aman untuk audit.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Alur Data Sistem</h3>
                        <div class="flex flex-col md:flex-row items-center justify-center gap-3 text-sm">
                            <div class="flex flex-col items-center w-20">
                                <div class="w-full h-14 rounded-lg bg-gradient-to-b from-blue-400 to-blue-500 flex items-center justify-center font-semibold text-white shadow-md">Input</div>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 text-center">Invoice / Purchase</span>
                            </div>
                            <x-filament::icon name="heroicon-o-arrow-right" class="w-5 h-5 text-gray-300 dark:text-gray-600 shrink-0 hidden md:block"/>
                            <div class="flex flex-col items-center w-20">
                                <div class="w-full h-14 rounded-lg bg-gradient-to-b from-emerald-400 to-emerald-500 flex items-center justify-center font-semibold text-white shadow-md">Jurnal</div>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 text-center">Double-Entry</span>
                            </div>
                            <x-filament::icon name="heroicon-o-arrow-right" class="w-5 h-5 text-gray-300 dark:text-gray-600 shrink-0 hidden md:block"/>
                            <div class="flex flex-col items-center w-20">
                                <div class="w-full h-14 rounded-lg bg-gradient-to-b from-purple-400 to-purple-500 flex items-center justify-center font-semibold text-white shadow-md">Buku Besar</div>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 text-center">General Ledger</span>
                            </div>
                            <x-filament::icon name="heroicon-o-arrow-right" class="w-5 h-5 text-gray-300 dark:text-gray-600 shrink-0 hidden md:block"/>
                            <div class="flex flex-col items-center w-20">
                                <div class="w-full h-14 rounded-lg bg-gradient-to-b from-amber-400 to-amber-500 flex items-center justify-center font-semibold text-white shadow-md">Laporan</div>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 text-center">Neraca / LR</span>
                            </div>
                            <x-filament::icon name="heroicon-o-arrow-right" class="w-5 h-5 text-gray-300 dark:text-gray-600 shrink-0 hidden md:block"/>
                            <div class="flex flex-col items-center w-20">
                                <div class="w-full h-14 rounded-lg bg-gradient-to-b from-red-400 to-red-500 flex items-center justify-center font-semibold text-white shadow-md">Tutup</div>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 text-center">Closing</span>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- ==================== PERSIAPAN AWAL ==================== --}}
                <div x-show="active==='start'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Persiapan Awal</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Panduan langkah demi langkah untuk memulai menggunakan Onezie Accounting dari awal.</p>
                    <div class="space-y-5">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="flex">
                                <div class="w-12 bg-gradient-to-b from-primary-500 to-primary-600 flex items-start justify-center pt-4 shrink-0">
                                    <span class="text-white font-bold text-lg">1</span>
                                </div>
                                <div class="p-5 flex-1">
                                    <h3 class="font-bold text-gray-900 dark:text-white">Setup Master Data</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Konfigurasi data dasar sebelum memulai transaksi:</p>
                                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-users" class="w-4 h-4 text-primary-500 shrink-0"/>
                                            <span>Customer & Supplier</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-cube" class="w-4 h-4 text-primary-500 shrink-0"/>
                                            <span>Produk (Goods/Jasa)</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-wallet" class="w-4 h-4 text-primary-500 shrink-0"/>
                                            <span>Dompet / Wallet (Kas & Bank)</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-document-chart-bar" class="w-4 h-4 text-primary-500 shrink-0"/>
                                            <span>Tax Rules (PPN & PPh)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="flex">
                                <div class="w-12 bg-gradient-to-b from-emerald-500 to-emerald-600 flex items-start justify-center pt-4 shrink-0">
                                    <span class="text-white font-bold text-lg">2</span>
                                </div>
                                <div class="p-5 flex-1">
                                    <h3 class="font-bold text-gray-900 dark:text-white">Setup Akun & Saldo Awal</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pastikan COA lengkap dan saldo awal sudah diinput:</p>
                                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-check-circle" class="w-4 h-4 text-emerald-500 shrink-0"/>
                                            <span>Periksa daftar akun di Master Data &rarr; Akun</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-check-circle" class="w-4 h-4 text-emerald-500 shrink-0"/>
                                            <span>Input saldo awal di Master Data &rarr; Saldo Awal</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-check-circle" class="w-4 h-4 text-emerald-500 shrink-0"/>
                                            <span>Set akun sistem di Pengaturan &rarr; Settings</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="flex">
                                <div class="w-12 bg-gradient-to-b from-blue-500 to-blue-600 flex items-start justify-center pt-4 shrink-0">
                                    <span class="text-white font-bold text-lg">3</span>
                                </div>
                                <div class="p-5 flex-1">
                                    <h3 class="font-bold text-gray-900 dark:text-white">Setup Periode</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Periode akuntansi (biasanya bulanan) untuk mengelompokkan transaksi:</p>
                                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-check-circle" class="w-4 h-4 text-blue-500 shrink-0"/>
                                            <span>Buka Pengaturan &rarr; Periode &rarr; buat periode bulan berjalan</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-check-circle" class="w-4 h-4 text-blue-500 shrink-0"/>
                                            <span>Jika perlu periode retroaktif, buat dengan tanggal mundur</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-check-circle" class="w-4 h-4 text-blue-500 shrink-0"/>
                                            <span>Set periode aktif — semua transaksi baru tercatat di sini</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="flex">
                                <div class="w-12 bg-gradient-to-b from-purple-500 to-purple-600 flex items-start justify-center pt-4 shrink-0">
                                    <span class="text-white font-bold text-lg">4</span>
                                </div>
                                <div class="p-5 flex-1">
                                    <h3 class="font-bold text-gray-900 dark:text-white">Mulai Transaksi</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Setelah setup selesai, mulai mencatat transaksi:</p>
                                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-shopping-cart" class="w-4 h-4 text-purple-500 shrink-0"/>
                                            <span>Buat Invoice Penjualan</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-truck" class="w-4 h-4 text-purple-500 shrink-0"/>
                                            <span>Buat Purchase Pembelian</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-document-text" class="w-4 h-4 text-purple-500 shrink-0"/>
                                            <span>Buat Jurnal Manual</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                            <x-filament::icon name="heroicon-o-banknotes" class="w-4 h-4 text-purple-500 shrink-0"/>
                                            <span>Catat Penerimaan / Pembayaran</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- ==================== ALUR TRANSAKSI ==================== --}}
                <div x-show="active==='flow'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Alur Transaksi</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Memahami bagaimana transaksi mengalir dari input hingga laporan keuangan.</p>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden group hover:shadow-md transition-all duration-200">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-3 flex items-center gap-2">
                                <x-filament::icon name="heroicon-o-shopping-cart" class="w-5 h-5 text-white"/>
                                <h3 class="font-bold text-white text-sm">Penjualan (Kredit)</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">1</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Buat Invoice</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pilih customer, tambah item, atur pajak</div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">2</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Post Invoice</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Jurnal: Debit Piutang, Kredit Pendapatan + PPN</div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">3</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Terima Pembayaran</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Catat via Kas/Bank. Kurangi piutang</div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">4</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Invoice Lunas</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Status Paid/Lunas. Selesai</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden group hover:shadow-md transition-all duration-200">
                            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 p-3 flex items-center gap-2">
                                <x-filament::icon name="heroicon-o-truck" class="w-5 h-5 text-white"/>
                                <h3 class="font-bold text-white text-sm">Pembelian (Kredit)</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center justify-center shrink-0">1</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Buat Purchase</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pilih supplier, tambah item, atur pajak</div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center justify-center shrink-0">2</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Post Purchase</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Jurnal: Debit Persediaan/Beban, Kredit Hutang + PPN</div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center justify-center shrink-0">3</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Bayar Hutang</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Catat via Kas/Bank. Kurangi hutang</div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center justify-center shrink-0">4</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Purchase Lunas</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Status Paid. Selesai</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden group hover:shadow-md transition-all duration-200">
                            <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-3 flex items-center gap-2">
                                <x-filament::icon name="heroicon-o-banknotes" class="w-5 h-5 text-white"/>
                                <h3 class="font-bold text-white text-sm">Kas & Bank</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-bold flex items-center justify-center shrink-0">1</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Penjualan Tunai</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Invoice Cash Sale. Pilih wallet</div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-bold flex items-center justify-center shrink-0">2</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Post Otomatis</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Jurnal + langsung Lunas + catat payment</div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-bold flex items-center justify-center shrink-0">3</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Pengeluaran</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Gunakan Expense/Payment bayar beban</div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-bold flex items-center justify-center shrink-0">4</div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">Transfer Dana</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pindahkan uang antar wallet</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-3">Alur Pembayaran (Payment Flow)</h3>
                        <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm">
                            <div class="flex flex-col items-center w-full md:w-auto">
                                <div class="w-full md:w-32 h-12 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-medium">Invoice Posted</div>
                                <span class="text-[10px] text-gray-400 mt-1 text-center">Menunggu bayar</span>
                            </div>
                            <x-filament::icon name="heroicon-o-arrow-right" class="w-5 h-5 text-gray-300 dark:text-gray-600 shrink-0"/>
                            <div class="flex flex-col items-center w-full md:w-auto">
                                <div class="w-full md:w-32 h-12 rounded-lg border-2 border-yellow-300 dark:border-yellow-700 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 flex items-center justify-center font-medium">Payment Dicatat</div>
                                <span class="text-[10px] text-gray-400 mt-1 text-center">Bayar sebagian/lunas</span>
                            </div>
                            <x-filament::icon name="heroicon-o-arrow-right" class="w-5 h-5 text-gray-300 dark:text-gray-600 shrink-0"/>
                            <div class="flex flex-col items-center w-full md:w-auto">
                                <div class="w-full md:w-32 h-12 rounded-lg border-2 border-emerald-300 dark:border-emerald-700 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-medium">Lunas</div>
                                <span class="text-[10px] text-gray-400 mt-1 text-center">Selesai</span>
                            </div>
                            <x-filament::icon name="heroicon-o-arrow-right" class="w-5 h-5 text-gray-300 dark:text-gray-600 shrink-0"/>
                            <div class="flex flex-col items-center w-full md:w-auto">
                                <div class="w-full md:w-32 h-12 rounded-lg border-2 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex items-center justify-center font-medium">Closing Bulanan</div>
                                <span class="text-[10px] text-gray-400 mt-1 text-center">Ke Laba Ditahan</span>
                            </div>
                        </div>
                    </div>
                </div>                {{-- ==================== DAFTAR AKUN ==================== --}}
                <div x-show="active==='coa'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Daftar Akun (Chart of Accounts)</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Chart of Accounts (COA) adalah kerangka pengelompokan semua akun keuangan. Setiap akun memiliki normal balance (debit atau kredit) yang menentukan bagaimana saldo bertambah.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        <div class="rounded-xl border-2 p-4 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                            <div class="flex items-center gap-2 mb-2">
                                <x-filament::icon name="heroicon-o-building-library" class="w-5 h-5 text-gray-600 dark:text-gray-300"/>
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white">Aset</h4>
                            </div>
                            <div class="text-xs space-y-1">
                                <p><span class="text-gray-500 dark:text-gray-400">Normal Balance:</span> <strong>Debit</strong></p>
                                <p><span class="text-gray-500 dark:text-gray-400">Contoh:</span> Kas, Piutang, Persediaan, Aset Tetap</p>
                                <p><span class="text-gray-500 dark:text-gray-400">Aturan:</span> Bertambah di Debit</p>
                            </div>
                        </div>
                        <div class="rounded-xl border-2 p-4 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800">
                            <div class="flex items-center gap-2 mb-2">
                                <x-filament::icon name="heroicon-o-credit-card" class="w-5 h-5 text-gray-600 dark:text-gray-300"/>
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white">Kewajiban</h4>
                            </div>
                            <div class="text-xs space-y-1">
                                <p><span class="text-gray-500 dark:text-gray-400">Normal Balance:</span> <strong>Kredit</strong></p>
                                <p><span class="text-gray-500 dark:text-gray-400">Contoh:</span> Hutang Usaha, PPN/PPh Payable</p>
                                <p><span class="text-gray-500 dark:text-gray-400">Aturan:</span> Bertambah di Kredit</p>
                            </div>
                        </div>
                        <div class="rounded-xl border-2 p-4 bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800">
                            <div class="flex items-center gap-2 mb-2">
                                <x-filament::icon name="heroicon-o-chart-pie" class="w-5 h-5 text-gray-600 dark:text-gray-300"/>
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white">Ekuitas</h4>
                            </div>
                            <div class="text-xs space-y-1">
                                <p><span class="text-gray-500 dark:text-gray-400">Normal Balance:</span> <strong>Kredit</strong></p>
                                <p><span class="text-gray-500 dark:text-gray-400">Contoh:</span> Modal, Laba Ditahan</p>
                                <p><span class="text-gray-500 dark:text-gray-400">Aturan:</span> Bertambah di Kredit</p>
                            </div>
                        </div>
                        <div class="rounded-xl border-2 p-4 bg-sky-50 dark:bg-sky-900/20 border-sky-200 dark:border-sky-800">
                            <div class="flex items-center gap-2 mb-2">
                                <x-filament::icon name="heroicon-o-arrow-trending-up" class="w-5 h-5 text-gray-600 dark:text-gray-300"/>
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white">Pendapatan</h4>
                            </div>
                            <div class="text-xs space-y-1">
                                <p><span class="text-gray-500 dark:text-gray-400">Normal Balance:</span> <strong>Kredit</strong></p>
                                <p><span class="text-gray-500 dark:text-gray-400">Contoh:</span> Penjualan Barang, Pendapatan Jasa</p>
                                <p><span class="text-gray-500 dark:text-gray-400">Aturan:</span> Bertambah di Kredit</p>
                            </div>
                        </div>
                        <div class="rounded-xl border-2 p-4 bg-rose-50 dark:bg-rose-900/20 border-rose-200 dark:border-rose-800">
                            <div class="flex items-center gap-2 mb-2">
                                <x-filament::icon name="heroicon-o-cube" class="w-5 h-5 text-gray-600 dark:text-gray-300"/>
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white">HPP (COGS)</h4>
                            </div>
                            <div class="text-xs space-y-1">
                                <p><span class="text-gray-500 dark:text-gray-400">Normal Balance:</span> <strong>Debit</strong></p>
                                <p><span class="text-gray-500 dark:text-gray-400">Contoh:</span> HPP Barang Dagang</p>
                                <p><span class="text-gray-500 dark:text-gray-400">Aturan:</span> Bertambah di Debit</p>
                            </div>
                        </div>
                        <div class="rounded-xl border-2 p-4 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
                            <div class="flex items-center gap-2 mb-2">
                                <x-filament::icon name="heroicon-o-receipt-percent" class="w-5 h-5 text-gray-600 dark:text-gray-300"/>
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white">Beban</h4>
                            </div>
                            <div class="text-xs space-y-1">
                                <p><span class="text-gray-500 dark:text-gray-400">Normal Balance:</span> <strong>Debit</strong></p>
                                <p><span class="text-gray-500 dark:text-gray-400">Contoh:</span> Gaji, Sewa, Listrik, Penyusutan</p>
                                <p><span class="text-gray-500 dark:text-gray-400">Aturan:</span> Bertambah di Debit</p>
                            </div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Akun Sistem (Wajib Diisi)</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800">
                            <div class="flex items-start gap-2">
                                <x-filament::icon name="heroicon-o-exclamation-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"/>
                                <p class="text-xs text-amber-700 dark:text-amber-400">Akun-akun ini harus dikonfigurasi di <strong>Pengaturan &rarr; Settings</strong> agar sistem berfungsi dengan benar.</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700 border-b">
                                        <th class="text-left p-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Setting Key</th>
                                        <th class="text-left p-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Deskripsi</th>
                                        <th class="text-left p-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kode Default</th>
                                        <th class="text-left p-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Normal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">ar_account_id</td>
                                        <td class="p-3 font-medium">Piutang Usaha (AR)</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">1300-00-020</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Debit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">ap_account_id</td>
                                        <td class="p-3 font-medium">Hutang Usaha (AP)</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">2100-00-020</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Kredit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">revenue_account_id</td>
                                        <td class="p-3 font-medium">Pendapatan (default)</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">4100-00-010</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Kredit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">inventory_account_id</td>
                                        <td class="p-3 font-medium">Persediaan Barang</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">1400-00-010</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Debit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">cogs_account_id</td>
                                        <td class="p-3 font-medium">HPP (default)</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">5100-00-010</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Debit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">ppn_output_account_id</td>
                                        <td class="p-3 font-medium">PPN Keluaran</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">2100-00-070</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Kredit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">ppn_input_account_id</td>
                                        <td class="p-3 font-medium">PPN Masukan</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">1500-00-030</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Debit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">retained_earnings_id</td>
                                        <td class="p-3 font-medium">Laba Ditahan</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">3200-00-010</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Kredit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">income_summary_id</td>
                                        <td class="p-3 font-medium">Ikhtisar Laba/Rugi</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">3200-00-020</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Kredit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">pph_payable_account_id</td>
                                        <td class="p-3 font-medium">PPh Terutang</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">2100-00-071</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Kredit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">pph_prepaid_account_id</td>
                                        <td class="p-3 font-medium">PPh Dibayar Dimuka</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">1500-00-040</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Debit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">fixed_asset_account_id</td>
                                        <td class="p-3 font-medium">Aset Tetap</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">1700-00-030</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Debit</span></td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-3 font-mono text-xs text-primary-600 dark:text-primary-400">expense_account_id</td>
                                        <td class="p-3 font-medium">Beban (default)</td>
                                        <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">6110-00-010</td>
                                        <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Debit</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <x-filament::icon name="heroicon-o-light-bulb" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"/>
                        <div class="text-sm text-blue-700 dark:text-blue-400">
                            <strong>Tips:</strong> Akun (COA) bisa ditambahkan sendiri. Gunakan format kode konsisten: 1xxx Aset, 2xxx Kewajiban, 3xxx Ekuitas, 4xxx Pendapatan, 5xxx HPP, 6xxx Beban.
                        </div>
                    </div>
                </div>
                {{-- ==================== PENJUALAN ==================== --}}
                <div x-show="active==='penjualan'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Modul Penjualan</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Mencatat penjualan barang/jasa, mengelola piutang, dan melacak pembayaran dari customer.</p>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">Cara Membuat Invoice</h3>
                            <ol class="space-y-3">
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">1</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Buka menu <strong>Penjualan &rarr; Invoice</strong> lalu klik <strong>Buat Invoice</strong></div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">2</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Pilih <strong>Customer</strong>, isi tanggal & jatuh tempo invoice</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">3</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Tambah <strong>item</strong> (line) — pilih produk/jasa, isi quantity & harga satuan</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">4</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Atur <strong>pajak</strong> jika perlu — centang PPN 11% dan/atau PPh 23</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">5</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Jika tunai, centang <strong>Cash Sale</strong> dan pilih wallet tujuan</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">6</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Simpan sebagai <strong>Draft</strong> (bisa diedit), lalu <strong>Post</strong> untuk mencatat jurnal</div>
                                </li>
                            </ol>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">Invoice Tunai vs Kredit</h3>
                            <div class="space-y-3">
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border border-blue-200 dark:border-blue-800">
                                    <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 flex items-center gap-1.5"><x-filament::icon name="heroicon-o-bolt" class="w-4 h-4"/> Cash Sale (Tunai)</h4>
                                    <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">Posting &rarr; langsung catat kas + lunas. Tidak ada piutang. Cocok untuk retail.</p>
                                </div>
                                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 border border-purple-200 dark:border-purple-800">
                                    <h4 class="text-sm font-semibold text-purple-700 dark:text-purple-400 flex items-center gap-1.5"><x-filament::icon name="heroicon-o-credit-card" class="w-4 h-4"/> Invoice Kredit</h4>
                                    <p class="text-xs text-purple-600 dark:text-purple-300 mt-1">Posting &rarr; catat piutang. Pembayaran nanti. Cocok untuk B2B.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Status Invoice</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                        <table class="w-full text-sm">
                            <thead><tr class="bg-gray-50 dark:bg-gray-700 border-b"><th class="text-left p-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th><th class="text-left p-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Arti</th><th class="text-left p-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bisa Diubah?</th><th class="text-left p-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tindakan</th></tr></thead>
                            <tbody>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="p-3"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">Draft</span></td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Belum diposting, belum ada jurnal</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Ya, bisa diedit</td>
                                    <td class="p-3"><span class="text-xs text-gray-400">Post atau Edit</span></td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="p-3"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Posted</span></td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Sudah diposting, jurnal tercatat</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Hanya void</td>
                                    <td class="p-3"><span class="text-xs text-gray-400">Catat Payment</span></td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="p-3"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">Partially Paid</span></td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Dibayar sebagian</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Tidak</td>
                                    <td class="p-3"><span class="text-xs text-gray-400">Catat sisa Payment</span></td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="p-3"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Paid / Lunas</span></td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Pembayaran penuh diterima</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Tidak</td>
                                    <td class="p-3"><span class="text-xs text-gray-400">Selesai</span></td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="p-3"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Void / Cancelled</span></td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Invoice dibatalkan, reversal sudah dibuat</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-400">Tidak</td>
                                    <td class="p-3"><span class="text-xs text-gray-400">Audit trail</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-red-50 to-red-100/50 dark:from-red-900/20 dark:to-red-900/10 border border-red-200 dark:border-red-800 rounded-xl">
                        <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0"><x-filament::icon name="heroicon-o-exclamation-triangle" class="w-4 h-4 text-red-500"/></div>
                        <div class="text-sm text-red-700 dark:text-red-400">
                            <strong class="block mb-0.5">Peringatan Penting:</strong> Invoice yang sudah diposting <strong>TIDAK BISA DIEDIT</strong>. Jika ada kesalahan, batalkan (Void) dan buat ulang. Void akan membuat jurnal reversal otomatis.
                        </div>
                    </div>
                </div>
                {{-- ==================== PEMBELIAN ==================== --}}
                <div x-show="active==='pembelian'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Modul Pembelian</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Mencatat pembelian barang/jasa dari supplier, mengelola hutang, dan melacak pembayaran.</p>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">Cara Membuat Purchase Order</h3>
                            <ol class="space-y-3">
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">1</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Buka <strong>Pembelian &rarr; Purchase</strong> lalu klik <strong>Buat Purchase</strong></div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">2</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Pilih <strong>Supplier</strong>, isi tanggal & jatuh tempo</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">3</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Pilih <strong>akun pembelian</strong> — Persediaan untuk barang, Beban untuk jasa</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">4</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Atur <strong>pajak</strong> — PPN Masukan dicatat otomatis</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">5</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5"><strong>Post</strong> untuk mencatat jurnal pembelian</div>
                                </li>
                            </ol>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-gradient-to-r from-emerald-50 to-emerald-100/50 dark:from-emerald-900/20 dark:to-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0"><x-filament::icon name="heroicon-o-light-bulb" class="w-4 h-4 text-emerald-500"/></div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Memilih Akun Pembelian</h4>
                                        <p class="text-xs text-emerald-600 dark:text-emerald-300 mt-1 leading-relaxed"><strong>Barang dagang (stok)</strong> &rarr; pilih akun Persediaan. Stok bertambah, HPP dihitung FIFO saat dijual.<br><strong>Jasa/beban operasional</strong> &rarr; pilih akun Beban langsung.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-2">Status Purchase</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center gap-2"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">Draft</span><span class="text-gray-500 dark:text-gray-400">Belum diposting</span></div>
                                    <div class="flex items-center gap-2"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Posted</span><span class="text-gray-500 dark:text-gray-400">Sudah diposting, menunggu bayar</span></div>
                                    <div class="flex items-center gap-2"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Paid</span><span class="text-gray-500 dark:text-gray-400">Sudah dibayar lunas</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-yellow-50 to-yellow-100/50 dark:from-yellow-900/20 dark:to-yellow-900/10 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                        <div class="w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center shrink-0"><x-filament::icon name="heroicon-o-document-check" class="w-4 h-4 text-yellow-500"/></div>
                        <div class="text-sm text-yellow-700 dark:text-yellow-400"><strong class="block mb-0.5">Catatan:</strong> Purchase barang (Goods) menambah stok persediaan. Purchase jasa tidak mempengaruhi stok.</div>
                    </div>
                </div>
                {{-- ==================== KAS & BANK ==================== --}}
                <div x-show="active==='kas'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Modul Kas & Bank</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Mengelola semua penerimaan dan pengeluaran kas/bank, transfer antar wallet, dan rekonsiliasi.</p>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">Setup Rekening Kas/Bank</h3>
                            <ol class="space-y-3">
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">1</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Buka <strong>Master Data &rarr; Dompet/Wallet</strong></div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">2</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Klik <strong>Buat Dompet</strong> — isi nama, pilih tipe (Kas/Bank/E-Wallet)</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">3</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Pilih <strong>Akun COA</strong> yang sesuai (misal: 1100-00-010 untuk Kas)</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">4</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Isi <strong>saldo awal</strong> jika ada — sistem buat jurnal pembuka otomatis</div>
                                </li>
                            </ol>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">Jenis Transaksi Kas</h3>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-green-50 dark:bg-green-900/20">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Penjualan Tunai</span>
                                    <span class="text-sm font-bold text-green-600 dark:text-green-400">+ Masuk</span>
                                </div>
                                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-green-50 dark:bg-green-900/20">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pembayaran Piutang</span>
                                    <span class="text-sm font-bold text-green-600 dark:text-green-400">+ Masuk</span>
                                </div>
                                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-green-50 dark:bg-green-900/20">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Penerimaan Lain</span>
                                    <span class="text-sm font-bold text-green-600 dark:text-green-400">+ Masuk</span>
                                </div>
                                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900/20">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pembayaran Hutang</span>
                                    <span class="text-sm font-bold text-red-600 dark:text-red-400">- Keluar</span>
                                </div>
                                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900/20">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Biaya / Expense</span>
                                    <span class="text-sm font-bold text-red-600 dark:text-red-400">- Keluar</span>
                                </div>
                                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Transfer Wallet</span>
                                    <span class="text-sm font-bold text-gray-500 dark:text-gray-400">Netral</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-yellow-50 to-amber-100/50 dark:from-yellow-900/20 dark:to-amber-900/10 border border-yellow-200 dark:border-yellow-800 rounded-xl mb-6">
                        <div class="w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center shrink-0"><x-filament::icon name="heroicon-o-shield-exclamation" class="w-4 h-4 text-yellow-500"/></div>
                        <div class="text-sm text-yellow-700 dark:text-yellow-400"><strong class="block mb-0.5">Penting:</strong> Saldo kas/bank di sistem <strong>berdasarkan jurnal</strong>, bukan input manual. Lakukan <strong>Rekonsiliasi Bank</strong> secara berkala.</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-3">Transfer Antar Wallet</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Memindahkan uang antar rekening (misal: dari Kas ke Bank):</p>
                        <ol class="space-y-2">
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-bold flex items-center justify-center shrink-0 text-gray-500 dark:text-gray-400">1</div><div class="text-sm text-gray-700 dark:text-gray-300">Buka menu <strong>Kas & Bank &rarr; Fund Transfer</strong></div></li>
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-bold flex items-center justify-center shrink-0 text-gray-500 dark:text-gray-400">2</div><div class="text-sm text-gray-700 dark:text-gray-300">Pilih <strong>From Wallet</strong> (sumber) dan <strong>To Wallet</strong> (tujuan)</div></li>
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-bold flex items-center justify-center shrink-0 text-gray-500 dark:text-gray-400">3</div><div class="text-sm text-gray-700 dark:text-gray-300">Masukkan <strong>jumlah</strong> dan <strong>tanggal</strong> transfer</div></li>
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-bold flex items-center justify-center shrink-0 text-gray-500 dark:text-gray-400">4</div><div class="text-sm text-gray-700 dark:text-gray-300">Post — sistem buat jurnal: Debit Wallet Tujuan, Kredit Wallet Asal</div></li>
                        </ol>
                    </div>
                </div>
                {{-- ==================== JURNAL MANUAL ==================== --}}
                <div x-show="active==='jurnal'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Modul Jurnal Manual</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Membuat jurnal manual untuk transaksi yang tidak tercakup modul otomatis (penyesuaian, koreksi, akrual manual).</p>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">Membuat Jurnal Manual</h3>
                            <ol class="space-y-3">
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">1</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Buka <strong>Jurnal &rarr; Jurnal</strong> lalu klik <strong>Buat Jurnal</strong></div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">2</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Pilih <strong>periode</strong> dan <strong>tanggal</strong> jurnal</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">3</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Tambah minimal <strong>2 line</strong> — masing-masing dengan akun, deskripsi, nominal debit/kredit</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">4</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Pastikan <strong>total Debit = total Kredit</strong> — sistem tolak jika tidak balance</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">5</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Simpan — jurnal langsung <strong>POSTED</strong> (tidak ada draft untuk jurnal manual)</div>
                                </li>
                            </ol>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">Void & Reversal</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Jika jurnal salah, <strong>jangan dihapus</strong> — gunakan Void:</p>
                            <ol class="space-y-2">
                                <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold flex items-center justify-center shrink-0">1</div><div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Buka jurnal yang salah</div></li>
                                <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold flex items-center justify-center shrink-0">2</div><div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Klik tombol <strong>Void</strong> dan beri alasan pembatalan</div></li>
                                <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold flex items-center justify-center shrink-0">3</div><div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Sistem buat <strong>jurnal reversal</strong> otomatis (membalik debit/kredit)</div></li>
                                <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold flex items-center justify-center shrink-0">4</div><div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Jurnal asli ditandai Void — tetap ada untuk audit trail</div></li>
                            </ol>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-red-50 to-red-100/50 dark:from-red-900/20 dark:to-red-900/10 border border-red-200 dark:border-red-800 rounded-xl">
                        <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0"><x-filament::icon name="heroicon-o-no-symbol" class="w-4 h-4 text-red-500"/></div>
                        <div class="text-sm text-red-700 dark:text-red-400"><strong class="block mb-0.5">Aturan Penting:</strong> Jurnal yang sudah diposting <strong>TIDAK BISA DIHAPUS</strong> dan <strong>TIDAK BISA DIEDIT</strong>. Koreksi harus melalui Void (reversal) lalu buat jurnal baru.</div>
                    </div>
                </div>
                {{-- ==================== PERIODE & CLOSING ==================== --}}
                <div x-show="active==='periode'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Periode & Closing</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Periode akuntansi membatasi rentang waktu pencatatan. Closing adalah proses menutup periode dan memindahkan saldo ke periode berikutnya.</p>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-3">Manajemen Periode</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5"><x-filament::icon name="heroicon-o-plus-circle" class="w-4 h-4 text-emerald-500"/>Membuat Periode</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Buka <strong>Pengaturan &rarr; Periode</strong> &rarr; Buat Periode. Isi nama, tanggal mulai & selesai. Periode baru otomatis jadi aktif.</p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5"><x-filament::icon name="heroicon-o-check-badge" class="w-4 h-4 text-blue-500"/>Status Periode</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><strong>Aktif:</strong> bisa diisi jurnal. <strong>Tidak Aktif:</strong> tidak bisa diisi. <strong>Closed:</strong> sudah ditutup, tidak bisa diisi lagi.</p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5"><x-filament::icon name="heroicon-o-arrow-path" class="w-4 h-4 text-purple-500"/>Carry Forward</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Saat periode ditutup, saldo akun Neraca otomatis dibawa ke periode berikutnya. Akun Laba-Rugi ditutup ke Laba Ditahan.</p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5"><x-filament::icon name="heroicon-o-lock-closed" class="w-4 h-4 text-amber-500"/>Closed Period</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Periode Closed tidak bisa menerima transaksi baru. Jika harus dibuka, hubungi administrator.</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">Closing Checklist (10 Langkah)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Buka <strong>Pengaturan &rarr; Closing Checklist</strong> untuk proses tutup bulan terpandu:</p>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">1</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-banknotes" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Rekonsiliasi Bank</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Cocokkan saldo sistem dengan saldo bank riil</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">2</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-clock" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Aging AR / AP</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Periksa umur piutang & hutang. Tagih yang lewat jatuh tempo</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-teal-50 dark:bg-teal-900/20 border-teal-200 dark:border-teal-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">3</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-building-office" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Depresiasi Aset</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Jalankan penyusutan aset tetap bulan ini</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">4</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-cube" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Cek Stok</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Verifikasi nilai persediaan sistem vs fisik</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">5</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-pencil" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Jurnal Penyesuaian</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Buat jurnal manual untuk penyesuaian (accrual, prepaid)</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-rose-50 dark:bg-rose-900/20 border-rose-200 dark:border-rose-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">6</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-clock" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Akrual Bulanan</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Proses otomatis amortisasi prepaid & deferred revenue</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">7</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-document-chart-bar" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Cek Pajak</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Review kewajiban PPN dan PPh. Setor jika perlu</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-violet-50 dark:bg-violet-900/20 border-violet-200 dark:border-violet-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">8</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-scale" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Cek Neraca</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Verifikasi Total Aset = Liabilitas + Ekuitas</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">9</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-lock-closed" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Tutup Periode</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Buat jurnal penutup. Pindahkan Laba-Rugi ke Laba Ditahan</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border rounded-lg p-3 bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300 shadow-sm">10</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2"><x-filament::icon name="heroicon-o-sparkles" class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0"/><h4 class="font-semibold text-sm text-gray-900 dark:text-white">Buka Periode Baru</h4></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-6">Aktifkan periode berikutnya + carry forward saldo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-emerald-50 to-emerald-100/50 dark:from-emerald-900/20 dark:to-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0"><x-filament::icon name="heroicon-o-check-circle" class="w-4 h-4 text-emerald-500"/></div>
                        <div class="text-sm text-emerald-700 dark:text-emerald-400"><strong class="block mb-0.5">Otomatisasi:</strong> Langkah 9 (Tutup Periode) otomatis membuat jurnal penutup. Langkah 10 (Buka Periode Baru) carry forward saldo otomatis.</div>
                    </div>
                </div>
                {{-- ==================== AKRUAL & PREPAID ==================== --}}
                <div x-show="active==='akrual'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Akrual & Prepaid</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Mencatat transaksi akrual (beban/pendapatan masih harus dibayar/diterima) dan prepaid (pembayaran di muka diamortisasi).</p>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center"><x-filament::icon name="heroicon-o-arrow-trending-down" class="w-4 h-4 text-rose-500"/></div>
                                <h3 class="font-bold text-gray-900 dark:text-white">Accrued Expense</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Beban sudah terjadi tapi belum dibayar. Contoh: gaji akhir bulan dibayar awal bulan depan.</p>
                            <div class="bg-rose-50 dark:bg-rose-900/20 rounded-lg p-3 border border-rose-200 dark:border-rose-800">
                                <p class="text-xs font-mono"><strong>Saat Transaksi:</strong> Debit Beban, Kredit Hutang Akrual</p>
                                <p class="text-xs font-mono mt-1"><strong>Amortisasi/Reversal:</strong> Debit Hutang Akrual, Kredit Beban</p>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center"><x-filament::icon name="heroicon-o-arrow-trending-up" class="w-4 h-4 text-sky-500"/></div>
                                <h3 class="font-bold text-gray-900 dark:text-white">Accrued Revenue</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Pendapatan sudah terjadi tapi belum diterima. Contoh: jasa selesai belum diinvoice.</p>
                            <div class="bg-sky-50 dark:bg-sky-900/20 rounded-lg p-3 border border-sky-200 dark:border-sky-800">
                                <p class="text-xs font-mono"><strong>Saat Transaksi:</strong> Debit Piutang Akrual, Kredit Pendapatan</p>
                                <p class="text-xs font-mono mt-1"><strong>Amortisasi/Reversal:</strong> Debit Pendapatan, Kredit Piutang Akrual</p>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center"><x-filament::icon name="heroicon-o-credit-card" class="w-4 h-4 text-emerald-500"/></div>
                                <h3 class="font-bold text-gray-900 dark:text-white">Prepaid Expense</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Pembayaran di muka untuk beban beberapa bulan. Contoh: sewa 1 tahun.</p>
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3 border border-emerald-200 dark:border-emerald-800">
                                <p class="text-xs font-mono"><strong>Saat Transaksi:</strong> Debit Prepaid, Kredit Kas</p>
                                <p class="text-xs font-mono mt-1"><strong>Amortisasi/Reversal:</strong> Debit Beban, Kredit Prepaid</p>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Buka <strong>Akuntansi &rarr; Prepaid Expense</strong> untuk mengelola.</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center"><x-filament::icon name="heroicon-o-banknotes" class="w-4 h-4 text-amber-500"/></div>
                                <h3 class="font-bold text-gray-900 dark:text-white">Deferred Revenue</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Penerimaan di muka untuk jasa periode depan. Contoh: langganan tahunan.</p>
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 border border-amber-200 dark:border-amber-800">
                                <p class="text-xs font-mono"><strong>Saat Transaksi:</strong> Debit Kas, Kredit Deferred Revenue</p>
                                <p class="text-xs font-mono mt-1"><strong>Amortisasi/Reversal:</strong> Debit Deferred Rev, Kredit Pendapatan</p>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Buka <strong>Akuntansi &rarr; Deferred Revenue</strong> untuk mengelola.</p>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-3">Cara Membuat Akrual Sederhana</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Akrual sederhana (auto-reversal bulan depan):</p>
                        <ol class="space-y-2">
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-rose-100 dark:bg-rose-900/30 text-xs font-bold flex items-center justify-center shrink-0 text-rose-500">1</div><div class="text-sm text-gray-700 dark:text-gray-300">Buka <strong>Jurnal &rarr; Jurnal</strong> &rarr; <strong>Buat Jurnal</strong></div></li>
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-rose-100 dark:bg-rose-900/30 text-xs font-bold flex items-center justify-center shrink-0 text-rose-500">2</div><div class="text-sm text-gray-700 dark:text-gray-300">Buat jurnal akrual (contoh: Debit Beban Gaji, Kredit Hutang Akrual)</div></li>
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-rose-100 dark:bg-rose-900/30 text-xs font-bold flex items-center justify-center shrink-0 text-rose-500">3</div><div class="text-sm text-gray-700 dark:text-gray-300">Centang opsi <strong>Auto-Reversal</strong>, pilih tanggal reversal (tgl 1 bulan depan)</div></li>
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-rose-100 dark:bg-rose-900/30 text-xs font-bold flex items-center justify-center shrink-0 text-rose-500">4</div><div class="text-sm text-gray-700 dark:text-gray-300">Post — sistem buat jurnal akrual + reversal otomatis di tanggal yang ditentukan</div></li>
                        </ol>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-rose-50 to-rose-100/50 dark:from-rose-900/20 dark:to-rose-900/10 border border-rose-200 dark:border-rose-800 rounded-xl">
                        <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center shrink-0"><x-filament::icon name="heroicon-o-information-circle" class="w-4 h-4 text-rose-500"/></div>
                        <div class="text-sm text-rose-700 dark:text-rose-400"><strong class="block mb-0.5">Catatan:</strong> Prepaid & Deferred Revenue dicatat sebagai record khusus (bukan jurnal biasa) agar amortisasi terlacak per bulan. Akrual sederhana cukup pakai jurnal auto-reversal.</div>
                    </div>
                </div>
                {{-- ==================== REKONSILIASI BANK ==================== --}}
                <div x-show="active==='rekon'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Rekonsiliasi Bank</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Proses mencocokkan saldo kas/bank sistem dengan rekening koran bank. Wajib dilakukan setiap bulan sebelum closing.</p>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">Cara Rekonsiliasi</h3>
                            <ol class="space-y-3">
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">1</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Buka <strong>Akuntansi &rarr; Rekonsiliasi Bank</strong></div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">2</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Pilih <strong>periode</strong> dan <strong>wallet</strong> yang akan direkonsiliasi</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">3</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Siapkan <strong>rekening koran</strong> (bank statement)</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">4</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Cocokkan transaksi — centang yang sudah sesuai dengan bank</div>
                                </li>
                                <li class="flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 text-white text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">5</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 pt-0.5">Klik <strong>Simpan Rekonsiliasi</strong> — selisih terlihat di summary card</div>
                                </li>
                            </ol>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                                <h3 class="font-bold text-gray-900 dark:text-white mb-3">Komponen Rekonsiliasi</h3>
                                <div class="space-y-3 text-sm">
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50"><span class="text-gray-600 dark:text-gray-400">Saldo Bank (sistem)</span><span class="font-mono font-semibold">Rp xxx</span></div>
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-green-50 dark:bg-green-900/20"><span class="text-green-700 dark:text-green-400">+ Transaksi belum di bank</span><span class="font-mono font-semibold text-green-600 dark:text-green-400">Rp xxx</span></div>
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-red-50 dark:bg-red-900/20"><span class="text-red-700 dark:text-red-400">- Transaksi belum di sistem</span><span class="font-mono font-semibold text-red-600 dark:text-red-400">Rp xxx</span></div>
                                    <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex items-center justify-between"><span class="font-bold text-gray-900 dark:text-white">Saldo Bank Statement</span><span class="font-mono font-bold">Rp xxx</span></div>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-cyan-50 to-cyan-100/50 dark:from-cyan-900/20 dark:to-cyan-900/10 border border-cyan-200 dark:border-cyan-800 rounded-xl p-4">
                                <div class="flex items-start gap-2"><x-filament::icon name="heroicon-o-light-bulb" class="w-4 h-4 text-cyan-500 shrink-0 mt-0.5"/><p class="text-xs text-cyan-700 dark:text-cyan-400"><strong>Tips:</strong> Jika ada selisih, periksa: transaksi belum dicatat, biaya bank, atau transfer antar wallet. Catat jurnal penyesuaian untuk selisih.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-yellow-50 to-amber-100/50 dark:from-yellow-900/20 dark:to-amber-900/10 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                        <div class="w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center shrink-0"><x-filament::icon name="heroicon-o-exclamation-triangle" class="w-4 h-4 text-yellow-500"/></div>
                        <div class="text-sm text-yellow-700 dark:text-yellow-400"><strong class="block mb-0.5">Penting:</strong> Rekonsiliasi bank harus dilakukan <strong>setiap bulan</strong> sebelum closing. Gunakan langkah 1 di Closing Checklist.</div>
                    </div>
                </div>
                {{-- ==================== PAJAK ==================== --}}
                <div x-show="active==='pajak'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Modul Pajak</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Sistem mendukung PPN 11%, PPh 23 (2%), PPh 21, dan PPh 4(2) — terintegrasi dalam invoice dan pembelian.</p>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center"><x-filament::icon name="heroicon-o-receipt-percent" class="w-4 h-4 text-blue-500"/></div>
                            <h3 class="font-bold text-gray-900 dark:text-white">PPN 11%</h3>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">PPN dihitung otomatis berdasarkan Tax Rule:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400">PPN Exclusive</h4>
                                <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">Harga belum termasuk PPN. PPN = 11% x DPP. Total = DPP + PPN.</p>
                            </div>
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-3">
                                <h4 class="text-sm font-semibold text-indigo-700 dark:text-indigo-400">PPN Inclusive</h4>
                                <p class="text-xs text-indigo-600 dark:text-indigo-300 mt-1">Harga sudah termasuk PPN. DPP = 100/111 x Harga. PPN = 11/111 x Harga.</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400">Laporan PPN: <strong>Laporan &rarr; Laporan PPN</strong>. Menampilkan Masukan, Keluaran, Kurang/Lebih Bayar.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center"><x-filament::icon name="heroicon-o-document-text" class="w-4 h-4 text-orange-500"/></div>
                            <h3 class="font-bold text-gray-900 dark:text-white">PPh 23 (2%)</h3>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">PPh 23 dikenakan atas penghasilan jasa, sewa, royalti. Tarif 2% dari DPP:</p>
                        <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="w-full text-sm">
                                <thead><tr class="bg-gray-50 dark:bg-gray-700"><th class="text-left p-2 text-xs text-gray-500 dark:text-gray-400">Situasi</th><th class="text-left p-2 text-xs text-gray-500 dark:text-gray-400">Invoice/Purchase</th><th class="text-left p-2 text-xs text-gray-500 dark:text-gray-400">Pembayaran</th></tr></thead>
                                <tbody>
                                    <tr class="border-t dark:border-gray-700"><td class="p-2 text-xs">Invoice ke customer NPWP</td><td class="p-2 text-xs">Full amount</td><td class="p-2 text-xs">Customer potong 2%</td></tr>
                                    <tr class="border-t dark:border-gray-700 even:bg-gray-50 dark:even:bg-gray-800/50"><td class="p-2 text-xs">Purchase dari supplier jasa</td><td class="p-2 text-xs">Full amount</td><td class="p-2 text-xs">Kita potong 2%, setor negara</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Laporan PPh 23: <strong>Laporan &rarr; Laporan PPh 23</strong>.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center"><x-filament::icon name="heroicon-o-building-library" class="w-4 h-4 text-red-500"/></div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Cara Setor Pajak</h3>
                        </div>
                        <ol class="space-y-2">
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-xs font-bold flex items-center justify-center shrink-0 text-red-500">1</div><div class="text-sm text-gray-700 dark:text-gray-300">Buka <strong>Akuntansi &rarr; Pembayaran Pajak</strong></div></li>
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-xs font-bold flex items-center justify-center shrink-0 text-red-500">2</div><div class="text-sm text-gray-700 dark:text-gray-300">Pilih <strong>jenis pajak</strong> (PPN, PPh 23, PPh 21, PPh 4(2))</div></li>
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-xs font-bold flex items-center justify-center shrink-0 text-red-500">3</div><div class="text-sm text-gray-700 dark:text-gray-300">Isi <strong>nomor SSP/NTPN</strong> sebagai referensi setoran</div></li>
                            <li class="flex gap-3"><div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-xs font-bold flex items-center justify-center shrink-0 text-red-500">4</div><div class="text-sm text-gray-700 dark:text-gray-300"><strong>Post</strong> — sistem catat jurnal: Debit Hutang Pajak, Kredit Kas/Bank</div></li>
                        </ol>
                    </div>
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-100/50 dark:from-blue-900/20 dark:to-indigo-900/10 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                        <div class="flex items-start gap-2">
                            <x-filament::icon name="heroicon-o-squares-2x2" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"/>
                            <div class="text-sm text-blue-700 dark:text-blue-400"><strong class="block mb-1">Dashboard Pajak Terintegrasi</strong> Buka <strong>Laporan &rarr; Dashboard Pajak</strong> untuk ringkasan SEMUA pajak: PPN, PPh 23, PPh 21, PPh 4(2) + riwayat pembayaran.</div>
                        </div>
                    </div>
                </div>
                {{-- ==================== ASET TETAP ==================== --}}
                <div x-show="active==='aset'">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Modul Aset Tetap</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Mengelola aset tetap (tanah, bangunan, kendaraan, mesin, peralatan) dari perolehan hingga penghapusan.</p>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-3">Siklus Aset Tetap</h3>
                            <div class="space-y-3">
                            <div class="border rounded-lg p-3 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300">1</div>
                                    <div>
                                        <h4 class="font-semibold text-sm text-gray-900 dark:text-white">Perolehan</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Catat pembelian aset. Jurnal: Debit Aset Tetap, Kredit Kas/Hutang</p>
                                    </div>
                                </div>
                            </div>
                            <div class="border rounded-lg p-3 bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300">2</div>
                                    <div>
                                        <h4 class="font-semibold text-sm text-gray-900 dark:text-white">Depresiasi</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Jalankan penyusutan bulanan. Hitung otomatis berdasarkan metode</p>
                                    </div>
                                </div>
                            </div>
                            <div class="border rounded-lg p-3 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300">3</div>
                                    <div>
                                        <h4 class="font-semibold text-sm text-gray-900 dark:text-white">Akumulasi</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Nilai Buku = Harga Perolehan - Akum. Penyusutan. Bertambah tiap bulan</p>
                                    </div>
                                </div>
                            </div>
                            <div class="border rounded-lg p-3 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center border-2 border-current shrink-0 text-gray-600 dark:text-gray-300">4</div>
                                    <div>
                                        <h4 class="font-semibold text-sm text-gray-900 dark:text-white">Penghapusan</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Jika aset dijual/dihapuskan. Sistem hitung laba/rugi disposal</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                                <h3 class="font-bold text-gray-900 dark:text-white mb-3">Metode Depresiasi</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50"><span class="text-gray-700 dark:text-gray-300">Garis Lurus (Straight Line)</span><span class="text-xs text-gray-400">Biaya merata setiap tahun</span></div>
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50"><span class="text-gray-700 dark:text-gray-300">Saldo Menurun Ganda (DDB)</span><span class="text-xs text-gray-400">Lebih besar di awal</span></div>
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50"><span class="text-gray-700 dark:text-gray-300">Jumlah Angka Tahun (SYD)</span><span class="text-xs text-gray-400">Menurun berdasarkan tahun</span></div>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                                <div class="flex items-start gap-2"><x-filament::icon name="heroicon-o-information-circle" class="w-4 h-4 text-blue-500 shrink-0 mt-0.5"/>
                                    <div class="text-sm text-blue-700 dark:text-blue-400"><strong>Akumulasi Penyusutan</strong> adalah contra-asset — saldo normal kredit, mengurangi nilai aset di Neraca.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-3">Rumus Depresiasi</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"><h4 class="font-semibold text-gray-900 dark:text-white mb-1">Garis Lurus</h4><p class="text-xs text-gray-500 dark:text-gray-400 font-mono">(HP - Nilai Residu) / Umur</p></div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"><h4 class="font-semibold text-gray-900 dark:text-white mb-1">DDB</h4><p class="text-xs text-gray-500 dark:text-gray-400 font-mono">2 x (1/Umur) x Nilai Buku</p></div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"><h4 class="font-semibold text-gray-900 dark:text-white mb-1">SYD</h4><p class="text-xs text-gray-500 dark:text-gray-400 font-mono">(Sisa Umur / Jumlah Angka Tahun) x (HP - Residu)</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

