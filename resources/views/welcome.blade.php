<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Accounting') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Figtree', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
        .bg-grid {
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .hero-glow {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.15), transparent 70%);
            top: -200px;
            right: -200px;
            pointer-events: none;
        }
        .hero-glow-2 {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.08), transparent 70%);
            bottom: -150px;
            left: -150px;
            pointer-events: none;
        }
        .card-gradient {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            border: 1px solid rgba(148, 163, 184, 0.1);
            backdrop-filter: blur(12px);
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 36px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #0f172a;
            font-weight: 600;
            font-size: 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(245, 158, 11, 0.4);
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 36px;
            background: rgba(148, 163, 184, 0.1);
            color: #e2e8f0;
            font-weight: 500;
            font-size: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: rgba(148, 163, 184, 0.2);
            transform: translateY(-2px);
        }
        .feature-card {
            padding: 28px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.7));
            border: 1px solid rgba(148, 163, 184, 0.08);
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            border-color: rgba(245, 158, 11, 0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }
        .stat-value { font-size: 32px; font-weight: 700; color: #f59e0b; }
        .stat-label { font-size: 14px; color: #94a3b8; margin-top: 4px; }
        .footer-link { color: #64748b; text-decoration: none; transition: color 0.2s; }
        .footer-link:hover { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="bg-grid" style="position:relative; min-height:100vh; overflow:hidden;">
        <div class="hero-glow"></div>
        <div class="hero-glow-2"></div>

        {{-- Navbar --}}
        <nav style="display:flex; align-items:center; justify-content:space-between; padding: 20px 40px; max-width:1200px; margin:0 auto; position:relative; z-index:10;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:18px; color:#0f172a;">A</div>
                <span style="font-weight:700; font-size:18px; color:#f1f5f9;">{{ config('app.name', 'Accounting') }}</span>
            </div>
            <div style="display:flex; align-items:center; gap:16px;">
                <a href="{{ url('/admin/login') }}" class="btn-primary" style="padding:10px 24px; font-size:14px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Masuk
                </a>
            </div>
        </nav>

        {{-- Hero Section --}}
        <section style="max-width:1200px; margin:0 auto; padding: 80px 40px 60px; position:relative; z-index:10; text-align:center;">
            <div style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.2); border-radius:20px; font-size:13px; color:#f59e0b; margin-bottom:30px;">
                <span style="width:8px; height:8px; background:#22c55e; border-radius:50%; display:inline-block;"></span>
                Sistem Akuntansi Double-Entry
            </div>

            <h1 style="font-size:clamp(36px, 6vw, 64px); font-weight:700; line-height:1.1; color:#f1f5f9; margin-bottom:20px;">
                Kelola Keuangan<br>
                <span style="background:linear-gradient(135deg, #f59e0b, #fbbf24); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">Bisnis Anda</span>
                dengan Mudah
            </h1>

            <p style="font-size:18px; color:#94a3b8; max-width:600px; margin:0 auto 40px; line-height:1.7;">
                Platform akuntansi terintegrasi untuk mencatat transaksi, membuat laporan keuangan,
                mengelola piutang & hutang, serta memantau arus kas bisnis Anda secara real-time.
            </p>

            <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
                <a href="{{ url('/admin/login') }}" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Mulai Sekarang
                </a>
                <a href="#fitur" class="btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 16 16 12 12 8"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Lihat Fitur
                </a>
            </div>
        </section>

        {{-- Stats --}}
        <section style="max-width:1000px; margin:0 auto; padding: 0 40px 60px; position:relative; z-index:10;">
            <div class="card-gradient" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:0; border-radius:20px; overflow:hidden;">
                <div style="padding:32px; text-align:center; border-right:1px solid rgba(148,163,184,0.08);">
                    <div class="stat-value">Double-Entry</div>
                    <div class="stat-label">Sistem Pembukuan</div>
                </div>
                <div style="padding:32px; text-align:center; border-right:1px solid rgba(148,163,184,0.08);">
                    <div class="stat-value">Laporan</div>
                    <div class="stat-label">Neraca & Laba Rugi</div>
                </div>
                <div style="padding:32px; text-align:center; border-right:1px solid rgba(148,163,184,0.08);">
                    <div class="stat-value">PPN & PPh</div>
                    <div class="stat-label">Perpajakan</div>
                </div>
                <div style="padding:32px; text-align:center;">
                    <div class="stat-value">Real-time</div>
                    <div class="stat-label">Arus Kas</div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="fitur" style="max-width:1200px; margin:0 auto; padding: 20px 40px 80px; position:relative; z-index:10;">
            <h2 style="font-size:32px; font-weight:700; color:#f1f5f9; text-align:center; margin-bottom:12px;">Fitur Lengkap</h2>
            <p style="color:#64748b; text-align:center; margin-bottom:50px; font-size:16px;">Semua yang Anda butuhkan untuk mengelola keuangan perusahaan</p>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
                <div class="feature-card">
                    <div style="width:48px; height:48px; background:rgba(245,158,11,0.15); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <h3 style="font-size:18px; font-weight:600; color:#f1f5f9; margin-bottom:8px;">Jurnal & Posting</h3>
                    <p style="font-size:14px; color:#94a3b8; line-height:1.6;">Catat setiap transaksi dengan jurnal double-entry yang otomatis balance.</p>
                </div>

                <div class="feature-card">
                    <div style="width:48px; height:48px; background:rgba(245,158,11,0.15); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <h3 style="font-size:18px; font-weight:600; color:#f1f5f9; margin-bottom:8px;">Invoice & Pembelian</h3>
                    <p style="font-size:14px; color:#94a3b8; line-height:1.6;">Buat invoice penjualan dan purchase order dengan perhitungan pajak otomatis.</p>
                </div>

                <div class="feature-card">
                    <div style="width:48px; height:48px; background:rgba(245,158,11,0.15); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
                    </div>
                    <h3 style="font-size:18px; font-weight:600; color:#f1f5f9; margin-bottom:8px;">Laporan Keuangan</h3>
                    <p style="font-size:14px; color:#94a3b8; line-height:1.6;">Neraca, Laba Rugi, Arus Kas, Buku Kas — semua siap cetak & ekspor.</p>
                </div>

                <div class="feature-card">
                    <div style="width:48px; height:48px; background:rgba(245,158,11,0.15); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                    </div>
                    <h3 style="font-size:18px; font-weight:600; color:#f1f5f9; margin-bottom:8px;">Manajemen Pajak</h3>
                    <p style="font-size:14px; color:#94a3b8; line-height:1.6;">Laporan PPN, PPh 23, dan setoran pajak terintegrasi penuh.</p>
                </div>

                <div class="feature-card">
                    <div style="width:48px; height:48px; background:rgba(245,158,11,0.15); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/><line x1="17" y1="17" x2="22" y2="17"/></svg>
                    </div>
                    <h3 style="font-size:18px; font-weight:600; color:#f1f5f9; margin-bottom:8px;">Piutang & Hutang</h3>
                    <p style="font-size:14px; color:#94a3b8; line-height:1.6;">Pantau aging piutang dan hutang dengan jatuh tempo otomatis.</p>
                </div>

                <div class="feature-card">
                    <div style="width:48px; height:48px; background:rgba(245,158,11,0.15); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <h3 style="font-size:18px; font-weight:600; color:#f1f5f9; margin-bottom:8px;">Aset Tetap</h3>
                    <p style="font-size:14px; color:#94a3b8; line-height:1.6;">Kelola aset tetap dengan perhitungan penyusutan otomatis.</p>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section style="max-width:900px; margin:0 auto; padding: 0 40px 80px; position:relative; z-index:10;">
            <div class="card-gradient" style="padding:60px 40px; border-radius:24px; text-align:center;">
                <h2 style="font-size:30px; font-weight:700; color:#f1f5f9; margin-bottom:16px;">Siap Mengelola Keuangan?</h2>
                <p style="color:#94a3b8; max-width:500px; margin:0 auto 30px; font-size:16px; line-height:1.7;">
                    Mulai gunakan sistem akuntansi digital untuk bisnis Anda. Catatan keuangan yang rapi, laporan yang akurat, dan keputusan yang lebih baik.
                </p>
                <a href="{{ url('/admin/login') }}" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Masuk ke Aplikasi
                </a>
            </div>
        </section>

        {{-- Footer --}}
        <footer style="max-width:1200px; margin:0 auto; padding: 30px 40px; position:relative; z-index:10; border-top:1px solid rgba(148,163,184,0.08); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:28px; height:28px; background:linear-gradient(135deg, #f59e0b, #d97706); border-radius:7px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; color:#0f172a;">A</div>
                <span style="font-size:14px; color:#64748b;">{{ config('app.name', 'Accounting') }} &copy; {{ date('Y') }}</span>
            </div>
            <div style="display:flex; gap:24px; font-size:13px;">
                <span style="color:#475569;">Dibangun dengan Laravel & Filament</span>
            </div>
        </footer>
    </div>
</body>
</html>