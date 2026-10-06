<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\LivestreamController as AdminLivestreamController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\GHNWebhookController;
use App\Http\Controllers\GiftExperienceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LivekitRoomController;
use App\Http\Controllers\LivestreamController;
use App\Http\Controllers\LivestreamInteractionController;
use App\Http\Controllers\LivestreamProductController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PerfumeController;
use App\Http\Controllers\ProductQuickViewController;
use App\Http\Controllers\StoreExperienceController;
use App\Http\Controllers\User\ChatController as UserChatController;
use App\Http\Controllers\User\GHNController;
use App\Http\Controllers\User\OrderController as UserOrderController;
use App\Http\Controllers\User\SePayController;
use App\Http\Middleware\PrivateGiftResponse;
use App\Support\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

// ============================================================
// TRANG CHỦ & CỬA HÀNG - Giữ nguyên từ Lab 01 & 02
// ============================================================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/language', [LocaleController::class, 'update'])->name('locale.update');
Route::get('/perfumes/{perfume}/quick-view', [ProductQuickViewController::class, 'show'])->name('perfumes.quick-view');
Route::get('/perfumes/{perfume}/quick-view/login', [ProductQuickViewController::class, 'login'])->name('perfumes.quick-view.login');
Route::get('/livestream', [LivestreamController::class, 'show'])->name('livestream.show');
Route::get('/livestream/state', [LivestreamController::class, 'state'])->name('livestream.state');
Route::get('/livestream/{livestream}/products', [LivestreamProductController::class, 'index'])->name('livestream.products');
Route::get('/livestream/{livestream}/products/{perfume}', [LivestreamProductController::class, 'openProduct'])->name('livestream.products.open');
Route::get('/livestream/{livestream}/messages', [LivestreamInteractionController::class, 'messages'])->middleware('throttle:120,1')->name('livestream.messages');
Route::post('/livestream/{livestream}/messages', [LivestreamInteractionController::class, 'send'])->middleware('throttle:10,1')->name('livestream.messages.send');
Route::post('/livestream/{livestream}/presence', [LivestreamInteractionController::class, 'presence'])->middleware('throttle:6,1')->name('livestream.presence');
Route::post('/livestream/{livestream}/viewer-token', [LivekitRoomController::class, 'viewerToken'])->middleware('throttle:600,1')->name('livestream.viewer-token');
Route::get('/chon-huong', [StoreExperienceController::class, 'finder'])->name('store.finder');
Route::post('/chon-qua', [StoreExperienceController::class, 'giftFinder'])->name('store.gift-finder');
Route::get('/so-sanh', [StoreExperienceController::class, 'compare'])->name('store.compare');
Route::get('/cam-nang', [JournalController::class, 'index'])->name('store.journal');
Route::get('/cam-nang/{article:slug}', [JournalController::class, 'show'])->name('store.article');
Route::get('/cau-hoi-thuong-gap', [StoreExperienceController::class, 'faq'])->name('store.faq');
Route::view('/lien-he', 'store.contact')->name('store.contact');
Route::view('/chinh-sach-bao-mat', 'store.privacy')->name('store.privacy');

