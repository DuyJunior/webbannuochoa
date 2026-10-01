<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Lab8AdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Perfume $perfume;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Boss',
            'email' => 'admin_lab8@test.com',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->user = User::factory()->create([
            'name' => 'Customer A',
            'email' => 'customer_lab8@test.com',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Nước hoa Nữ',
            'slug' => 'nuoc-hoa-nu',
        ]);

        $this->perfume = Perfume::create([
            'name' => 'Chanel Chance Eau Tendre',
            'slug' => 'chanel-chance-eau-tendre',
            'brand' => 'Chanel',
            'price' => 880000,
            'volume_ml' => 50,
            'weight' => 150,
            'stock' => 50,
            'category_id' => $category->id,
            'gender' => 'nu',
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->user->id,
            'name' => 'Customer A',
            'customer_name' => 'Customer A',
            'phone' => '0912345678',
            'address' => '123 Đường Láng, Hà Nội',
            'total_price' => 880000,
            'status' => 'pending',
            'shipping_status' => 'pending',
            'ghn_order_code' => 'GHN12345',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'perfume_id' => $this->perfume->id,
            'product_id' => $this->perfume->id,
            'quantity' => 1,
            'price' => 880000,
            'volume_ml' => 50,
        ]);
    }

    public function test_admin_can_view_orders_index_with_tabs()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $response->assertStatus(200);
        $response->assertSee('Quản lý đơn hàng');
        $response->assertSee('Tất cả');
        $response->assertSee('Chờ xử lý');
        $response->assertSee('Đang giao');
        $response->assertSee('selectAllOrders');
    }

    public function test_admin_can_view_order_detail()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order->id));
        $response->assertStatus(200);
        $response->assertSee('Customer A');
        $response->assertSee('Chanel Chance Eau Tendre');
    }

    public function test_delivering_order_cannot_be_cancelled()
    {
        $deliveringOrder = Order::create([
            'user_id' => $this->user->id,
            'name' => 'Customer B',
            'customer_name' => 'Customer B',
            'phone' => '0987654321',
            'address' => '456 Cầu Giấy',
            'total_price' => 500000,
            'status' => 'confirmed',
            'shipping_status' => 'delivering', // Đang giao
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.orders.update', $deliveringOrder->id), [
            'status' => 'cancelled',
        ]);

        $response->assertSessionHas('error');
        $deliveringOrder->refresh();
        $this->assertNotEquals('cancelled', $deliveringOrder->status);
    }

    public function test_admin_can_bulk_update_orders_status()
    {
        $order2 = Order::create([
            'user_id' => $this->user->id,
            'name' => 'Customer C',
            'customer_name' => 'Customer C',
            'phone' => '0933333333',
            'address' => '789 Ba Đình',
            'total_price' => 1000000,
            'status' => 'pending',
            'shipping_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.bulk_update'), [
            'order_ids' => [$this->order->id, $order2->id],
            'bulk_status' => 'confirmed',
            'bulk_shipping_status' => 'ready_to_pick',
        ]);

        $response->assertSessionHas('success');
        $this->order->refresh();
        $order2->refresh();

        $this->assertEquals('confirmed', $this->order->status);
        $this->assertEquals('ready_to_pick', $this->order->shipping_status);
        $this->assertEquals('confirmed', $order2->status);
        $this->assertEquals('ready_to_pick', $order2->shipping_status);
    }

    public function test_admin_can_view_reports_and_charts()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Báo cáo doanh thu');
        $response->assertSee('Doanh thu theo danh mục');

        $chartResponse = $this->actingAs($this->admin)->get(route('admin.reports.charts'));
        $chartResponse->assertStatus(200);
        $chartResponse->assertSee('Biểu đồ báo cáo doanh thu');
        $chartResponse->assertSee('categoryRevenueChart');
    }

    public function test_admin_can_crud_users()
    {
        // 1. Index
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
        $response->assertSee('Danh sách người dùng');

        // 2. Create & Store
        $createResponse = $this->actingAs($this->admin)->get(route('admin.users.create'));
        $createResponse->assertStatus(200);

        $storeResponse = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Nhân Viên Mới',
            'email' => 'staff@perfume.com',
            'password' => '123456',
            'role' => 'admin',
        ]);
        $storeResponse->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'staff@perfume.com']);

        $newUser = User::where('email', 'staff@perfume.com')->first();

        // 3. Show & Edit
        $showResponse = $this->actingAs($this->admin)->get(route('admin.users.show', $newUser->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Nhân Viên Mới');

        $editResponse = $this->actingAs($this->admin)->get(route('admin.users.edit', $newUser->id));
        $editResponse->assertStatus(200);

        // 4. Update
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.users.update', $newUser->id), [
            'name' => 'Nhân Viên Pro',
            'email' => 'staff@perfume.com',
            'role' => 'admin',
        ]);
        $updateResponse->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['name' => 'Nhân Viên Pro']);

        // 5. Delete
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $newUser->id));
        $deleteResponse->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
    }

    public function test_admin_can_filter_reports_by_preset_and_category()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.index', [
            'preset' => 'today',
            'gateway' => 'cod',
        ]));
        $response->assertStatus(200);
        $response->assertSee('Báo cáo doanh thu');
        $response->assertSee('Xóa bộ lọc');
        $this->assertSame(now()->toDateString(), $response->viewData('filters')['date_from']);
        $this->assertSame('cod', $response->viewData('filters')['gateway']);

        $chartResponse = $this->actingAs($this->admin)->get(route('admin.reports.charts', [
            'preset' => '7days',
            'gateway' => 'momo',
        ]));
        $chartResponse->assertStatus(200);
        $chartResponse->assertSee('Xóa bộ lọc');
        $this->assertSame(now()->subDays(6)->toDateString(), $chartResponse->viewData('filters')['date_from']);
        $this->assertSame('momo', $chartResponse->viewData('filters')['gateway']);
    }

    public function test_admin_can_proactively_search_and_message_customer()
    {
        // 1. Admin tìm kiếm khách hàng chưa từng nhắn tin
        $searchResponse = $this->actingAs($this->admin)->getJson(route('admin.chat.users', [
            'search' => 'Customer A',
        ]));
        $searchResponse->assertStatus(200);
        $searchResponse->assertJsonFragment(['name' => 'Customer A', 'id' => $this->user->id]);

        // 2. Admin chủ động gửi tin nhắn đầu tiên cho khách hàng
        $sendResponse = $this->actingAs($this->admin)->postJson(route('admin.chat.send'), [
            'user_id' => $this->user->id,
            'message' => 'Chào bạn Customer A, shop có thể hỗ trợ gì cho đơn hàng của bạn?',
        ]);
        $sendResponse->assertStatus(200);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->admin->id,
            'receiver_id' => $this->user->id,
            'content' => 'Chào bạn Customer A, shop có thể hỗ trợ gì cho đơn hàng của bạn?',
        ]);

        // 3. Khách hàng nhận được tin nhắn trong hộp thoại
        $userMsgResponse = $this->actingAs($this->user)->getJson(route('user.chat.messages'));
        $userMsgResponse->assertStatus(200);
        $userMsgResponse->assertJsonFragment([
            'content' => 'Chào bạn Customer A, shop có thể hỗ trợ gì cho đơn hàng của bạn?',
        ]);
    }
}

