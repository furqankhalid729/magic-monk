<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="HandheldFriendly" content="true">
    <meta name="MobileOptimized" content="width">

    <title>@yield('title', 'My Website')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- Global CSS --}}
     @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles') {{-- Page-specific CSS --}}
    
</head>

<body>

    {{-- Main Content --}}
    <main class="main-content">
        @yield('content')
    </main>


    @stack('scripts') {{-- Page-specific JS --}}
</body>
</html>
