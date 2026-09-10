<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_not_be_updated_from_profile(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertStatus(405);

        $user->refresh();

        $this->assertNotSame('Test User', $user->name);
        $this->assertNotSame('test@example.com', $user->email);
    }

    public function test_user_password_can_be_updated_from_profile_flow(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertTrue(auth()->attempt([
            'email' => $user->email,
            'password' => 'new-password',
        ]));
    }

    public function test_user_can_not_delete_their_account_from_profile(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ])
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
