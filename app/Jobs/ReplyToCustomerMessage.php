<?php

namespace App\Jobs;

use App\Models\ChatConversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AiChatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReplyToCustomerMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $timeout = 40;

    public bool $failOnTimeout = true;

    public function __construct(public int $messageId)
    {
        $this->onConnection(config('ai_chat.connection'));
        $this->onQueue(config('ai_chat.queue'));
    }

    public function handle(AiChatService $ai): void
    {
        $source = Message::find($this->messageId);
        if (! $source || $source->ai_status !== 'pending') {
            return;
        }
        $lock = Cache::lock('ai-chat:'.$source->sender_id, 60);
        if (! $lock->get()) {
            $this->release(5);

            return;
        }
        try {
            if (! $this->eligible($source->fresh(), $ai)) {
                $this->skip();

                return;
            }
            $answer = $ai->reply($source);
            // Do not hold DB locks during the network request. Recheck after it completes.
            DB::transaction(function () use ($source, $answer, $ai) {
                User::whereKey($source->sender_id)->lockForUpdate()->first();
                $current = Message::whereKey($source->id)->lockForUpdate()->first();
                if (! $this->eligible($current, $ai)) {
                    $this->skip();

                    return;
                }
                Message::create([
                    'sender_id' => $current->receiver_id,
                    'receiver_id' => $current->sender_id,
                    'content' => $answer,
                    'is_read' => false,
                    'is_ai' => true,
                    'reply_to_id' => $current->id,
                ]);
                $current->update(['ai_status' => 'replied']);
            });
        } catch (Throwable $error) {
            $this->failed($error);
        } finally {
            $lock->release();
        }
    }

    private function eligible(?Message $source, AiChatService $ai): bool
    {
        return $source && $source->ai_status === 'pending' && ! $source->is_ai
            && $source->created_at->gt(now()->subMinutes(2))
            && $ai->available()
            && $source->sender?->role !== 'admin' && $source->receiver?->role === 'admin'
            && ! ChatConversation::where('user_id', $source->sender_id)->where('human_mode', true)->exists()
            && ! Message::where('reply_to_id', $source->id)->exists()
            && ! Message::conversation($source->sender_id)->where('id', '>', $source->id)->exists();
    }

    private function skip(): void
    {
        Message::whereKey($this->messageId)->where('ai_status', 'pending')->update(['ai_status' => 'skipped']);
    }

    public function failed(?Throwable $error): void
    {
        Message::whereKey($this->messageId)->where('ai_status', 'pending')->update(['ai_status' => 'failed']);
        Log::warning('Groq chat reply unavailable', ['message_id' => $this->messageId]);
    }
}
