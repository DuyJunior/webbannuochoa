<?php

namespace Tests\Feature;

use App\Jobs\ReplyToCustomerMessage;
use App\Models\ChatConversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai_chat.enabled' => true,
            'ai_chat.free_plan_confirmed' => true,
            'ai_chat.api_key' => 'test-key-do-not-expose',
            'ai_chat.model' => 'openai/gpt-oss-20b',
        ]);

        Http::preventStrayRequests();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'user']);
    }

    public function test_ai_status_and_mode_require_authentication(): void
    {
        $this->getJson(route('user.chat.status'))->assertUnauthorized();
        $this->postJson(route('user.chat.mode'), ['mode' => 'human'])->assertUnauthorized();
    }

    public function test_sending_a_customer_message_queues_ai_without_losing_the_message(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->customer)
            ->postJson(route('user.chat.send'), ['message' => 'Tư vấn giúp tôi nước hoa đi làm.'])
            ->assertOk()
            ->assertJson([
                'sender_id' => $this->customer->id,
                'receiver_id' => $this->admin->id,
                'content' => 'Tư vấn giúp tôi nước hoa đi làm.',
                'ai_status' => 'pending',
            ]);

        $this->assertDatabaseHas('messages', [
            'id' => $response->json('id'),
            'content' => 'Tư vấn giúp tôi nước hoa đi làm.',
            'ai_status' => 'pending',
        ]);
        Queue::assertPushed(ReplyToCustomerMessage::class, 1);
        $this->getJson(route('user.chat.status'))
            ->assertOk()
            ->assertJson(['ai_available' => true, 'mode' => 'ai', 'pending' => true]);
    }

    public function test_disabled_ai_keeps_normal_chat_working_without_queueing_a_reply(): void
    {
        config(['ai_chat.enabled' => false]);
        Queue::fake();

        $this->actingAs($this->customer)
            ->postJson(route('user.chat.send'), ['message' => 'Tôi muốn gặp nhân viên.'])
            ->assertOk();

        Queue::assertNotPushed(ReplyToCustomerMessage::class);
        $this->assertDatabaseHas('messages', ['content' => 'Tôi muốn gặp nhân viên.']);
        $this->getJson(route('user.chat.status'))->assertJson(['ai_available' => false, 'pending' => false]);
    }

    public function test_missing_api_key_does_not_queue_ai_or_expose_configuration(): void
    {
        config(['ai_chat.api_key' => '']);
        Queue::fake();

        $this->actingAs($this->customer)
            ->postJson(route('user.chat.send'), ['message' => 'Shop có nước hoa nam không?'])
            ->assertOk();

        Queue::assertNotPushed(ReplyToCustomerMessage::class);
        $response = $this->getJson(route('user.chat.status'))->assertOk()
            ->assertJson(['ai_available' => false, 'pending' => false]);
        $this->assertStringNotContainsString('api_key', $response->getContent());
    }

    public function test_invalid_messages_are_rejected_before_any_job_is_queued(): void
    {
        Queue::fake();
        $this->actingAs($this->customer);

        $this->postJson(route('user.chat.send'), ['message' => '   '])->assertStatus(400);
        $this->postJson(route('user.chat.send'), ['message' => ['invalid']])->assertUnprocessable();
        $this->postJson(route('user.chat.send'), ['message' => str_repeat('a', 1001)])->assertUnprocessable();

        Queue::assertNotPushed(ReplyToCustomerMessage::class);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_ai_reply_is_saved_once_in_the_original_customer_conversation(): void
    {
        $incoming = $this->incoming('Tôi thích mùi nhẹ nhàng.');
        Http::fake(['api.groq.com/openai/v1/chat/completions' => Http::response($this->completion('Bạn có thể chọn mùi hương hoa nhẹ để đi làm.'))]);

        $this->runReply($incoming);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->admin->id,
            'receiver_id' => $this->customer->id,
            'reply_to_id' => $incoming->id,
            'is_ai' => true,
            'content' => 'Bạn có thể chọn mùi hương hoa nhẹ để đi làm.',
        ]);
        $this->assertSame('replied', $incoming->fresh()->ai_status);
        $this->actingAs($this->customer)->getJson(route('user.chat.messages'))
            ->assertOk()
            ->assertJsonFragment(['is_ai' => true, 'reply_to_id' => $incoming->id]);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-key-do-not-expose')
            && $request['model'] === 'openai/gpt-oss-20b');
    }

    public function test_ai_uses_active_catalog_and_this_customers_history_only(): void
    {
        $otherCustomer = User::factory()->create(['role' => 'user', 'email' => 'private-customer@example.test']);
        $this->incoming('PRIVATE_OTHER_CHAT_583', $otherCustomer);
        Message::create([
            'sender_id' => $this->admin->id,
            'receiver_id' => $this->customer->id,
            'content' => 'HISTORY_FOR_THIS_CUSTOMER_119',
            'is_read' => true,
        ]);
        $this->perfume('Public Rose Perfume', true);
        $this->perfume('HIDDEN_CATALOG_915', false);
        Order::create([
            'user_id' => $this->customer->id,
            'customer_name' => 'PRIVATE_ORDER_NAME_553',
            'phone' => '0901234567',
            'address' => 'PRIVATE_ADDRESS_553',
            'total_price' => 1000000,
            'status' => 'pending',
        ]);
        $incoming = $this->incoming('Tư vấn Public Rose Perfume cho tôi.');
        Http::fake(['api.groq.com/openai/v1/chat/completions' => Http::response($this->completion('Shop có Public Rose Perfume.'))]);

        $this->runReply($incoming);

        Http::assertSent(function (Request $request) use ($otherCustomer) {
            $payload = json_encode($request->data(), JSON_UNESCAPED_UNICODE);

            $this->assertStringContainsString('Public Rose Perfume', $payload);
            $this->assertStringContainsString('HISTORY_FOR_THIS_CUSTOMER_119', $payload);
            $this->assertStringNotContainsString('PRIVATE_OTHER_CHAT_583', $payload);
            $this->assertStringNotContainsString('HIDDEN_CATALOG_915', $payload);
            $this->assertStringNotContainsString('PRIVATE_ADDRESS_553', $payload);
            $this->assertStringNotContainsString('PRIVATE_ORDER_NAME_553', $payload);
            $this->assertStringNotContainsString($otherCustomer->email, $payload);
            $this->assertStringNotContainsString($this->customer->email, $payload);

            return true;
        });
    }

    public function test_api_failure_keeps_customer_message_and_creates_no_fake_ai_reply(): void
    {
        $incoming = $this->incoming('Tôi cần tư vấn.');
        Http::fake(['api.groq.com/openai/v1/chat/completions' => Http::response([
            'error' => ['message' => 'PRIVATE_PROVIDER_ERROR test-key-do-not-expose'],
        ], 429)]);

        $this->runReply($incoming);

        $this->assertSame('failed', $incoming->fresh()->ai_status);
        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseHas('messages', ['id' => $incoming->id, 'content' => 'Tôi cần tư vấn.']);
        $response = $this->actingAs($this->customer)->getJson(route('user.chat.status'))
            ->assertOk()->assertJson(['pending' => false]);
        $this->assertNotEmpty($response->json('notice'));
        $this->assertStringNotContainsString('PRIVATE_PROVIDER_ERROR', $response->getContent());
        $this->assertStringNotContainsString('test-key-do-not-expose', $response->getContent());
    }

    public function test_empty_model_output_is_not_saved_as_an_ai_message(): void
    {
        $incoming = $this->incoming('Xin chào.');
        Http::fake(['api.groq.com/openai/v1/chat/completions' => Http::response($this->completion('   '))]);

        $this->runReply($incoming);

        $this->assertSame('failed', $incoming->fresh()->ai_status);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_truncated_response_is_not_published_as_complete_advice(): void
    {
        $source = $this->incoming('Xin chào');
        $response = $this->completion('Câu trả lời bị cắt');
        $response['choices'][0]['finish_reason'] = 'length';
        Http::fake(['api.groq.com/*' => Http::response($response)]);
        $this->runReply($source);
        $this->assertSame('failed', $source->fresh()->ai_status);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_zero_daily_limit_blocks_provider_calls(): void
    {
        config(['ai_chat.daily_request_limit' => 0]);
        $source = $this->incoming('Xin chào');
        $this->runReply($source);
        $this->assertSame('failed', $source->fresh()->ai_status);
        Http::assertNothingSent();
    }

    public function test_customer_can_pause_ai_for_their_own_conversation(): void
    {
        Queue::fake();
        $otherCustomer = User::factory()->create(['role' => 'user']);
        ChatConversation::create(['user_id' => $otherCustomer->id, 'human_mode' => false]);

        $this->actingAs($this->customer)->postJson(route('user.chat.mode'), ['mode' => 'human'])
            ->assertOk();
        $this->getJson(route('user.chat.status'))->assertJson(['mode' => 'human', 'pending' => false]);
        $this->postJson(route('user.chat.send'), ['message' => 'Nhân viên hỗ trợ giúp tôi.'])->assertOk();

        Queue::assertNotPushed(ReplyToCustomerMessage::class);
        $this->assertDatabaseHas('chat_conversations', ['user_id' => $this->customer->id, 'human_mode' => true]);
        $this->assertDatabaseHas('chat_conversations', ['user_id' => $otherCustomer->id, 'human_mode' => false]);
    }

    public function test_customer_can_enable_ai_again_for_future_messages(): void
    {
        Queue::fake();
        ChatConversation::create(['user_id' => $this->customer->id, 'human_mode' => true]);

        $this->actingAs($this->customer)->postJson(route('user.chat.mode'), ['mode' => 'ai'])->assertOk();
        $this->getJson(route('user.chat.status'))->assertJson(['mode' => 'ai']);
        $this->postJson(route('user.chat.send'), ['message' => 'AI tư vấn cho tôi.'])->assertOk();

        $this->assertDatabaseHas('chat_conversations', ['user_id' => $this->customer->id, 'human_mode' => false]);
        Queue::assertPushed(ReplyToCustomerMessage::class);
    }

    public function test_invalid_chat_mode_is_rejected(): void
    {
        $this->actingAs($this->customer)->postJson(route('user.chat.mode'), ['mode' => 'invalid'])
            ->assertUnprocessable();
        $this->assertDatabaseMissing('chat_conversations', ['user_id' => $this->customer->id, 'human_mode' => true]);
    }

    public function test_admin_reply_pauses_ai_and_pending_job_does_not_call_provider(): void
    {
        $incoming = $this->incoming('Tôi cần gặp nhân viên.');
        Http::fake();

        $this->actingAs($this->admin)->postJson(route('admin.chat.send'), [
            'user_id' => $this->customer->id,
            'message' => 'Nhân viên đang hỗ trợ bạn.',
        ])->assertOk();
        $this->runReply($incoming);

        $this->assertDatabaseHas('chat_conversations', ['user_id' => $this->customer->id, 'human_mode' => true]);
        $this->assertSame('skipped', $incoming->fresh()->ai_status);
        $this->assertSame(0, Message::where('is_ai', true)->count());
        Http::assertNothingSent();
    }

    public function test_human_takeover_while_provider_is_running_discards_the_ai_reply(): void
    {
        $incoming = $this->incoming('Xin tư vấn cho tôi.');
        Http::fake(function () {
            ChatConversation::updateOrCreate(['user_id' => $this->customer->id], ['human_mode' => true]);

            return Http::response($this->completion('Câu trả lời đến sau nhân viên.'));
        });

        $this->runReply($incoming);

        $this->assertSame('skipped', $incoming->fresh()->ai_status);
        $this->assertSame(0, Message::where('is_ai', true)->count());
    }

    public function test_replaying_the_same_job_does_not_duplicate_reply_or_provider_request(): void
    {
        $incoming = $this->incoming('Xin chào.');
        Http::fake(['api.groq.com/openai/v1/chat/completions' => Http::response($this->completion('Chào bạn!'))]);

        $this->runReply($incoming);
        $this->runReply($incoming);

        $this->assertSame(1, Message::where('reply_to_id', $incoming->id)->count());
        $this->assertSame('replied', $incoming->fresh()->ai_status);
        Http::assertSentCount(1);
    }

    public function test_stale_job_is_skipped_when_customer_has_sent_a_newer_message(): void
    {
        $older = $this->incoming('Tôi muốn nước hoa.');
        $newer = $this->incoming('Ngân sách của tôi là một triệu.');
        Http::fake(['api.groq.com/openai/v1/chat/completions' => Http::response($this->completion('Tôi sẽ tư vấn theo ngân sách của bạn.'))]);

        $this->runReply($older);

        $this->assertSame('skipped', $older->fresh()->ai_status);
        Http::assertNothingSent();

        $this->runReply($newer);

        $this->assertSame('replied', $newer->fresh()->ai_status);
        $this->assertSame(1, Message::where('is_ai', true)->count());
        $this->assertDatabaseHas('messages', ['reply_to_id' => $newer->id, 'is_ai' => true]);
    }

    public function test_new_customer_message_during_provider_request_discards_stale_reply(): void
    {
        $incoming = $this->incoming('Tôi muốn tìm nước hoa.');
        Http::fake(function () {
            $this->incoming('Tôi cần loại dành cho nam.');

            return Http::response($this->completion('Câu trả lời cho câu hỏi cũ.'));
        });

        $this->runReply($incoming);

        $this->assertSame('skipped', $incoming->fresh()->ai_status);
        $this->assertSame(0, Message::where('is_ai', true)->count());
    }

    public function test_ai_disabled_after_dispatch_skips_pending_job(): void
    {
        $incoming = $this->incoming('Xin chào.');
        config(['ai_chat.enabled' => false]);
        Http::fake();

        $this->runReply($incoming);

        $this->assertSame('skipped', $incoming->fresh()->ai_status);
        Http::assertNothingSent();
    }

    public function test_free_plan_confirmation_is_required_before_any_call(): void
    {
        config(['ai_chat.free_plan_confirmed' => false]);
        Queue::fake();
        $this->actingAs($this->customer)->postJson(route('user.chat.send'), ['message' => 'Xin chào'])->assertOk();
        $this->getJson(route('user.chat.status'))->assertJson(['ai_available' => false]);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_daily_limit_is_shared_between_customers_and_fails_closed(): void
    {
        config(['ai_chat.daily_request_limit' => 1]);
        Http::fake(['api.groq.com/*' => Http::response($this->completion('Chào bạn!'))]);
        $this->runReply($this->incoming('Xin chào'));
        $other = User::factory()->create(['role' => 'user']);
        $blocked = $this->incoming('Tư vấn giúp tôi', $other);
        $this->runReply($blocked);
        Http::assertSentCount(1);
        $this->assertSame('failed', $blocked->fresh()->ai_status);
        $this->assertDatabaseHas('ai_chat_usage', ['day' => now('UTC')->toDateString(), 'requests' => 1]);
    }

    public function test_rate_limit_stops_subsequent_calls_without_retry_or_fallback(): void
    {
        Http::fake(['api.groq.com/*' => Http::response([], 429, ['retry-after' => '120'])]);
        $first = $this->incoming('Xin chào');
        $this->runReply($first);
        $second = $this->incoming('Tư vấn giúp tôi');
        $this->runReply($second);
        Http::assertSentCount(1);
        $this->assertSame('failed', $second->fresh()->ai_status);
        $this->travel(121)->seconds();
        $this->runReply($this->incoming('Thử lại sau thời gian chờ'));
        Http::assertSentCount(2);
    }

    public function test_stopped_worker_does_not_leave_typing_indicator_forever(): void
    {
        $source = $this->incoming('Xin chào');
        $this->travel(3)->minutes();
        $this->actingAs($this->customer)->getJson(route('user.chat.status'))->assertJson(['pending' => false]);
        $this->assertSame('failed', $source->fresh()->ai_status);
        $this->runReply($source);
        Http::assertNothingSent();
    }

    public function test_missing_admin_does_not_send_to_arbitrary_user_id(): void
    {
        $this->admin->delete();
        $this->actingAs($this->customer)->postJson(route('user.chat.send'), ['message' => 'Xin chào'])->assertStatus(503);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_database_queue_can_deliver_reply_using_a_real_serialized_job(): void
    {
        Http::fake(['api.groq.com/*' => Http::response($this->completion('Chào bạn!'))]);
        $response = $this->actingAs($this->customer)->postJson(route('user.chat.send'), ['message' => 'Xin chào'])->assertOk();
        Http::assertNothingSent();
        $this->assertDatabaseHas('jobs', ['queue' => 'ai-chat']);
        $job = Queue::connection('database')->pop('ai-chat');
        $this->assertNotNull($job);
        $job->fire();
        $this->assertDatabaseHas('messages', ['reply_to_id' => $response->json('id'), 'is_ai' => true]);
        Http::assertSentCount(1);
    }

    public function test_queue_failure_preserves_the_customers_saved_message(): void
    {
        Bus::shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Private queue error'));
        $this->actingAs($this->customer)->postJson(route('user.chat.send'), ['message' => 'Xin chào'])
            ->assertOk()->assertJson(['content' => 'Xin chào', 'ai_status' => 'failed']);
        $this->assertDatabaseCount('messages', 1);
        Http::assertNothingSent();
    }

    public function test_network_timeout_is_handled_without_a_fake_reply(): void
    {
        Http::fake(fn () => throw new ConnectionException('Timeout'));
        $source = $this->incoming('Xin chào');
        $this->runReply($source);
        $this->assertSame('failed', $source->fresh()->ai_status);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_chat_view_discloses_provider_but_never_exposes_key(): void
    {
        $this->actingAs($this->customer);
        $html = view('partials.chat_popup')->render();
        $this->assertStringContainsString('Groq', $html);
        $this->assertStringContainsString('Gặp nhân viên', $html);
        $this->assertStringContainsString('js/customer-chat.js', $html);
        $this->assertStringNotContainsString('test-key-do-not-expose', $html);
    }

    public function test_customer_cannot_read_another_customers_chat_or_account_details(): void
    {
        $other = User::factory()->create(['role' => 'user']);
        $this->incoming('PRIVATE_OTHER_CHAT', $other);
        $this->incoming('MY_CHAT');
        $response = $this->actingAs($this->customer)->getJson(route('user.chat.messages'))->assertOk()->assertJsonCount(1);
        $this->assertStringNotContainsString('PRIVATE_OTHER_CHAT', $response->getContent());
        $this->assertStringNotContainsString($this->admin->email, $response->getContent());
    }

    private function incoming(string $content, ?User $customer = null): Message
    {
        return Message::create([
            'sender_id' => ($customer ?? $this->customer)->id,
            'receiver_id' => $this->admin->id,
            'content' => $content,
            'is_read' => false,
            'is_ai' => false,
            'ai_status' => 'pending',
        ]);
    }

    private function perfume(string $name, bool $active): Perfume
    {
        return Perfume::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'brand' => 'Test Brand',
            'gender' => 'unisex',
            'volume_ml' => 100,
            'price' => 1200000,
            'stock' => 3,
            'is_active' => $active,
        ]);
    }

    private function runReply(Message $incoming): void
    {
        app()->call([new ReplyToCustomerMessage($incoming->id), 'handle']);
    }

    private function completion(string $text): array
    {
        return ['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => $text]]]];
    }
}
