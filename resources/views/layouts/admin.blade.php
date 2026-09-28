<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị') · Soopi</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,600&display=swap&subset=vietnamese" rel="stylesheet">
    <style>
        :root {
            --pink:       #e8728a;
            --pink-dark:  #c94d68;
            --pink-light: #f4a7b6;
            --pink-pale:  #fce8ed;
            --pink-soft:  #fdf0f3;
            --blush:      #f9d4dc;
            --sidebar-w:  240px;
            --text-dark:  #2d1a20;
            --text-mid:   #6b3f4e;
            --text-muted: #b08899;
            --border:     rgba(232,114,138,.18);
            --white:      #ffffff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Be Vietnam Pro', -apple-system, sans-serif;
            background: #fef5f7;
            color: var(--text-dark);
        }

        /* ── SIDEBAR ── */
        .admin-sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: #fff;
            position: fixed; top: 0; left: 0;
            display: flex; flex-direction: column;
            border-right: 1px solid var(--border);
            box-shadow: 2px 0 20px rgba(200,80,100,.07);
            z-index: 100;
        }

        .sidebar-brand {
            display: flex; align-items: center; gap: 12px;
            padding: 22px 20px;
            border-bottom: 1px solid var(--border);
            text-decoration: none !important;
        }
        .sidebar-brand .brand-icon {
            width: 40px; height: 40px; border-radius: 10px;
            background: linear-gradient(135deg, var(--pink-dark), var(--pink));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 18px; flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(232,114,138,.35);
        }
        .sidebar-brand .brand-text strong {
            display: block;
            font-size: 15px; font-weight: 700;
            color: var(--text-dark); letter-spacing: .3px;
        }
        .sidebar-brand .brand-text span {
            font-size: 10px; color: var(--pink);
            font-weight: 600; letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .sidebar-section-label {
            padding: 16px 20px 6px;
            font-size: 9.5px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--text-muted);
        }

        .sidebar-menu {
            padding: 8px 12px;
            flex: 1; list-style: none;
        }
        .sidebar-menu li { margin-bottom: 2px; }
        .sidebar-menu a {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px; border-radius: 10px;
            font-size: 13.5px; font-weight: 500;
            color: var(--text-mid);
            text-decoration: none !important;
            transition: all .2s;
        }
        .sidebar-menu a i {
            width: 18px; text-align: center;
            font-size: 14px; color: var(--text-muted);
            transition: color .2s;
        }
        .sidebar-menu a:hover {
            background: var(--pink-pale);
            color: var(--pink-dark);
        }
        .sidebar-menu a:hover i { color: var(--pink); }
        .sidebar-menu a.active {
            background: linear-gradient(135deg, rgba(201,77,104,.12), rgba(232,114,138,.15));
            color: var(--pink-dark); font-weight: 600;
            border-left: 3px solid var(--pink);
            padding-left: 11px;
        }
        .sidebar-menu a.active i { color: var(--pink); }

        .sidebar-divider {
            margin: 10px 20px;
            border-top: 1px solid var(--border);
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            background: var(--pink-soft);
        }
        .admin-user-info {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 12px;
        }
        .admin-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--pink-dark), var(--pink));
            color: #fff; display: flex; align-items: center;
            justify-content: center; font-weight: 700; font-size: 14px;
            flex-shrink: 0;
        }
        .admin-meta .name {
            font-size: 13px; font-weight: 600; color: var(--text-dark);
        }
        .admin-meta .role {
            font-size: 10px; color: var(--pink); font-weight: 600;
            letter-spacing: .5px; text-transform: uppercase;
        }
        .btn-sidebar-logout {
            width: 100%;
            background: rgba(192,57,43,.07);
            border: 1px solid rgba(192,57,43,.2);
            color: #c0392b;
            padding: 8px 14px; border-radius: 8px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            transition: all .2s;
        }
        .btn-sidebar-logout:hover {
            background: #ef4444; color: #fff; border-color: #ef4444;
        }

        /* ── MAIN ── */
        .admin-main-wrapper {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        .admin-topbar {
            background: #fff;
            padding: 0 32px;
            height: 62px;
            border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 12px rgba(200,80,100,.05);
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .topbar-left h1 {
            font-size: 18px; font-weight: 700; color: var(--text-dark); margin: 0;
        }
        .topbar-breadcrumb {
            font-size: 11px; color: var(--text-muted);
            display: flex; align-items: center; gap: 4px; margin-top: 2px;
        }
        .topbar-right { display: flex; align-items: center; gap: 14px; }
        .topbar-date {
            font-size: 12px; color: var(--text-muted);
            display: flex; align-items: center; gap: 5px;
        }
        .topbar-badge {
            padding: 4px 10px; border-radius: 20px;
            background: linear-gradient(135deg, var(--pink-dark), var(--pink));
            color: #fff; font-size: 11px; font-weight: 600;
        }

        .admin-content-area {
            padding: 28px 32px; flex: 1;
        }

        /* ── CARDS ── */
        .admin-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 12px rgba(200,80,100,.05);
            padding: 24px;
            margin-bottom: 22px;
        }

        /* ── TABLES ── */
        .table-admin thead th {
            background: var(--pink-soft);
            color: var(--pink-dark);
            font-weight: 700; font-size: 11px;
            text-transform: uppercase; letter-spacing: 1px;
            border-top: none;
            border-bottom: 2px solid var(--border);
            padding: 12px 16px;
        }
        .table-admin tbody td {
            padding: 13px 16px;
            vertical-align: middle;
            border-color: var(--border);
            font-size: 13.5px;
        }
        .table-admin tbody tr:hover td {
            background: var(--pink-soft);
        }

        /* ── BADGES ── */
        .badge-active {
            background: #dcfce7; color: #15803d;
            padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-inactive {
            background: var(--pink-pale); color: var(--pink-dark);
            padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }

        /* ── ALERTS ── */
        .alert-success {
            background: #f0fdf4; border-color: #bbf7d0; color: #15803d;
            border-radius: 10px; font-size: 13px;
        }
        .alert-danger {
            background: #fff5f5; border-color: #fecaca; color: #c0392b;
            border-radius: 10px; font-size: 13px;
        }

        /* ── BUTTONS ── */
        .btn-pink,
        .btn-primary {
            background: linear-gradient(135deg, var(--pink-dark), var(--pink)) !important;
            color: #fff !important; border: 0 !important; padding: 9px 20px; border-radius: 8px;
            font-size: 13px; font-weight: 600;
            box-shadow: 0 4px 14px rgba(232,114,138,.3);
            transition: all .2s;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-pink:hover,
        .btn-primary:hover {
            opacity: .9; transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(201,77,104,.35);
            color: #fff !important;
        }

        .btn-outline-pink,
        .btn-outline-primary {
            background: transparent !important; color: var(--pink-dark) !important;
            border: 1.5px solid var(--pink-light) !important; padding: 8px 18px;
            border-radius: 8px; font-size: 13px; font-weight: 600;
            transition: all .2s;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-outline-pink:hover,
        .btn-outline-primary:hover {
            background: var(--pink-pale) !important; color: var(--pink-dark) !important;
            border-color: var(--pink) !important; text-decoration: none;
        }

        .btn-outline-info {
            background: #fff !important; color: var(--pink-dark) !important;
            border: 1.5px solid rgba(232,114,138,.35) !important; border-radius: 8px;
            font-size: 12.5px; font-weight: 600; transition: all .2s;
        }
        .btn-outline-info:hover {
            background: var(--pink-pale) !important; border-color: var(--pink) !important;
            color: var(--pink-dark) !important;
        }

        .btn-outline-warning {
            background: #fff !important; color: #b45309 !important;
            border: 1.5px solid #fde68a !important; border-radius: 8px;
            font-size: 12.5px; font-weight: 600; transition: all .2s;
        }
        .btn-outline-warning:hover {
            background: #fef3c7 !important; color: #92400e !important;
        }

        .btn-outline-danger {
            background: #fff !important; color: #e11d48 !important;
            border: 1.5px solid #fecdd3 !important; border-radius: 8px;
            font-size: 12.5px; font-weight: 600; transition: all .2s;
        }
        .btn-outline-danger:hover {
            background: #ffe4e6 !important; color: #be123c !important;
        }

        .btn-outline-secondary {
            background: #fff !important; color: var(--text-mid) !important;
            border: 1.5px solid rgba(232,114,138,.25) !important; border-radius: 8px;
            font-size: 12.5px; font-weight: 600; transition: all .2s;
        }
        .btn-outline-secondary:hover {
            background: var(--pink-pale) !important; color: var(--pink-dark) !important;
            border-color: var(--pink) !important;
        }

        .btn-light {
            background: var(--pink-soft) !important; border: 1px solid var(--border) !important;
            color: var(--text-mid) !important; border-radius: 8px; font-weight: 600;
        }
        .btn-light:hover {
            background: var(--pink-pale) !important; color: var(--pink-dark) !important;
        }

        /* ── BOOTSTRAP OVERRIDES ── */
        .text-primary { color: var(--pink-dark) !important; }
        .bg-primary { background: linear-gradient(135deg, var(--pink-dark), var(--pink)) !important; }
        .bg-light { background-color: var(--pink-soft) !important; }

        /* ── PAGINATION BEAUTY ── */
        .pagination {
            display: inline-flex !important;
            gap: 5px !important;
            margin: 0 !important;
            padding: 0 !important;
            list-style: none !important;
            align-items: center !important;
        }
        .page-item .page-link {
            border-radius: 8px !important;
            border: 1.5px solid rgba(232,114,138,.25) !important;
            color: #4a3540 !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            padding: 6px 14px !important;
            background: #ffffff !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 36px !important;
            height: 36px !important;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03) !important;
            text-decoration: none !important;
        }
        .page-item:hover .page-link {
            background: var(--pink-pale) !important;
            color: var(--pink-dark) !important;
            border-color: var(--pink-light) !important;
        }
        .page-item.active .page-link {
            background: linear-gradient(135deg, var(--pink-dark), var(--pink)) !important;
            border-color: var(--pink-dark) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(232,114,138,.35) !important;
        }
        .page-item.disabled .page-link {
            opacity: 0.45 !important;
            background: #fafafa !important;
            color: #94a3b8 !important;
            border-color: #e2e8f0 !important;
            cursor: not-allowed !important;
        }
        .pagination svg {
            width: 16px !important;
            height: 16px !important;
        }
        nav[role="navigation"] {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        nav[role="navigation"] > div:first-child {
            display: none !important;
        }

        .form-control, .custom-select {
            border-radius: 8px;
            border: 1.5px solid rgba(232,114,138,.25);
            background: #fff;
            color: var(--text-dark);
            transition: all .2s;
        }
        .form-control:focus, .custom-select:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(232,114,138,.15);
        }

        .badge-primary {
            background: var(--pink-pale) !important;
            color: var(--pink-dark) !important;
            border: 1px solid rgba(232,114,138,.3);
            font-weight: 600;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--pink-dark), var(--pink));
            border-color: var(--pink);
        }
        .pagination .page-link {
            color: var(--pink-dark);
            border-color: var(--border);
        }
    </style>
    @yield('styles')
</head>
<body class="boutique-admin">
    <button type="button" class="ht-admin-backdrop" aria-label="Đóng menu quản trị" hidden></button>

    {{-- Sidebar --}}
    <aside class="admin-sidebar" id="admin-sidebar">
        <a href="{{ Auth::user()->role === 'admin' ? route('admin.dashboard') : route('admin.livestreams.index') }}" class="sidebar-brand">
            <div class="brand-icon">@include('partials.icon', ['name' => 'flower', 'size' => 24])</div>
            <div class="brand-text">
                <strong>Soopi</strong>
                <span>Admin Panel</span>
            </div>
        </a>

        <ul class="sidebar-menu" style="padding-top:12px;">
            @if(Auth::user()->role === 'admin')
            <li class="sidebar-section-label">Tổng quan</li>
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="sidebar-section-label" style="padding-top:14px;">Quản lý</li>
            <li>
                <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-spray-can-sparkles"></i>
                    <span>Sản phẩm</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Danh mục</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Đơn hàng</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.coupons.index') }}" class="{{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-ticket"></i><span>Mã ưu đãi</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.articles.index') }}" class="{{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-book-open"></i><span>Cẩm nang</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.videos.index') }}" class="{{ request()->routeIs('admin.videos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clapperboard"></i><span>Video & Shorts</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i>
                    <span>Người dùng</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Báo cáo</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.finance.index') }}" class="{{ request()->routeIs('admin.finance.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-wallet"></i><span>Thống kê tài chính</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.finance.transactions') }}" class="{{ request()->routeIs('admin.finance.transactions') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-transfer"></i><span>Giao dịch thanh toán</span>
                </a>
            </li>

            @endif
            <li>
                <a href="{{ route('admin.livestreams.index') }}" class="{{ request()->routeIs('admin.livestreams.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-video"></i><span>Livestream</span>
                </a>
            </li>
            <hr class="sidebar-divider">
            <li>
                <a href="{{ route('home') }}" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Xem cửa hàng</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="admin-user-info">
                <div class="admin-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}</div>
                <div class="admin-meta">
                    <div class="name">{{ Auth::user()->name ?? 'Admin' }}</div>
                    <div class="role">{{ Auth::user()->role === 'admin' ? 'Quản trị viên' : 'Nhân viên livestream' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn-sidebar-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="admin-main-wrapper">
        <header class="admin-topbar">
            <div class="topbar-left"><button type="button" class="ht-admin-toggle" aria-label="Mở menu quản trị" aria-controls="admin-sidebar" aria-expanded="false">@include('partials.icon', ['name' => 'menu'])<span>Menu</span></button>
                <div>
                    <h1>@yield('page_title', 'Quản trị hệ thống')</h1>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date">
                    <i class="fa-regular fa-calendar"></i>
                    {{ date('d/m/Y') }}
                </div>
                <span class="topbar-badge">🌸 Online</span>
            </div>
        </header>

        <main class="admin-content-area">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @if(Auth::user()->role === 'admin')
    {{-- LAB 7: ADMIN LIVECHAT POPUP (PDF Trang 13 - 14 + Chủ động nhắn tin) --}}
    <div id="admin-chat-box">
        <button id="chat-toggle" class="btn btn-dark shadow">💬 Chat Khách hàng</button>
        <div id="chat-popup" class="card shadow-lg" style="display:none;">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-success" style="width:8px; height:8px; border-radius:50%; display:inline-block; padding:0;"></span>
                    <strong style="font-size:0.95rem;">Hỗ trợ khách hàng</strong>
                </div>
                <button id="chat-close" class="btn btn-sm btn-outline-light py-0 px-2 font-weight-bold">&times;</button>
            </div>

            {{-- Thanh tìm kiếm & chuyển chế độ --}}
            <div class="p-2 bg-light border-bottom">
                <div class="input-group input-group-sm mb-1">
                    <input type="text" id="chat-search-input" class="form-control form-control-sm" placeholder="🔍 Tìm khách hàng (tên, email)...">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="btn-clear-search" style="display:none;">&times;</button>
                    </div>
                </div>
                <div class="d-flex gap-1 justify-content-between align-items-center">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 active" id="chat-tab-recent" style="font-size:0.75rem;">Đã nhắn tin</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0" id="chat-tab-all" style="font-size:0.75rem;">Tất cả khách</button>
                    </div>
                </div>
            </div>

            {{-- Header user đang chọn --}}
            <div id="chat-active-header" class="px-3 py-1 bg-white border-bottom text-truncate small" style="display:none; color:#db2777; font-weight:600;">
                💬 Đang trò chuyện với: <span id="active-user-name" class="text-dark"></span>
            </div>

            {{-- Danh sách User --}}
            <div id="user-list">
                <div class="p-2 text-center text-muted"><small>Đang tải danh sách...</small></div>
            </div>

            {{-- Vùng hiển thị tin nhắn --}}
            <div id="chat-messages">
                <div class="text-center mt-5 text-muted small">
                    <i class="fa-regular fa-comments mb-2" style="font-size:2rem; opacity:0.35;"></i>
                    <div>Chọn một khách hàng hoặc tìm kiếm để bắt đầu nhắn tin</div>
                </div>
            </div>

            {{-- Ô nhập tin nhắn --}}
            <div class="card-footer bg-white p-2">
                <div class="input-group">
                    <input type="text" id="chat-input" class="form-control form-control-sm" placeholder="Nhập tin nhắn gửi khách..." disabled>
                    <div class="input-group-append">
                        <button id="send-btn" class="btn btn-success btn-sm px-3" disabled>
                            <i class="fa-solid fa-paper-plane"></i> Gửi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* LAB 7: ADMIN CHAT STYLING */
    #admin-chat-box {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
    }
    #admin-chat-box #chat-toggle {
        border-radius: 30px;
        padding: 10px 20px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        cursor: pointer;
    }
    #admin-chat-box #chat-popup {
        width: 440px;
        height: 520px;
        position: absolute;
        bottom: 55px;
        right: 0;
        border-radius: 12px;
        overflow: hidden;
        flex-direction: column;
    }
    #user-list {
        max-height: 90px;
        overflow-y: auto;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        padding: 6px 8px;
    }
    .user-item {
        padding: 3px 10px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 16px;
        font-size: 0.78rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        max-width: 135px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .user-item:hover {
        background: #e2e8f0;
    }
    .user-item.active {
        background: #db2777;
        border-color: #db2777;
        color: #fff;
        font-weight: 700;
    }
    #admin-chat-box #chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 14px 12px;
        background: #faf8f9;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .msg-row {
        max-width: 85%;
        font-size: 0.88rem;
        word-break: break-word;
        padding: 8px 12px;
        border-radius: 12px;
        line-height: 1.42;
    }
    .msg-admin {
        align-self: flex-end;
        background: #2563eb;
        color: #ffffff;
        border-bottom-right-radius: 3px;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2);
    }
    .msg-admin .msg-author {
        color: #dbeafe;
    }
    .msg-admin .msg-time {
        color: rgba(255, 255, 255, 0.75);
    }
    .msg-customer {
        align-self: flex-start;
        background: #ffffff;
        color: #1f2937;
        border: 1px solid #f1e4eb;
        border-bottom-left-radius: 3px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }
    .msg-customer .msg-author {
        color: #be185d;
    }
    .msg-customer .msg-time {
        color: #9ca3af;
    }
    .msg-meta-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 3px;
        font-size: 0.72rem;
    }
    .msg-author {
        font-weight: 700;
    }
    .msg-time {
        font-size: 0.68rem;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    </style>
    @endif

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    @yield('scripts')

    {{-- LAB 7 & LAB 8: ADMIN CHAT SCRIPT LOGIC (Chủ động nhắn tin) --}}
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        let currentUserId = null;
        let currentUserName = "";
        let currentMode = "recent"; // "recent" | "all"
        let searchTimeout = null;

        const chatToggle = document.getElementById("chat-toggle");
        const chatPopup = document.getElementById("chat-popup");
        const chatClose = document.getElementById("chat-close");
        const chatMessages = document.getElementById("chat-messages");
        const chatInput = document.getElementById("chat-input");
        const sendBtn = document.getElementById("send-btn");
        const searchInput = document.getElementById("chat-search-input");
        const clearSearchBtn = document.getElementById("btn-clear-search");
        const tabRecent = document.getElementById("chat-tab-recent");
        const tabAll = document.getElementById("chat-tab-all");
        const activeHeader = document.getElementById("chat-active-header");
        const activeUserName = document.getElementById("active-user-name");

        if (!chatToggle || !chatPopup) return;

        chatToggle.onclick = () => {
            chatPopup.style.display = "flex";
            loadUsers();
        };
        chatClose.onclick = () => {
            chatPopup.style.display = "none";
        };

        if (tabRecent) {
            tabRecent.onclick = () => {
                currentMode = "recent";
                tabRecent.classList.add("active");
                tabAll.classList.remove("active");
                if (searchInput) searchInput.value = "";
                loadUsers();
            };
        }
        if (tabAll) {
            tabAll.onclick = () => {
                currentMode = "all";
                tabAll.classList.add("active");
                tabRecent.classList.remove("active");
                if (searchInput) searchInput.value = "";
                loadUsers();
            };
        }

        // Tìm kiếm khách hàng realtime
        if (searchInput) {
            searchInput.addEventListener("input", function () {
                const q = this.value.trim();
                if (clearSearchBtn) clearSearchBtn.style.display = q ? "inline-block" : "none";
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadUsers(q);
                }, 300);
            });
        }
        if (clearSearchBtn) {
            clearSearchBtn.onclick = () => {
                searchInput.value = "";
                clearSearchBtn.style.display = "none";
                loadUsers();
            };
        }

        // 1. Tải danh sách khách hàng
        function loadUsers(keyword = "") {
            let url = "{{ route('admin.chat.users') }}";
            if (keyword) {
                url += "?search=" + encodeURIComponent(keyword);
            } else if (currentMode === "all") {
                url += "?mode=all";
            }

            fetch(url)
                .then(res => res.json())
                .then(users => {
                    let html = "";
                    if (!users || users.length === 0) {
                        html = '<div class="p-2 text-muted small w-100 text-center">Không tìm thấy khách hàng nào</div>';
                    } else {
                        users.forEach(user => {
                            let activeClass = (currentUserId == user.id) ? 'active' : '';
                            let escapedName = (user.name || "Khách").replace(/'/g, "\\'");
                            html += `<div class="user-item ${activeClass}" title="${user.name} (${user.email || ''})" onclick="selectUser(${user.id}, '${escapedName}', this)">
                                ${user.name}
                            </div>`;
                        });
                    }
                    document.getElementById("user-list").innerHTML = html;
                })
                .catch(err => console.error("Lỗi load users:", err));
        }

        // 2. Chọn khách hàng để chat
        window.selectUser = function(userId, userName, element) {
            currentUserId = userId;
            currentUserName = userName || "Khách hàng";

            if (activeHeader && activeUserName) {
                activeUserName.innerText = currentUserName;
                activeHeader.style.display = "block";
            }

            if (chatInput) {
                chatInput.disabled = false;
                chatInput.placeholder = `Nhắn tin cho ${currentUserName}...`;
            }
            if (sendBtn) sendBtn.disabled = false;

            document.querySelectorAll('.user-item').forEach(el => el.classList.remove('active'));
            if (element) element.classList.add('active');

            loadMessages();
            if (chatInput) chatInput.focus();
        };

        // 3. Admin chủ động mở chat từ bảng Đơn hàng hoặc Người dùng
        window.openChatWithUser = function(userId, userName) {
            if (!userId) return;
            chatPopup.style.display = "flex";
            window.selectUser(userId, userName, null);
        };

        function formatChatTime(dateStr) {
            if (!dateStr) return '';
            try {
                let s = String(dateStr).trim();
                if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/.test(s)) {
                    s = s.replace(' ', 'T');
                }
                const d = new Date(s);
                if (isNaN(d.getTime())) return '';
                const now = new Date();
                const hours = String(d.getHours()).padStart(2, '0');
                const minutes = String(d.getMinutes()).padStart(2, '0');
                const timePart = `${hours}:${minutes}`;

                if (d.toDateString() === now.toDateString()) {
                    return timePart;
                }
                const yest = new Date(now);
                yest.setDate(now.getDate() - 1);
                if (d.toDateString() === yest.toDateString()) {
                    return `Hôm qua ${timePart}`;
                }
                return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')} ${timePart}`;
            } catch (e) {
                return '';
            }
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        // 4. Tải tin nhắn của khách hàng được chọn
        function loadMessages() {
            if (!currentUserId) return;
            fetch(`/admin/chat/messages/${currentUserId}`)
                .then(res => res.json())
                .then(messages => {
                    let html = "";
                    if (!messages || messages.length === 0) {
                        html = `<div class="text-center mt-4 text-muted small">
                            <i class="fa-regular fa-paper-plane mb-2" style="font-size:1.8rem; opacity:0.35;"></i>
                            <div>Chưa có tin nhắn nào với <strong>${currentUserName}</strong>.</div>
                            <div class="mt-1 text-primary">Hãy chủ động gửi tin nhắn đầu tiên bên dưới!</div>
                        </div>`;
                    } else {
                        messages.forEach(msg => {
                            let isAdmin = msg.is_ai || msg.sender?.role === 'admin';
                            let senderName = msg.is_ai ? "Trợ lý AI · Groq" : (msg.sender_id == "{{ Auth::id() }}" ? "Bạn (Admin)" : (msg.sender ? msg.sender.name : "Khách hàng"));
                            let rowClass = isAdmin ? "msg-admin" : "msg-customer";
                            let timeStr = formatChatTime(msg.created_at);

                            html += `<div class="msg-row ${rowClass}">
                                <div class="msg-meta-header">
                                    <span class="msg-author">${escapeHtml(senderName)}</span>
                                    <span class="msg-time">${timeStr}</span>
                                </div>
                                <div style="margin-top:2px; white-space:pre-wrap; overflow-wrap:anywhere;">${escapeHtml(msg.content)}</div>
                            </div>`;
                        });
                    }
                    chatMessages.innerHTML = html;
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(err => console.error("Lỗi load tin nhắn:", err));
        }

        // 5. Gửi tin nhắn chủ động hoặc phản hồi
        function sendMessage() {
            let message = chatInput.value.trim();
            if (!message || !currentUserId) return;
            sendBtn.disabled = true;

            fetch("{{ route('admin.chat.send') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: message,
                    user_id: currentUserId
                })
            })
            .then(res => res.json())
            .then(data => {
                chatInput.value = "";
                sendBtn.disabled = false;
                loadMessages();
                if (currentMode === "recent") {
                    loadUsers();
                }
            })
            .catch(err => {
                sendBtn.disabled = false;
                console.error("Lỗi gửi tin:", err);
            });
        }

        if (sendBtn) sendBtn.onclick = sendMessage;
        if (chatInput) {
            chatInput.onkeypress = (e) => {
                if (e.key === 'Enter') sendMessage();
            };
        }

        // 6. Polling cập nhật mỗi 3 giây
        setInterval(() => {
            if (chatPopup.style.display !== "none" && currentUserId) {
                loadMessages();
            }
        }, 3000);
    });
    </script>
@vite(['resources/css/admin-boutique.css', 'resources/js/admin-boutique.js'])
</body>
</html>
