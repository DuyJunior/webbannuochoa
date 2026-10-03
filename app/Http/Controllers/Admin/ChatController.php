<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Lấy danh sách những User đã từng nhắn tin với Admin hoặc tìm kiếm khách hàng
     */
    public function getUsers(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'mode' => ['nullable', 'string', 'in:recent,all'],
        ]);
        $adminId = Auth::id();

        // 1. Tìm kiếm chủ động theo từ khóa (tên, email, sđt)
        if ($request->filled('search')) {
            $keyword = trim($request->search);

            return User::where('id', '!=', $adminId)
                ->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                })
                ->select('id', 'name', 'email')
                ->limit(20)
                ->get();
        }

        // 2. Chế độ lấy tất cả khách hàng để chủ động nhắn tin
        if ($request->query('mode') === 'all') {
            return User::where('id', '!=', $adminId)
                ->where('role', '!=', 'admin')
                ->select('id', 'name', 'email')
                ->latest()
                ->limit(50)
                ->get();
        }

        // Conversations are shared by the support team, including replies from AI.
        $adminIds = User::where('role', 'admin')->pluck('id')->all();
        $userIds = Message::where(function ($query) use ($adminIds) {
                $query->whereIn('receiver_id', $adminIds)->orWhereIn('sender_id', $adminIds);
            })
            ->orderByDesc('id')
            ->get(['sender_id', 'receiver_id'])
            ->map(fn ($message) => in_array($message->sender_id, $adminIds)
                ? $message->receiver_id : $message->sender_id)
            ->unique()
            ->toArray();

        // Lấy thông tin chi tiết các User đó
        return User::whereIn('id', $userIds)
            ->whereNotIn('id', $adminIds)
            ->select('id', 'name', 'email')
            ->get()->sortBy(fn ($user) => array_search($user->id, $userIds))->values();
    }

    /**
     * Tìm kiếm khách hàng để Admin chủ động nhắn tin
     */
    public function searchCustomers(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $adminId = Auth::id();
        $query = trim($request->get('q', ''));

        $users = User::where('id', '!=', $adminId)
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->select('id', 'name', 'email')
            ->limit(20)
            ->get();

        return response()->json($users);
    }

    /**
     * Lấy lịch sử tin nhắn của một User cụ thể
     */
    public function getMessages($userId)
    {
        return Message::conversation((int) $userId)->with('sender:id,name,role')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Admin gửi tin nhắn phản hồi
     */
    public function send(Request $request)
    {
        // Kiểm tra dữ liệu đầu vào
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
        ]);

        $message = DB::transaction(function () use ($request) {
            User::whereKey($request->user_id)->lockForUpdate()->first();
            ChatConversation::updateOrCreate(['user_id' => $request->user_id], ['human_mode' => true]);
            Message::where('sender_id', $request->user_id)->where('ai_status', 'pending')->update(['ai_status' => 'skipped']);

            return Message::create([
                'sender_id' => Auth::id(),
                'receiver_id' => $request->user_id,
                'content' => $request->message,
                'is_read' => true,
            ]);
        });

        return response()->json($message);
    }
}
