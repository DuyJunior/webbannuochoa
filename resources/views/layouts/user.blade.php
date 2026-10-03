<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Soopi · Perfume Studio')</title>
    @include('partials.brand-favicon')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,600&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap&subset=vietnamese" rel="stylesheet">

    <style>
        body {
            font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .store-brand {
            width: 190px;
            max-width: 48vw;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #c94d68 !important;
            text-decoration: none !important;
        }
        .store-brand > span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border: 1.5px solid #e8728a;
            border-radius: 6px;
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 22px;
            font-weight: bold;
            color: #c94d68;
            line-height: 1;
        }
        .store-brand strong {
            font-size: 22px;
            font-weight: 700;
            color: #2d1a20;
            line-height: 1.1;
        }
        .store-brand small {
            display: block;
            margin-top: 2px;
            color: #e8728a;
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .nav-link-btn {
            background: none;
            border: none;
            padding: 0;
            margin: 0;
            color: #c94d68;
            cursor: pointer;
        }
        .nav-link-btn:hover {
            text-decoration: underline;
        }

        /* ── LAB 7: USER CHAT POPUP STYLES ── */
        #chat-box {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
        }
        #chat-toggle {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #db2777;
            border: none;
            color: #fff;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(219, 39, 119, 0.4);
            transition: all 0.2s;
        }
        #chat-toggle:hover {
            transform: scale(1.06);
        }
        #chat-popup {
            width: 330px;
            height: 430px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #fce7f3;
        }
        #chat-popup .card-header {
            background: #db2777;
            color: #fff;
            font-weight: 600;
            padding: 10px 14px;
        }
        #chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #fafafa;
        }
        .message-row {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.88rem;
            max-width: 85%;
            word-break: break-word;
        }
        .user-msg {
            align-self: flex-end;
            background: #fce7f3;
            color: #9d174d;
            text-align: right;
        }
        .admin-msg {
            align-self: flex-start;
            background: #e5e7eb;
            color: #111827;
            text-align: left;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2">
    <a class="navbar-brand store-brand" href="{{ route('home') }}" aria-label="Soopi — Trang chủ">
        @include('partials.brand-logo')
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
            {{-- Trang chủ --}}
            <li class="nav-item">
                <a class="nav-link" href="{{ route('welcome') }}">Trang chủ</a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            @auth
                {{-- Link lịch sử đơn hàng --}}
                @if(Auth::user()->role === 'user' || Auth::user()->role === 'customer')
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('user.orders.index') }}">@include('partials.icon', ['name' => 'copy', 'size' => '1em']) Lịch sử đơn</a>
                    </li>
                @endif

                <li class="nav-item">
                    <span class="nav-link">@include('partials.icon', ['name' => 'user', 'size' => '1em']) Xin chào, {{ Auth::user()->name }}</span>
                </li>
                <li class="nav-item">
                    <form action="{{ route('logout') }}" method="POST" class="form-inline">
                        @csrf
                        <button type="submit" class="nav-link-btn nav-link">@include('partials.icon', ['name' => 'logout', 'size' => '1em']) Đăng xuất</button>
                    </form>
                </li>
            @endauth

            @guest
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('register') }}">Đăng ký</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('login') }}">Đăng nhập</a>
                </li>
            @endguest
        </ul>
    </div>
</nav>

<div class="container mt-4">
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @yield('content')
</div>

{{-- Bootstrap JS --}}
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

@include('partials.chat_popup')
</body>
</html>
