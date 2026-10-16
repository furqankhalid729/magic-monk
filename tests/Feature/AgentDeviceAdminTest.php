<?php

namespace Tests\Feature;

use App\Filament\Resources\AgentResource\Pages\EditAgent;
use App\Filament\Resources\AgentResource\Pages\ListAgents;
use App\Filament\Resources\AgentResource\RelationManagers\LoginSessionsRelationManager;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgentDeviceAdminTest extends TestCase
{
    use RefreshDatabase;

    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->agent = Agent::create([
            'name' => 'Test Agent',
            'whatsapp_number' => '9154545485',
            'pan_number' => 'ABCDE1234F',
            'city' => 'Mumbai',
        ]);

        $this->postJson('/api/agent/login', [
            'phoneNumber' => '9154545485',
            'password' => '9154545485',
            'deviceInfo' => [
                'platform' => 'android',
                'brand' => 'Xiaomi',
                'manufacturer' => 'Xiaomi',
                'modelName' => '21081111RG',
                'deviceId' => '411df960d215296a',
            ],
        ])->assertOk();
    }

    public function test_edit_page_shows_registered_device_and_saves_bypass_toggle(): void
    {
        Livewire::test(EditAgent::class, ['record' => $this->agent->getKey()])
            ->assertSee('Xiaomi 21081111RG')
            ->assertSee('411df960d215296a')
            ->fillForm(['bypass_device_check' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($this->agent->fresh()->bypass_device_check);
    }

    public function test_reset_device_action_clears_the_registered_device(): void
    {
        Livewire::test(EditAgent::class, ['record' => $this->agent->getKey()])
            ->callAction('resetDevice')
            ->assertNotified('Device reset');

        $this->assertNull($this->agent->fresh()->device_id);
    }

    public function test_agents_list_renders_with_bypass_column(): void
    {
        Livewire::test(ListAgents::class)
            ->assertSuccessful()
            ->assertTableColumnExists('bypass_device_check');
    }

    public function test_login_sessions_relation_manager_lists_sessions(): void
    {
        Livewire::test(LoginSessionsRelationManager::class, [
            'ownerRecord' => $this->agent,
            'pageClass' => EditAgent::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($this->agent->loginSessions)
            ->assertSee('Matched')
            ->assertSee('No logout');
    }
}