Route::get('/quiz', [StoreExperienceController::class, 'quiz'])->name('store.quiz');
Route::get('/trac-nghiem-mui-huong', [StoreExperienceController::class, 'quiz'])->name('store.quiz.alias');
Route::get('/hop-thu-mui', [StoreExperienceController::class, 'discoveryBox'])->name('store.discovery-box');
Route::get('/mui-huong-hom-nay', [StoreExperienceController::class, 'scentOfTheDay'])->name('store.scent-of-the-day');
Route::get('/tang-qua', [StoreExperienceController::class, 'giftShare'])->name('store.gift-share');
Route::middleware(PrivateGiftResponse::class)->group(function () {
    Route::middleware(['auth', 'verified'])->prefix('qua-cua-toi')->name('gifts.')->group(function () {
        Route::get('/', [GiftExperienceController::class, 'index'])->name('index');
        Route::get('/tao', [GiftExperienceController::class, 'create'])->name('create');
        Route::post('/', [GiftExperienceController::class, 'store'])->middleware('throttle:10,1')->name('store');
        Route::get('/{gift}/sua', [GiftExperienceController::class, 'edit'])->name('edit');
        Route::put('/{gift}', [GiftExperienceController::class, 'update'])->middleware('throttle:10,1')->name('update');
        Route::delete('/{gift}', [GiftExperienceController::class, 'destroy'])->name('destroy');
        Route::get('/{gift}/xem-truoc', [GiftExperienceController::class, 'preview'])->name('preview');
        Route::get('/{gift}/thiep', [GiftExperienceController::class, 'card'])->name('card');
    });
    Route::prefix('mo-qua/{token}')->where(['token' => '[A-Za-z0-9]{48}'])->name('gifts.')->group(function () {
        Route::get('/', [GiftExperienceController::class, 'show'])->name('open');
        Route::post('/', [GiftExperienceController::class, 'unlock'])->middleware('throttle:20,1')->name('unlock');
        Route::post('/dong', [GiftExperienceController::class, 'lock'])->name('lock');
        Route::post('/cam-on', [GiftExperienceController::class, 'thank'])->middleware('throttle:5,1')->name('thank');
        Route::get('/media/{kind}', [GiftExperienceController::class, 'media'])->whereIn('kind', ['photo', 'audio'])->name('media');
    });
});
Route::get('/tu-nuoc-hoa/chia-se/{user}', [StoreExperienceController::class, 'shareWardrobe'])->name('store.wardrobe.share');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('perfumes', PerfumeController::class)->except(['index', 'show']);
    Route::resource('categories', CategoryController::class)->except(['index', 'show']);
});
Route::resource('perfumes', PerfumeController::class)->only(['index', 'show']);
Route::resource('categories', CategoryController::class)->only(['index', 'show']);

// ============================================================
// THIRD-PARTY WEBHOOKS (GHN, SePay HMAC)
// NOTE:
// - Không dùng middleware 'auth' vì bên thứ 3 gọi sang tự động.
// - Đã được bypass CSRF trong bootstrap/app.php.
// ============================================================
Route::post('/ghn/webhook', [GHNWebhookController::class, 'handle'])->name('ghn.webhook');
Route::post('/payment/sepay/webhook', [SePayController::class, 'webhook'])->name('payment.sepay.webhook');

