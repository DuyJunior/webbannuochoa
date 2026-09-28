<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Quản trị · Soopi</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --pink-main: #e8728a;
            --pink-dark: #c94d68;
            --pink-light: #fce8ed;
            --pink-soft: #fdf2f5;
            --text-title: #2d1a22;
            --text-sub: #7a4b5a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Be Vietnam Pro', -apple-system, sans-serif;
            background: linear-gradient(135deg, #fdf5f7 0%, #fae6ec 50%, #fceef2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle ambient glow circles */
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(232, 114, 138, 0.15), transparent 70%);
            top: -50px;
            right: -50px;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(244, 167, 182, 0.2), transparent 70%);
            bottom: -50px;
            left: -50px;
            pointer-events: none;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 16px 48px rgba(201, 77, 104, 0.12), 0 2px 8px rgba(0,0,0,0.03);
            border: 1px solid rgba(232, 114, 138, 0.22);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            position: relative;
            z-index: 10;
        }

        .login-header {
            background: linear-gradient(135deg, #fff0f3 0%, #fce4eb 100%);
            padding: 38px 30px 28px;
            text-align: center;
            border-bottom: 1px solid rgba(232, 114, 138, 0.18);
            position: relative;
        }

        .brand-icon-wrap {
            width: 54px;
            height: 54px;
            margin: 0 auto 12px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--pink-dark), var(--pink-main));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 6px 16px rgba(232, 114, 138, 0.35);
        }

        .login-header .brand-title {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--text-title);
            margin-bottom: 4px;
        }

        .login-header .badge-admin {
            display: inline-block;
            background: #fff;
            color: var(--pink-dark);
            font-size: 0.72rem;
            letter-spacing: 2px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 20px;
            border: 1px solid rgba(232, 114, 138, 0.3);
            text-transform: uppercase;
        }

        .login-header .desc-text {
            color: var(--text-sub);
            font-size: 0.88rem;
            margin-top: 10px;
            margin-bottom: 0;
        }

        .login-body {
            padding: 32px 30px 36px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-title);
            margin-bottom: 6px;
            display: block;
        }

        .form-control {
            border-radius: 10px;
            border: 1.5px solid rgba(232, 114, 138, 0.25);
            padding: 12px 16px;
            height: auto;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #fdfafb;
        }

        .form-control:focus {
            background: #fff;
            border-color: var(--pink-main);
            box-shadow: 0 0 0 4px rgba(232, 114, 138, 0.16);
            outline: none;
        }

        .btn-admin-login {
            background: linear-gradient(135deg, var(--pink-dark), var(--pink-main));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 0.98rem;
            font-weight: 600;
            width: 100%;
            letter-spacing: 0.3px;
            box-shadow: 0 6px 18px rgba(201, 77, 104, 0.3);
            transition: all 0.25s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-admin-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(201, 77, 104, 0.4);
            color: white;
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 22px;
            color: var(--text-sub);
            font-size: 0.86rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--pink-dark);
            text-decoration: none;
        }

        .alert {
            border-radius: 10px;
            font-size: 0.88rem;
        }
        .alert-danger {
            background: #fff1f2;
            border-color: #fecdd3;
            color: #be123c;
        }
    </style>
</head>
<body class="boutique-admin-login">
    <div class="login-card">
        <div class="login-header">
            <div class="brand-icon-wrap">
                <i class="fa-solid fa-gem"></i>
            </div>
            <h1 class="brand-title">Soopi</h1>
            <span class="badge-admin">Admin Portal</span>
            <p class="desc-text">Đăng nhập để quản lý cửa hàng nước hoa</p>
        </div>

        <div class="login-body">
            {{-- Thông báo lỗi --}}
            @if ($errors->any())
                <div class="alert alert-danger py-2 mb-3" role="alert">
                    @foreach ($errors->all() as $error)
                        <div><i class="fa-solid fa-circle-exclamation mr-1"></i> {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger py-2 mb-3">
                    <i class="fa-solid fa-circle-exclamation mr-1"></i> {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}">
                @csrf

                <div class="form-group mb-3">
                    <label for="email"><i class="fa-regular fa-envelope mr-1 text-muted"></i> Email quản trị viên</label>
                    <input type="email"
                           name="email"
                           id="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           placeholder="admin@example.com"
                           required
                           autofocus>
                </div>

                <div class="form-group mb-4">
                    <label for="password"><i class="fa-solid fa-lock mr-1 text-muted"></i> Mật khẩu</label>
                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="••••••••"
                           required>
                </div>

                <button type="submit" class="btn-admin-login">
                    <span>Đăng nhập hệ thống</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <a href="{{ route('home') }}" class="back-link">
                <i class="fa-solid fa-arrow-left-long"></i> Quay lại trang mua sắm
            </a>
        </div>
    </div>
@vite('resources/css/admin-boutique.css')
</body>
</html>
