<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_combines_search_with_customer_roles_and_keeps_total_counts(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Lan quản trị']);
        $customer = User::factory()->create(['role' => 'user', 'name' => 'Lan Anh']);
        $legacy = User::factory()->create(['role' => 'customer', 'name' => 'Lan Hương']);
        User::factory()->create(['role' => 'livestream_staff', 'name' => 'Lan livestream']);
        User::factory()->create(['role' => 'user', 'name' => 'Minh']);
        $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Lan', 'role' => 'customer']))
            ->assertOk()->assertViewHas('users', fn ($items) => $items->modelKeys() === [$legacy->id, $customer->id])
            ->assertViewHas('roleCounts', fn ($counts) => $counts->sum() === 5);
    }

    public function test_directory_rejects_invalid_filter_input(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson('/admin/users?search[]=name')->assertUnprocessable()->assertJsonValidationErrors('search');
        $this->getJson('/admin/users?role=owner')->assertUnprocessable()->assertJsonValidationErrors('role');
    }

    public function test_livestream_staff_cannot_use_customer_directory(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'livestream_staff']))->get('/admin/users')->assertRedirect(route('home'));
    }
}