// ============================================================
// GIỎ HÀNG & ĐẶT HÀNG - Yêu cầu đăng nhập (auth middleware)
// ============================================================
Route::middleware(['auth'])->group(function () {
    Route::get('/thanh-vien', [StoreExperienceController::class, 'member'])->name('store.member');
    Route::get('/yeu-thich', [StoreExperienceController::class, 'wishlist'])->name('store.wishlist');
    Route::post('/yeu-thich/{perfume}', [StoreExperienceController::class, 'toggleWishlist'])->name('store.wishlist.toggle');
    Route::post('/danh-gia/{perfume}', [StoreExperienceController::class, 'review'])->name('store.review');
    Route::post('/bao-hang/{perfume}', [StoreExperienceController::class, 'stockAlert'])->name('store.stock-alert');
    Route::get('/tu-nuoc-hoa', [StoreExperienceController::class, 'wardrobe'])->name('store.wardrobe');
    Route::post('/tu-nuoc-hoa', [StoreExperienceController::class, 'addToWardrobe'])->name('store.wardrobe.add');
    Route::delete('/tu-nuoc-hoa/{id}', [StoreExperienceController::class, 'removeFromWardrobe'])->name('store.wardrobe.remove');
    Route::get('/tai-khoan', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/tai-khoan', [AccountController::class, 'update'])->middleware('throttle:10,1')->name('account.update');
    Route::put('/tai-khoan/mat-khau', [AccountController::class, 'password'])->middleware('throttle:5,1')->name('account.password');
    Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
    Route::post('/gio-hang/hop-thu-mui', [CartController::class, 'addDiscoveryBox'])->name('cart.add-discovery-box');
    Route::post('/gio-hang/combo/{perfume}', [CartController::class, 'addGiftBundle'])->name('cart.add-gift-bundle');
    Route::post('/gio-hang/{perfume}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/gio-hang/{itemKey}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/gio-hang/{itemKey}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/dat-hang', [CartController::class, 'checkout'])->block(90, 15)->name('cart.checkout');
    Route::get('/gio-hang-nguoi-dung', [CartController::class, 'index'])->name('user.cart.index');

    // Payment & GHN Shipping (Aliased routes)
    Route::get('/payment', [UserOrderController::class, 'index'])->name('payment.index');
    Route::post('/payment/process', [UserOrderController::class, 'processPayment'])->block(90, 15)->name('payment.process');

    // User Orders History & Tracking (Aliased routes)
    Route::get('/orders', [UserOrderController::class, 'orderHistory'])->name('orders.index');
    Route::get('/orders/{order}', [UserOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [UserOrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{order}/pay/sepay', [SePayController::class, 'show'])->name('orders.sepay.pay');
    Route::get('/orders/{order}/payment-status', [SePayController::class, 'status'])->middleware('throttle:30,1')->name('orders.sepay.status');
});

// Nhóm route chuẩn theo tài liệu PDF (prefix 'user', name 'user.')
Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {
    // Payment
    Route::get('/payment', [UserOrderController::class, 'index'])->name('payment.index');
    Route::post('/payment/process', [UserOrderController::class, 'processPayment'])->block(90, 15)->name('payment.process');
    Route::get('/orders/{order}/pay/sepay', [SePayController::class, 'show'])->name('orders.sepay.pay');
    Route::get('/orders/{order}/payment-status', [SePayController::class, 'status'])->middleware('throttle:30,1')->name('orders.sepay.status');
    Route::get('/orders', [UserOrderController::class, 'orderHistory'])->name('orders.index');
    // Thanh toán mô phỏng chỉ dành cho đơn demo local.
    Route::get('/orders/{order}/payment-pending', [UserOrderController::class, 'paymentPending'])->name('orders.payment.pending');
    // Không thể dùng endpoint demo để xác nhận thanh toán thật.
    Route::post('/orders/{order}/confirm-payment', [UserOrderController::class, 'confirmPayment'])->name('orders.confirm.payment');

    // Lab 7: Livechat User (PDF Trang 7-8)
    Route::post('/chat/send', [UserChatController::class, 'send'])->middleware('throttle:15,1')->name('chat.send');
    Route::get('/chat/status', [UserChatController::class, 'status'])->name('chat.status');
    Route::post('/chat/mode', [UserChatController::class, 'mode'])->middleware('throttle:15,1')->name('chat.mode');
    Route::get('/chat/messages', [UserChatController::class, 'getMessages'])->name('chat.messages');
});

// Tra cứu địa giới hành chính & cước vận chuyển GHN
Route::prefix('locations')->name('locations.')->group(function () {
    Route::post('/current-address', \App\Http\Controllers\User\DeliveryLocationController::class)
        ->middleware(['auth', 'throttle:5,1'])->name('current-address');
    Route::get('/provinces', [GHNController::class, 'getProvinces'])->name('provinces');
    Route::get('/districts/{provinceId}', [GHNController::class, 'getDistricts'])->name('districts');
    Route::get('/wards/{districtId}', [GHNController::class, 'getWards'])->name('wards');
    Route::get('/ward-directory/{provinceId}', [GHNController::class, 'getWardDirectory'])
        ->whereNumber('provinceId')->middleware(['auth', 'throttle:10,1'])->name('ward-directory');
    Route::post('/calculate-fee', [GHNController::class, 'getShippingFee'])->name('fee');
});

// ============================================================
// TRA CỨU & KIỂM TRA ĐƠN HÀNG (Public)
// ============================================================
Route::get('/kiem-tra-don-hang', [UserOrderController::class, 'trackingForm'])->middleware('auth')->name('orders.tracking');
Route::post('/kiem-tra-don-hang', [UserOrderController::class, 'trackingSearch'])->middleware(['auth', 'throttle:15,1'])->name('orders.tracking.search');
Route::get('/tra-cuu-don-hang', [UserOrderController::class, 'trackingForm'])->middleware('auth');

// ============================================================
// KHÁCH HÀNG - Đăng ký & Đăng nhập cửa hàng (Lab 03)
// ============================================================
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('/quen-mat-khau', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/quen-mat-khau', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1,password-email')->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [PasswordResetController::class, 'resetForm'])->middleware('throttle:30,1,password-form')->name('password.reset');
    Route::post('/dat-lai-mat-khau', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1,password-reset')->name('password.update');
});

// ============================================================
// XÁC THỰC EMAIL (Lab 03)
// ============================================================
// Hiển thị thông báo xác thực email
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->middleware('auth')->name('verification.notice');
Route::post('/email/verify', [EmailVerificationController::class, 'confirm'])
    ->middleware(['auth', 'throttle:10,1,email-otp'])->name('verification.confirm');

// Liên kết đã ký xác thực trực tiếp, kể cả khi mở email trên trình duyệt khác.
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/demo-preview', function (Request $request) {
    abort_unless(DemoMode::enabled() && config('mail.default') === 'log', 404);
    $user = $request->user();

    return redirect(URL::temporarySignedRoute(
        'verification.verify', now()->addMinutes(15),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    ));
})->middleware(['auth', 'throttle:6,1'])->name('verification.demo');

