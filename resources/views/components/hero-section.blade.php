<script src="https://cdn.tailwindcss.com"></script>
<section class="relative w-full h-screen bg-cover bg-center flex items-center"
    style="background-image: url('{{ $backgroundImage }}');">
    <!-- Dark overlay for readability -->
    <div class="absolute inset-0 bg-black/40"></div>

    <div
        class="relative z-10 w-full px-6 lg:px-20 flex flex-col lg:flex-row justify-between items-center lg:items-start">
        <!-- Left Content -->
        <div class="text-center lg:text-left max-w-xl space-y-4">
            <h1 class="text-white text-4xl lg:text-5xl font-bold leading-tight">
                {{ $title }}
            </h1>
            <p class="text-white text-lg">{{ $subtitle }}</p>
            <a href="{{ $buttonLink }}"
                class="inline-block mt-4 px-6 py-3 bg-white text-black font-medium rounded-full shadow hover:bg-gray-100 transition">
                {{ $buttonText }}
            </a>
        </div>

        <!-- Right Content -->
        <div class="mt-6 lg:mt-0 text-white text-center lg:text-right text-lg max-w-xs">
            {{ $rightText }}
        </div>
    </div>
</section>
