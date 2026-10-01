<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveChatTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Support',
            'email' => 'admin@perfume.com',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->user = User::factory()->create([
            'name' => 'Khách Hàng A',
            'email' => 'user@example.com',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_chat()
    {
        $response = $this->getJson(route('user.chat.messages'));
        $response->assertStatus(401);

        $response = $this->postJson(route('user.chat.send'), ['message' => 'Hello']);
        $response->assertStatus(401);
    }

    public function test_user_cannot_send_empty_message()
    {
        $response = $this->actingAs($this->user)->postJson(route('user.chat.send'), [
            'message' => '   ',
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Nội dung tin nhắn không được để trống']);
    }

    public function test_user_can_send_message_to_admin()
    {
        $response = $this->actingAs($this->user)->postJson(route('user.chat.send'), [
            'message' => 'Xin chào, tư vấn giúp tôi chai Chanel Chance!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'sender_id' => $this->user->id,
                'receiver_id' => $this->admin->id,
                'content' => 'Xin chào, tư vấn giúp tôi chai Chanel Chance!',
                'is_read' => false,
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->user->id,
            'receiver_id' => $this->admin->id,
            'content' => 'Xin chào, tư vấn giúp tôi chai Chanel Chance!',
        ]);
    }

    public function test_user_can_get_messages_history()
    {
        Message::create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->admin->id,
            'content' => 'Tin nhắn 1 từ User',
            'is_read' => false,
        ]);

        Message::create([
            'sender_id' => $this->admin->id,
            'receiver_id' => $this->user->id,
            'content' => 'Tin nhắn phản hồi từ Admin',
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('user.chat.messages'));

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['content' => 'Tin nhắn 1 từ User'])
            ->assertJsonFragment(['content' => 'Tin nhắn phản hồi từ Admin']);
    }

    public function test_admin_can_get_user_list_who_chatted()
    {
        Message::create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->admin->id,
            'content' => 'Alo shop ơi',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.chat.users'));

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Khách Hàng A', 'id' => $this->user->id]);
    }

    public function test_admin_customer_search_rejects_non_string_and_excessive_filters(): void
    {
        $this->actingAs($this->admin);
        $this->getJson(route('admin.chat.users', ['search' => ['invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors('search');
        $this->getJson(route('admin.chat.users', ['mode' => ['invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors('mode');
        $this->getJson(route('admin.chat.users', ['search' => str_repeat('a', 101)]))
            ->assertUnprocessable()->assertJsonValidationErrors('search');
        $this->getJson(route('admin.chat.search_customers', ['q' => ['invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors('q');
    }

    public function test_admin_can_get_messages_of_specific_user()
    {
        Message::create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->admin->id,
            'content' => 'Tư vấn nước hoa',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.chat.messages', ['userId' => $this->user->id]));

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['content' => 'Tư vấn nước hoa']);
    }

    public function test_admin_can_reply_message_to_user()
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.chat.send'), [
            'user_id' => $this->user->id,
            'message' => 'Chào bạn, Chanel Chance có sẵn dung tích 10ml, 50ml và 100ml nhé!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'sender_id' => $this->admin->id,
                'receiver_id' => $this->user->id,
                'content' => 'Chào bạn, Chanel Chance có sẵn dung tích 10ml, 50ml và 100ml nhé!',
                'is_read' => true,
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->admin->id,
            'receiver_id' => $this->user->id,
            'content' => 'Chào bạn, Chanel Chance có sẵn dung tích 10ml, 50ml và 100ml nhé!',
        ]);
    }
}
