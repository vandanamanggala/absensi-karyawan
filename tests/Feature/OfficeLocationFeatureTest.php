<?php

namespace Tests\Feature;

use App\Models\OfficeLocation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeLocationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_toggle_location_requirement(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $response = $this->actingAs($owner)->post(route('owner.office.toggle'), [
            'location_verification_enabled' => '0',
        ]);

        $response->assertRedirect();
        $this->assertEquals('0', Setting::get('location_verification_enabled'));

        $response = $this->actingAs($owner)->post(route('owner.office.toggle'), [
            'location_verification_enabled' => '1',
        ]);

        $response->assertRedirect();
        $this->assertEquals('1', Setting::get('location_verification_enabled'));
    }

    public function test_api_active_locations_returns_setting_status(): void
    {
        $user = User::factory()->create();
        Setting::set('location_verification_enabled', '0');

        $response = $this->actingAs($user)->getJson(route('api.office.locations'));

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'is_location_required' => false,
            ]);
    }
}
