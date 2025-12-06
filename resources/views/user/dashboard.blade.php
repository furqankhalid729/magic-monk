@extends('layouts.app')

@section('title', 'Home Page')

@section('content')
    <div class="max-w-[600px] mx-auto mt-10 relative bg-white shadow-md rounded-xl p-8 border border-gray-100">

        <h2 class="text-2xl font-semibold text-gray-800 mb-6">User Information</h2>

        <form action="{{ route('user.info.store') }}" method="POST" class="space-y-5" x-data="{ step: 1 }">
            @csrf

            <!-- Hidden fields for full_name and email -->
            <input type="hidden" name="full_name" value="{{ old('full_name', auth()->user()->name) }}">
            <input type="hidden" name="email" value="{{ old('email', auth()->user()->email) }}">

            <!-- Step 1: Phone, Birthdate, Gender, Terms -->
            <div x-show="step === 1" x-transition>
                <!-- Phone -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Phone number</label>
                    <input onchange="updateField('phone_number', this.value)" type="text" name="phone_number"
                        value="{{ old('phone_number', auth()->user()->phone_number) }}"
                        class="w-full px-4 py-2 border rounded-lg bg-white text-gray-700">
                </div>

                <!-- Birthdate -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Birthdate</label>
                    <input onchange="updateField('date_of_birth', this.value)" type="date" name="date_of_birth"
                        value="{{ old('date_of_birth', auth()->user()->date_of_birth) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    @error('date_of_birth')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Gender -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Gender</label>
                    <select onchange="updateField('gender', this.value)" name="gender"
                        class="w-full px-4 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="Female" {{ old('gender', auth()->user()->gender) == 'Female' ? 'selected' : '' }}>
                            Female</option>
                        <option value="Male" {{ old('gender', auth()->user()->gender) == 'Male' ? 'selected' : '' }}>Male
                        </option>
                        <option value="Other" {{ old('gender', auth()->user()->gender) == 'Other' ? 'selected' : '' }}>
                            Other</option>
                    </select>
                    @error('gender')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Terms Checkbox -->
                <div class="flex items-center space-x-3 mt-3">
                    <input type="checkbox" name="terms" id="terms" value="1" checked
                        class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" required>
                    <label for="terms" class="text-gray-700">I agree to the terms and conditions</label>
                </div>
                @error('terms')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror

                <!-- Next Button -->
                <button type="button" @click="step = 2"
                    class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition mt-4">
                    Next
                </button>
            </div>

            <!-- Step 2: Address (Building, City, State) -->
            <div x-show="step === 2" x-transition x-data="addressAutocomplete()">
                <label class="block text-gray-700 font-medium mb-1">Building Name</label>
                <input type="text" x-model="buildingQuery" @input="filterBuildings()" name="building_name"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="Type building name...">

                <input type="hidden" name="city" x-model="city">
                <input type="hidden" name="state" x-model="state">
                <input type="hidden" name="sub_locality" x-model="sub_locality">
                <ul x-show="filteredBuildings.length > 0"
                    class="absolute z-10 w-[90%] bg-white border rounded-lg mt-1 max-h-60 overflow-auto">

                    <template x-for="building in filteredBuildings" :key="building.id">
                        <li @click="selectBuilding(building)" class="px-4 py-2 cursor-pointer hover:bg-blue-100">
                            <span x-text="building.label"></span>
                        </li>
                    </template>

                </ul>

                <label class="block text-gray-700 font-medium mb-1 mt-4">Address</label>
                <input type="text" x-model="address" name="address" placeholder="Floor, building number"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <div class="flex justify-between mt-6">
                    <button type="button" @click="step = 1"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">Back</button>
                    <button type="submit"
                        class="px-4 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">Submit</button>
                </div>
            </div>

        </form>
    </div>

    <script>
        console.log(@json($buildings));

        function updateField(field, value) {
            fetch("{{ route('user.info.update-field') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        field,
                        value
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) console.log(data.success);
                })
                .catch(err => console.error(err));
        }

        function addressAutocomplete() {
            return {
                buildingQuery: '{{ auth()->user()->building_name ?? '' }}',
                city: '{{ auth()->user()->city ?? '' }}',
                state: '{{ auth()->user()->state ?? '' }}',
                sub_locality: '{{ auth()->user()->sub_locality ?? '' }}',
                address: '{{ auth()->user()->address ?? '' }}',
                buildings: @json($buildings),
                filteredBuildings: [],
                filterBuildings() {
                    this.filteredBuildings = this.buildings
                        .filter(b => b.building_name.toLowerCase().includes(this.buildingQuery.toLowerCase()))
                        .map(b => ({
                            ...b,
                            label: `${b.building_name} (${b.sub_locality ?? 'NA'}, ${b.city ?? 'NA'})`
                        }));
                },
                selectBuilding(building) {
                    this.buildingQuery = building.building_name;
                    this.city = building.city || '';
                    this.state = building.state || '';
                    this.sub_locality = building.sub_locality || '';
                    this.filteredBuildings = [];

                    // Send to backend live update
                    updateField('building_name', this.buildingQuery);
                    updateField('city', this.city);
                    updateField('state', this.state);
                    updateField('sub_locality', this.sub_locality);
                }
            }
        }
    </script>

    <script src="//unpkg.com/alpinejs" defer></script>
@endsection