// Gửi lại email xác nhận
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth', 'throttle:6,1,email-otp-send'])->name('verification.send');

// ============================================================
// ADMIN - Trang đăng nhập & quản trị RIÊNG BIỆT
// Truy cập qua: http://localhost:8000/admin/login
// ============================================================

// Đăng nhập / Đăng xuất Admin
Route::get('/admin', function () {
    if (auth()->check()) {
        if (auth()->user()->canManageLivestreams()) {
            return redirect()->route(auth()->user()->role === 'admin' ? 'admin.dashboard' : 'admin.livestreams.index');
        }

        return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập vào trang quản trị viên!');
    }

    return redirect()->route('admin.login');
});
Route::get('/admin/login', [AuthController::class, 'showAdminLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:10,1')->name('admin.login.post');
Route::post('/admin/logout', [AuthController::class, 'adminLogout'])->name('admin.logout');

Route::middleware(['auth', 'livestream.staff'])->prefix('admin')->group(function () {
    Route::delete('/livestreams/{livestream}/messages/{message}', [LivestreamInteractionController::class, 'hide'])->name('admin.livestreams.messages.hide');
    Route::patch('/livestreams/{livestream}/pin', [LivestreamProductController::class, 'pin'])->name('admin.livestreams.pin');
    Route::get('/livestreams/{livestream}/report', [AdminLivestreamController::class, 'report'])->name('admin.livestreams.report');
    Route::post('/livestreams/{livestream}/products', [LivestreamProductController::class, 'store'])->name('admin.livestreams.products.store');
    Route::delete('/livestreams/{livestream}/products/{perfume}', [LivestreamProductController::class, 'destroy'])->name('admin.livestreams.products.destroy');
    Route::get('/livestreams/{livestream}/studio', [LivekitRoomController::class, 'studio'])->name('admin.livestreams.studio');
    Route::post('/livestreams/{livestream}/host-token', [LivekitRoomController::class, 'hostToken'])->middleware('throttle:30,1')->name('admin.livestreams.host-token');
    Route::post('/livestreams/{livestream}/begin', [LivekitRoomController::class, 'begin'])->name('admin.livestreams.begin');
    Route::post('/livestreams/{livestream}/heartbeat', [LivekitRoomController::class, 'heartbeat'])->middleware('throttle:12,1')->name('admin.livestreams.heartbeat');
    Route::post('/livestreams/{livestream}/finish', [LivekitRoomController::class, 'finish'])->name('admin.livestreams.finish');
    Route::patch('/livestreams/{livestream}/status', [AdminLivestreamController::class, 'changeStatus'])->name('admin.livestreams.status');
    Route::resource('/livestreams', AdminLivestreamController::class, ['as' => 'admin'])->except(['show']);
});

// Khu vực quản trị (yêu cầu đăng nhập với quyền admin)
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/coupons', [CouponController::class, 'index'])->name('admin.coupons.index');
    Route::post('/coupons', [CouponController::class, 'store'])->name('admin.coupons.store');
    Route::post('/coupons/{coupon}/toggle', [CouponController::class, 'toggle'])->name('admin.coupons.toggle');
    Route::resource('/articles', ArticleController::class, ['as' => 'admin'])->except(['show']);
    Route::post('/videos/{video}/toggle', [VideoController::class, 'toggle'])->name('admin.videos.toggle');
    Route::resource('/videos', VideoController::class, ['as' => 'admin'])->except(['show']);
    Route::resource('/products', ProductController::class, ['as' => 'admin']);
    Route::resource('/categories', CategoryController::class, ['as' => 'admin']);
    Route::post('/orders/bulk-update', [OrderController::class, 'bulkUpdate'])->name('admin.orders.bulk_update');
    Route::resource('/orders', OrderController::class, ['as' => 'admin'])->only(['index', 'show', 'update']);

    // Lab 9: Finance and COD reconciliation.
    Route::get('/finance', [FinanceController::class, 'index'])->name('admin.finance.index');
    Route::get('/finance/transactions', [FinanceController::class, 'transactions'])->name('admin.finance.transactions');
    Route::get('/finance/sepay', [FinanceController::class, 'sepayReceipts'])->name('admin.finance.sepay');
    Route::get('/finance/export', [FinanceController::class, 'export'])->name('admin.finance.export');
    Route::patch('/finance/{order}/status', [FinanceController::class, 'updateStatus'])->middleware('throttle:30,1')->name('admin.finance.update-status');

    // Lab 8: Reports (Báo cáo doanh thu & biểu đồ - PDF Trang 7)
    Route::get('/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/export', [AdminReportController::class, 'export'])->name('admin.reports.export');
    Route::get('/reports/charts', [AdminReportController::class, 'charts'])->name('admin.reports.charts');

    // Lab 8: Quản lý người dùng (PDF Trang 21)
    Route::resource('/users', AdminUserController::class, ['as' => 'admin']);

    // Lab 7: Livechat Admin (PDF Trang 8)
    Route::get('/chat/users', [AdminChatController::class, 'getUsers'])->name('admin.chat.users');
    Route::get('/chat/search-customers', [AdminChatController::class, 'searchCustomers'])->name('admin.chat.search_customers');
    Route::get('/chat/messages/{userId}', [AdminChatController::class, 'getMessages'])->name('admin.chat.messages');
    Route::post('/chat/send', [AdminChatController::class, 'send'])->name('admin.chat.send');
});

// Route phụ trợ tương thích Lab 03
Route::get('/welcome', [HomeController::class, 'index'])->name('welcome');
Route::get('/danh-muc', [CategoryController::class, 'index'])->name('user.categories.index');
Route::get('/lich-su-don-hang', [UserOrderController::class, 'orderHistory'])->middleware(['auth']);

Route::middleware(['auth'])->group(function () {
    Route::get('/products/{product}', [ProductController::class, 'show_normal'])->name('products.show');
});
