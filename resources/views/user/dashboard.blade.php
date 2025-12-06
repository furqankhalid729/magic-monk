@extends('layouts.app')

@section('title', 'Home Page')

@section('content')
<div class="max-w-[600px] mx-auto mt-10 bg-white shadow-md rounded-xl p-8 border border-gray-100">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">User Information</h2>

    <form action="{{ route('user.info.store') }}" method="POST" class="space-y-5">
        @csrf

        <!-- Full Name (readonly) -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Full Name</label>
            <input type="text" name="full_name" value="{{ old('full_name', auth()->user()->name) }}" readonly
                class="w-full px-4 py-2 border rounded-lg bg-gray-100 cursor-not-allowed text-gray-700 focus:outline-none">
        </div>

        <!-- Email (readonly) -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Email Address</label>
            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" readonly
                class="w-full px-4 py-2 border rounded-lg bg-gray-100 cursor-not-allowed text-gray-700 focus:outline-none">
        </div>

        <!-- Birthdate -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Birthdate</label>
            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', auth()->user()->date_of_birth) }}"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            @error('date_of_birth')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Gender -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Gender</label>
            <select name="gender"
                class="w-full px-4 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="Female" {{ old('gender', auth()->user()->gender) == 'Female' ? 'selected' : '' }}>Female
                </option>
                <option value="Male" {{ old('gender', auth()->user()->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Other" {{ old('gender', auth()->user()->gender) == 'Other' ? 'selected' : '' }}>Other
                </option>
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

        <!-- Submit Button -->
        <button type="submit"
            class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
            Submit Information
        </button>
    </form>
</div>
@endsection
