<x-filament::widget>
    <x-filament::card>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="bg-white shadow rounded-xl p-4">
                <h2 class="text-sm font-medium text-gray-500">Total Agents</h2>
                <p class="text-2xl font-bold text-gray-800">{{ $totalAgents }}</p>
            </div>

            <div class="bg-white shadow rounded-xl p-4">
                <h2 class="text-sm font-medium text-gray-500">Active Agents</h2>
                <p class="text-2xl font-bold text-green-600">{{ $activeAgents }}</p>
            </div>

            <div class="bg-white shadow rounded-xl p-4">
                <h2 class="text-sm font-medium text-gray-500">Total Locations</h2>
                <p class="text-2xl font-bold text-blue-600">{{ $totalLocations }}</p>
            </div>

            <div class="bg-white shadow rounded-xl p-4">
                <h2 class="text-sm font-medium text-gray-500">Today Picked</h2>
                <p class="text-2xl font-bold text-purple-600">{{ $todayPicked }}</p>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
