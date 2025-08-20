<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Monk Magic</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body style="margin:0px"
    class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex items-center lg:justify-center min-h-screen flex-col m-0">
    <header class="w-full bg-white shadow-sm dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="/" class="text-2xl font-bold text-gray-900 dark:text-white">
                        Monk Magic
                    </a>
                </div>

                <!-- Navigation Links -->
                <nav class="hidden md:flex space-x-8">
                    <a href="/"
                        class="text-gray-700 dark:text-gray-300 hover:text-black dark:hover:text-white font-medium">
                        Home
                    </a>
                    <a href="#products"
                        class="text-gray-700 dark:text-gray-300 hover:text-black dark:hover:text-white font-medium">
                        Products
                    </a>
                    <a href="/about"
                        class="text-gray-700 dark:text-gray-300 hover:text-black dark:hover:text-white font-medium">
                        About
                    </a>
                </nav>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button type="button"
                        class="text-gray-700 dark:text-gray-300 hover:text-black dark:hover:text-white focus:outline-none">
                        <!-- Heroicon menu -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>
    <div
        class=" flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
        <main class="flex-grow max-w-3xl mx-auto px-6 py-12">
            <h1 class="text-3xl font-bold mb-6">About Us</h1>
            <p>
                We’re on a sweet revolution – minus the Sugar!
                💡 "Why should healthy food taste boring? I wanted to change that!"

                A serial entrepreneur with a passion for health and wellness, Nikunj saw the massive gap in India’s
                dessert industry—where "healthy" often meant compromising on taste.

                He set out to create a brand where indulgence meets nutrition, ensuring that every scoop, sip, and bite
                of MonkMagic is as satisfying as its sugar-loaded counterparts, minus the guilt.
            </p>
        </main>

    </div>
    <footer class=" py-8">
        <div class="container mx-auto text-center">
            <!-- Row 1: Logo -->
            <div class="mb-4">
                <img src="https://www.monkmagic.in/web/image/website/1/logo/MonkMagic%20-%20Zero%20Sugar%20Spreads%20%26%20Monk%20Fruit%20Sweetener?unique=c6d7da7"
                    alt="Monk Magic Logo" class="mx-auto h-12">
            </div>

            <!-- Row 2: Policy Links -->
            <div class="flex justify-center flex-wrap gap-4 text-sm text-gray-600">
                <a href="/privacy-policy" class="hover:underline">Privacy Policy</a>
                <a href="/terms-and-conditions" class="hover:underline">Terms and Conditions</a>
            </div>
        </div>
    </footer>
</body>

</html>
