<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\ReplyToCustomerMessage;
use App\Models\ChatConversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AiChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class ChatController extends Controller
{
    public function send(Request $request, AiChatService $ai)
    {
        $text = $request->input('message');
        if ($text === null || (is_string($text) && trim($text) === '')) {
            return response()->json(['error' => 'Nội dung tin nhắn không được để trống'], 400);
        }
        $request->validate(['message' => 'required|string|max:1000']);
        $admin = User::where('role', 'admin')->orderBy('id')->first();
        if (! $admin) {
            return response()->json(['error' => 'Kênh hỗ trợ chưa sẵn sàng. Vui lòng thử lại sau.'], 503);
        }
        $message = DB::transaction(function () use ($text, $admin, $ai) {
            User::whereKey(Auth::id())->lockForUpdate()->first();
            $conversation = ChatConversation::firstOrCreate(['user_id' => Auth::id()]);

            return Message::create([
                'sender_id' => Auth::id(),
                'receiver_id' => $admin->id,
                'content' => trim($text),
                'is_read' => false,
                'is_ai' => false,
                'ai_status' => $ai->available() && ! $conversation->human_mode && Auth::user()->role !== 'admin' ? 'pending' : null,
            ]);
        });
        if ($message->ai_status === 'pending') {
            try {
                Bus::dispatch(new ReplyToCustomerMessage($message->id));
            } catch (Throwable $error) {
                // A queue outage must not lose a successfully saved customer message.
                $message->update(['ai_status' => 'failed']);
            }
        }

        return response()->json($message);
    }

    public function getMessages()
    {
        return response()->json(Message::conversation(Auth::id())
            ->with(['sender:id,name,role', 'receiver:id,name,role'])
            ->latest('id')->limit(100)->get()->reverse()->values());
    }

    public function status(AiChatService $ai)
    {
        Message::where('sender_id', Auth::id())->where('ai_status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(2))->update(['ai_status' => 'failed']);
        $human = ChatConversation::where('user_id', Auth::id())->where('human_mode', true)->exists();
        $latest = Message::conversation(Auth::id())->where('sender_id', Auth::id())->latest('id')->first();
        $available = $ai->available();

        return response()->json([
            'ai_available' => $available,
            'mode' => $human ? 'human' : 'ai',
            'pending' => $available && ! $human && $latest?->ai_status === 'pending',
            'notice' => $human ? 'Đã chọn hỗ trợ từ nhân viên. Nhân viên sẽ phản hồi khi có thể.'
                : (! $available ? 'AI hiện chưa sẵn sàng. Bạn vẫn có thể gửi tin nhắn cho nhân viên.'
                    : (in_array($latest?->ai_status, ['failed', 'skipped']) ? 'AI chưa thể phản hồi tin nhắn này. Tin nhắn đã được lưu; bạn có thể chọn Gặp nhân viên.' : null)),
        ]);
    }

    public function mode(Request $request, AiChatService $ai)
    {
        $request->validate(['mode' => 'required|in:ai,human']);
        DB::transaction(function () use ($request) {
            User::whereKey(Auth::id())->lockForUpdate()->first();
            ChatConversation::updateOrCreate(['user_id' => Auth::id()], ['human_mode' => $request->input('mode') === 'human']);
            Message::where('sender_id', Auth::id())->where('ai_status', 'pending')->update(['ai_status' => 'skipped']);
        });

        return $this->status($ai);
    }
}
