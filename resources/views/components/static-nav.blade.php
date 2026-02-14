<nav class="w-full bg-white dark:bg-[#111] border-b border-gray-200 dark:border-gray-800">
    <div class="max-w-[1300px] mx-auto px-6 py-6 flex items-center justify-between text-sm font-medium">
        <a href="{{ url('/') }}"
            class="flex items-center gap-2 text-gray-900 dark:text-gray-100 text-lg font-semibold">
            {{-- Replace span with an <img> tag if a logo asset becomes available --}}
            <img src="{{ asset('storage/logo.jpg') }}" alt="Sign Up Header" class="max-w-[200px] w-full h-auto">
        </a>

        <div class="flex flex-wrap items-center justify-end gap-6 text-gray-800 dark:text-gray-200">
            <a href="{{ url('/') }}" class="hover:underline">Home</a>
            <a href="{{ url('/') }}#products" class="hover:underline">Products</a>
            <a href="{{ url('/about') }}" class="hover:underline">About</a>
            <a href="{{ url('/privacy-policy') }}" class="hover:underline">Privacy Policy</a>
            <a href="{{ url('/terms-and-conditions') }}" class="hover:underline">Terms &amp; Conditions</a>
            <a href="{{ url('/refund-and-cancellation') }}" class="hover:underline">Refund &amp; Cancellation</a>
        </div>
    </div>
</nav>
