<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\DB;

class AgentDeviceService
{
    /**
     * Fields that must equal the registered device. A difference here means
     * a different physical device.
     */
    public const HARD_FIELDS = ['deviceId', 'platform', 'brand', 'manufacturer', 'modelName'];

    /**
     * Fields that are compared and recorded but never block a login, because
     * they can legitimately change on the same phone.
     */
    public const SOFT_FIELDS = ['totalMemory', 'supportedCpuArchitectures', 'deviceYearClass', 'isPhysicalDevice', 'timezone'];

    /**
     * Compare the incoming device against the one registered on the agent.
     *
     * @return array{matched: bool, mismatch_fields: array{hard: list<string>, soft: list<string>}}
     */
    public function compare(Agent $agent, array $deviceInfo): array
    {
        $registered = $agent->device_info ?? [];

        $hard = $this->differing(self::HARD_FIELDS, $registered, $deviceInfo);
        $soft = $this->differing(self::SOFT_FIELDS, $registered, $deviceInfo);

        return [
            'matched' => $hard === [],
            'mismatch_fields' => ['hard' => $hard, 'soft' => $soft],
        ];
    }

    /**
     * Register the device on the agent if none is registered yet. Locks the
     * agent row so two simultaneous first logins cannot bind different devices.
     *
     * Returns the agent as it stands after the attempt (fresh from the lock).
     */
    public function bindIfUnregistered(Agent $agent, array $deviceInfo): Agent
    {
        return DB::transaction(function () use ($agent, $deviceInfo) {
            $locked = Agent::whereKey($agent->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->device_id === null) {
                $locked->forceFill([
                    'device_id' => $deviceInfo['deviceId'],
                    'device_info' => $deviceInfo,
                    'device_registered_at' => now(),
                ])->save();
            }

            return $locked;
        });
    }

    private function differing(array $fields, array $registered, array $incoming): array
    {
        return array_values(array_filter(
            $fields,
            fn (string $field) => ($registered[$field] ?? null) != ($incoming[$field] ?? null)
        ));
    }
}
