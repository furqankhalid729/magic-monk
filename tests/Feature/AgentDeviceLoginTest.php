<?php

namespace Tests\Feature;

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentDeviceLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '9154545485';

    private function agent(array $overrides = []): Agent
    {
        return Agent::create(array_merge([
            'name' => 'Test Agent',
            'whatsapp_number' => self::PHONE,
            'pan_number' => 'ABCDE1234F',
            'city' => 'Mumbai',
        ], $overrides));
    }

    private function deviceInfo(array $overrides = []): array
    {
        return array_merge([
            'platform' => 'android',
            'osVersion' => '14',
            'brand' => 'Xiaomi',
            'manufacturer' => 'Xiaomi',
            'modelName' => '21081111RG',
            'deviceId' => '411df960d215296a',
            'totalMemory' => 7866048512,
            'supportedCpuArchitectures' => ['arm64-v8a', 'armeabi-v7a', 'armeabi'],
            'deviceYearClass' => 2016,
            'isPhysicalDevice' => true,
            'appVersion' => '2.33.22',
            'timezone' => 'Asia/Karachi',
            'network' => ['type' => 'WIFI', 'localIp' => '192.168.100.6'],
            'publicIp' => '39.58.251.99',
        ], $overrides);
    }

    private function login(?array $deviceInfo = null, string $phone = self::PHONE, ?string $password = null)
    {
        $payload = ['phoneNumber' => $phone, 'password' => $password ?? $phone];

        if ($deviceInfo !== null) {
            $payload['deviceInfo'] = $deviceInfo;
        }

        return $this->postJson('/api/agent/login', $payload);
    }

    public function test_bypass_defaults_to_true_for_new_agents(): void
    {
        $this->assertTrue($this->agent()->fresh()->bypass_device_check);
    }

    public function test_first_login_registers_the_device_and_creates_a_session(): void
    {
        $agent = $this->agent();

        $response = $this->login($this->deviceInfo());

        $response->assertOk()->assertJson(['status' => true])->assertJsonStructure(['sessionId']);

        $agent->refresh();
        $this->assertSame('411df960d215296a', $agent->device_id);
        $this->assertSame('21081111RG', $agent->device_info['modelName']);
        $this->assertNotNull($agent->device_registered_at);

        $session = $agent->loginSessions()->first();
        $this->assertSame($response->json('sessionId'), $session->id);
        $this->assertTrue($session->device_matched);
        $this->assertFalse($session->blocked);
        $this->assertNull($session->logout_at);
        $this->assertSame('411df960d215296a', $session->device_id);
        $this->assertSame('39.58.251.99', $session->public_ip);
        $this->assertSame('2.33.22', $session->app_version);
        $this->assertNotNull($session->login_date);
    }

    public function test_same_device_matches_even_when_volatile_fields_change(): void
    {
        $agent = $this->agent(['bypass_device_check' => false]);
        $this->login($this->deviceInfo())->assertOk();

        $this->login($this->deviceInfo([
            'osVersion' => '15',
            'appVersion' => '3.0.0',
            'publicIp' => '1.2.3.4',
            'network' => ['type' => 'CELLULAR'],
        ]))->assertOk();

        $latest = $agent->loginSessions()->latest('id')->first();
        $this->assertTrue($latest->device_matched);
        $this->assertSame(2, $agent->loginSessions()->count());
    }

    public function test_different_device_is_blocked_when_bypass_is_off(): void
    {
        $agent = $this->agent(['bypass_device_check' => false]);
        $this->login($this->deviceInfo())->assertOk();

        $response = $this->login($this->deviceInfo([
            'deviceId' => 'ffffffffffffffff',
            'brand' => 'Samsung',
            'manufacturer' => 'Samsung',
            'modelName' => 'SM-A525F',
        ]));

        $response->assertForbidden()->assertJson([
            'status' => false,
            'code' => 'DEVICE_MISMATCH',
        ]);

        $agent->refresh();
        $this->assertSame('411df960d215296a', $agent->device_id);

        $blocked = $agent->loginSessions()->latest('id')->first();
        $this->assertTrue($blocked->blocked);
        $this->assertFalse($blocked->device_matched);
        $this->assertContains('deviceId', $blocked->mismatch_fields['hard']);
        $this->assertContains('modelName', $blocked->mismatch_fields['hard']);
    }

    public function test_different_device_is_allowed_but_flagged_when_bypass_is_on(): void
    {
        $agent = $this->agent();
        $this->login($this->deviceInfo())->assertOk();

        $this->login($this->deviceInfo(['deviceId' => 'ffffffffffffffff']))
            ->assertOk()
            ->assertJson(['status' => true]);

        $session = $agent->loginSessions()->latest('id')->first();
        $this->assertFalse($session->blocked);
        $this->assertFalse($session->device_matched);
        $this->assertSame(['deviceId'], $session->mismatch_fields['hard']);
        $this->assertSame('411df960d215296a', $agent->fresh()->device_id);
    }

    public function test_soft_field_differences_are_recorded_but_never_block(): void
    {
        $agent = $this->agent(['bypass_device_check' => false]);
        $this->login($this->deviceInfo())->assertOk();

        $this->login($this->deviceInfo(['totalMemory' => 4000000000, 'timezone' => 'Asia/Kolkata']))
            ->assertOk();

        $session = $agent->loginSessions()->latest('id')->first();
        $this->assertTrue($session->device_matched);
        $this->assertFalse($session->blocked);
        $this->assertSame([], $session->mismatch_fields['hard']);
        $this->assertEqualsCanonicalizing(['totalMemory', 'timezone'], $session->mismatch_fields['soft']);
    }

    public function test_missing_device_info_is_allowed_when_bypass_is_on(): void
    {
        $agent = $this->agent();

        $this->login()->assertOk()->assertJson(['status' => true]);

        $session = $agent->loginSessions()->first();
        $this->assertNull($session->device_id);
        $this->assertNull($session->device_matched);
        $this->assertNull($agent->fresh()->device_id);
    }

    public function test_missing_device_info_is_rejected_when_bypass_is_off(): void
    {
        $agent = $this->agent(['bypass_device_check' => false]);

        $this->login()->assertForbidden()->assertJson([
            'status' => false,
            'code' => 'DEVICE_INFO_REQUIRED',
        ]);

        $this->assertNull($agent->fresh()->device_id);
        $this->assertTrue($agent->loginSessions()->firstOrFail()->blocked);
    }

    public function test_device_is_not_evaluated_when_credentials_are_wrong(): void
    {
        $agent = $this->agent(['bypass_device_check' => false]);

        $this->login($this->deviceInfo(), self::PHONE, 'wrong-password')
            ->assertOk()
            ->assertJson(['status' => false, 'message' => 'Invalid credentials.']);

        $this->assertNull($agent->fresh()->device_id);
        $this->assertSame(0, $agent->loginSessions()->count());
    }

    public function test_reset_allows_a_new_device_to_bind(): void
    {
        $agent = $this->agent(['bypass_device_check' => false]);
        $this->login($this->deviceInfo())->assertOk();

        $agent->resetDevice();
        $this->assertNull($agent->fresh()->device_id);
        $this->assertNull($agent->fresh()->device_info);
        $this->assertNull($agent->fresh()->device_registered_at);

        $this->login($this->deviceInfo(['deviceId' => 'aaaaaaaaaaaaaaaa', 'modelName' => 'Pixel 8']))
            ->assertOk();

        $this->assertSame('aaaaaaaaaaaaaaaa', $agent->fresh()->device_id);
    }

    public function test_logout_closes_the_latest_open_session(): void
    {
        $agent = $this->agent();
        $this->login($this->deviceInfo())->assertOk();
        $sessionId = $agent->loginSessions()->firstOrFail()->id;

        $this->postJson('/api/agent/logout', [
            'phoneNumber' => self::PHONE,
            'deviceId' => '411df960d215296a',
        ])->assertOk()->assertJson(['status' => true]);

        $this->assertNotNull($agent->loginSessions()->findOrFail($sessionId)->logout_at);
    }

    public function test_logout_only_closes_the_latest_open_session_and_is_idempotent(): void
    {
        $agent = $this->agent();
        $this->login($this->deviceInfo())->assertOk();
        $this->login($this->deviceInfo())->assertOk();

        $this->postJson('/api/agent/logout', ['phoneNumber' => self::PHONE])->assertOk();

        $sessions = $agent->loginSessions()->orderBy('id')->get();
        $this->assertNull($sessions[0]->logout_at);
        $this->assertNotNull($sessions[1]->logout_at);

        $closedAt = $sessions[1]->logout_at;
        $this->postJson('/api/agent/logout', ['phoneNumber' => self::PHONE])->assertOk();
        $this->assertNotNull($sessions[0]->fresh()->logout_at);
        $this->assertEquals($closedAt, $sessions[1]->fresh()->logout_at);

        $this->postJson('/api/agent/logout', ['phoneNumber' => self::PHONE])
            ->assertOk()
            ->assertJson(['status' => true]);
    }

    public function test_logout_ignores_blocked_attempts(): void
    {
        $agent = $this->agent(['bypass_device_check' => false]);
        $this->login($this->deviceInfo())->assertOk();
        $this->login($this->deviceInfo(['deviceId' => 'ffffffffffffffff']))->assertForbidden();

        $this->postJson('/api/agent/logout', ['phoneNumber' => self::PHONE])->assertOk();

        $sessions = $agent->loginSessions()->orderBy('id')->get();
        $this->assertNotNull($sessions[0]->logout_at);
        $this->assertNull($sessions[1]->logout_at);
    }

    public function test_logout_for_unknown_agent_returns_404(): void
    {
        $this->postJson('/api/agent/logout', ['phoneNumber' => '0000000000'])->assertNotFound();
    }
}
