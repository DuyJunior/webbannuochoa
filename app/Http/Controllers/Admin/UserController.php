<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Hiển thị danh sách người dùng.
     */
    public function index(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'role' => ['nullable', 'in:admin,customer,livestream_staff']]);
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim($request->string('search')->toString());
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->when($request->filled('role'), fn ($query) => $request->input('role') === 'customer'
                ? $query->whereIn('role', ['user', 'customer']) : $query->where('role', $request->input('role')))
            ->orderByDesc('id')->paginate(15)->withQueryString();
        $roleCounts = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        return view('admin.users.index', compact('users', 'roleCounts'));
    }

    /**
     * Hiển thị form tạo người dùng mới.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Lưu người dùng mới vào CSDL.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user,customer,livestream_staff',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);
        $user->markEmailAsVerified();

        return redirect()->route('admin.users.index')->with('success', 'Thêm người dùng thành công.');
    }

    /**
     * Hiển thị chi tiết người dùng.
     */
    public function show(User $user): View
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Hiển thị form chỉnh sửa người dùng.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Cập nhật thông tin người dùng.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,user,customer,livestream_staff',
            'password' => 'nullable|string|min:6',
        ]);

        if ($user->id === $request->user()->id && $request->input('role') !== 'admin') {
            throw ValidationException::withMessages([
                'role' => 'Bạn không thể thay đổi quyền quản trị của tài khoản đang đăng nhập.',
            ]);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Cập nhật người dùng thành công.');
    }

    /**
     * Xóa người dùng.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'Không thể tự xóa tài khoản đang đăng nhập.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Xóa người dùng thành công.');
    }
}
