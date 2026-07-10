<?php

namespace Tests\Feature\Api;

use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortlinkControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_should_get_all_shortlinks()
    {
        ShortLink::factory(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/shortlink');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_should_create_shortlink()
    {
        $response = $this->postJson('/api/shortlink', [
            'url' => 'https://google.com',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'url',
                'short_url',
                'created_at',
            ],
        ]);
    }

    public function test_should_get_shortlink()
    {
        $shortLink = ShortLink::factory()->create(['is_active' => true]);

        $response = $this->getJson('/api/shortlink/' . $shortLink->short_url);

        $response->assertRedirect($shortLink->url);
        $response->assertStatus(302);
        $this->assertEquals($shortLink->clicks + 1, $shortLink->refresh()->clicks);
    }

    public function test_should_not_access_a_deactivated_shortlink()
    {
        $shortLink = ShortLink::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/shortlink/' . $shortLink->short_url);

        $response->assertNotFound();
    }

    public function test_should_delete_shortlink()
    {
        $shortLink = ShortLink::factory()->create(['is_active' => true]);

        $response = $this->deleteJson('/api/shortlink/' . $shortLink->id);

        $response->assertNoContent();
        $this->assertFalse((bool)$shortLink->fresh()->is_active);
    }
}
