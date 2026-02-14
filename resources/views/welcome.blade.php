@extends('layouts.app')

@push('styles')
    <style>
        @keyframes hero-rise {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes hero-float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-12px);
            }
        }

        .hero-content {
            opacity: 0;
            transform: translateY(40px);
            animation: hero-rise 0.8s ease-out forwards;
            animation-delay: 0.2s;
        }

        .hero-accent {
            animation: hero-float 6s ease-in-out infinite;
        }
    </style>
@endpush

@section('title', 'MonkMagic | Snack Sane, Act Nuts')

@section('content')
    <div class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-gray-100">
        @include('components.static-nav')

        <section class="hero-banner relative flex min-h-screen items-center justify-center overflow-hidden text-white" style="background-image: url('{{ asset('storage/Monkmagic Cover Pic.png') }}'); background-size: cover; background-position: center;">
            <div class="absolute inset-0 bg-black/55 z-10"></div>
            <div class="relative z-20 w-full max-w-4xl mx-auto px-6 py-24">
                <div class="hero-content max-w-2xl mx-auto space-y-6 text-center">
                    <p class="uppercase tracking-[0.35em] text-sm font-semibold text-white/80">Zero Sugar Indulgence</p>
                    <h1 class="text-4xl md:text-6xl font-bold leading-tight">
                        Snack sane
                        <span class="inline-block bg-yellow-300 text-black px-2 py-1 md:px-4 md:py-2 md:ml-2">Act Nuts</span>
                    </h1>
                    <p class="text-lg md:text-xl text-white/90">
                        MonkMagic makes dessert decisions simple: satisfy cravings with clean ingredients, bold flavours, and no compromise on fun.
                    </p>
                    <a href="{{ url('/instagram-login') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-[#F58529] via-[#DD2A7B] to-[#515BD4] px-6 py-3 text-base font-semibold text-white shadow-lg transition hover:translate-y-1">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.347 3.608 1.322.975.975 1.26 2.242 1.322 3.608.058 1.266.069 1.646.069 4.837s-.012 3.571-.069 4.837c-.062 1.366-.347 2.633-1.322 3.608-.975.975-2.242 1.26-3.608 1.322-1.266.058-1.646.069-4.85.069s-3.584-.012-4.85-.069c-1.366-.062-2.633-.347-3.608-1.322-.975-.975-1.26-2.242-1.322-3.608C2.175 15.584 2.163 15.204 2.163 12s.012-3.571.069-4.837c.062-1.366.347-2.633 1.322-3.608.975-.975 2.242-1.26 3.608-1.322C8.416 2.175 8.796 2.163 12 2.163zm0-2.163C8.741 0 8.332.012 7.052.07 5.773.127 4.638.435 3.678 1.395 2.718 2.355 2.41 3.49 2.353 4.769 2.295 6.049 2.283 6.459 2.283 12s.012 5.951.07 7.231c.057 1.279.365 2.414 1.325 3.374.96.96 2.095 1.268 3.374 1.325 1.28.058 1.689.07 7.231.07s5.951-.012 7.231-.07c1.279-.057 2.414-.365 3.374-1.325.96-.96 1.268-2.095 1.325-3.374.058-1.28.07-1.689.07-7.231s-.012-5.951-.07-7.231c-.057-1.279-.365-2.414-1.325-3.374C21.414.435 20.279.127 19 .07 17.72.012 17.311 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zm0 10.162a3.999 3.999 0 110-7.998 3.999 3.999 0 010 7.998zm6.406-11.845a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z" />
                        </svg>
                        Login with Instagram
                    </a>
                </div>
            </div>
        </section>

        <section class="max-w-3xl mx-auto px-6 py-12 space-y-8">
            <header class="space-y-2">
                <h2 class="text-3xl font-bold">About Us</h2>
                <p class="text-gray-600 dark:text-gray-400 text-lg">We&rsquo;re on a sweet revolution &ndash; minus the sugar.</p>
            </header>

            <div class="space-y-4 text-lg leading-relaxed text-gray-700 dark:text-gray-300">
                <p><span class="font-semibold">&quot;Why should healthy food taste boring? I wanted to change that!&quot;</span></p>
                <p>A serial entrepreneur with a passion for health and wellness, Nikunj saw the massive gap in India&rsquo;s dessert industry&mdash;where &ldquo;healthy&rdquo; often meant compromising on taste.</p>
                <p>MonkMagic Desserts Private Limited was launched in February 2025 with the vision of making zero sugar the new normal.</p>
                <p>He set out to create a brand where indulgence meets nutrition, ensuring that every scoop, sip, and bite of MonkMagic is as satisfying as its sugar-loaded counterparts, minus the guilt.</p>
            </div>

            <section class="space-y-3">
                <h3 class="text-2xl font-semibold">Company Details</h3>
                <p class="text-gray-700 dark:text-gray-300"><strong>MonkMagic Desserts Private Limited</strong></p>
                <p class="text-gray-700 dark:text-gray-300"><strong>GSTN:</strong> 27AASCM9705H1ZW</p>
            </section>

            <section class="space-y-3">
                <h3 class="text-2xl font-semibold">Contact Us</h3>
                <p class="text-gray-700 dark:text-gray-300">
                    <strong>Corporate Office</strong><br>
                    MonkMagic Desserts Private Limited<br>
                    3, Shree Naman Plaza, SV Road, Kandivali West, Mumbai - 400067
                </p>
                <p class="text-gray-700 dark:text-gray-300">
                    <strong>Email:</strong> hello@monkmagic.in<br>
                    <strong>Phone:</strong> +91 9867806668
                </p>
            </section>
        </section>

        @include('components.static-footer')
    </div>
@endsection
