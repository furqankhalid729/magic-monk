@extends('layouts.app')

@push('styles')
    <style type="text/css">
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

            0%,
            100% {
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

        .frame-section {
            background: #fdf3ea;
            position: relative;
        }

        .frame-sticky {
            position: sticky;
            top: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .frame-heading {
            font-family: 'Montserrat', 'Poppins', sans-serif;
        }

        .frame-heading span {
            display: block;
        }

        .frame-heading .highlight {
            display: inline-block;
            background: linear-gradient(90deg, #ff1594, #ff1594 60%, #ffb6e1);
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            box-shadow: 0.35rem 0.35rem 0 #111;
        }

        .frame-window {
            width: 100%;
            overflow: hidden;
        }

        .frame-window::after {
            display: none;
        }

        .frame-track {
            display: flex;
            align-items: center;
            gap: clamp(1.5rem, 4vw, 2rem);
            padding: clamp(1.5rem, 3vw, 2rem) 0;
            width: max-content;
            will-change: transform;
        }

        .frame-item {
            display: flex;
            flex-direction: column;
            flex: 0 0 280px;
            width: 280px;
            overflow: hidden;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .frame-item img {
            display: block;
            width: 100%;
            height: 320px;
            object-fit: cover;
        }

        .frame-card-caption {
            margin-top: 0.75rem;
            padding: 0 1rem 1.25rem;
            text-align: center;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #20151e;
            font-size: 0.875rem;
        }

        .frame-label {
            letter-spacing: 0.4em;
            text-transform: uppercase;
        }

        .frame-note {
            color: #5a4c43;
        }

        @media (max-width: 768px) {
            .frame-window {
                border-radius: 2rem;
            }

            .frame-item {
                flex: 0 0 220px;
                width: 220px;
            }

            .frame-item img {
                height: 260px;
            }
        }

        @media (max-width: 640px) {
            .frame-section {
                min-height: auto !important;
            }

            .frame-sticky {
                position: relative;
                min-height: auto;
            }

            .frame-window {
                overflow-x: auto;
                overflow-y: hidden;
            }

            .frame-track {
                scroll-snap-type: x mandatory;
                overflow-x: visible;
                width: max-content;
                padding-bottom: 2.5rem;
                transform: none !important;
            }

            .frame-item {
                scroll-snap-align: center;
                opacity: 1 !important;
                transform: none !important;
                flex: 0 0 200px;
                width: 200px;
            }

            .frame-item img {
                height: 240px;
            }

            .frame-heading span {
                opacity: 1 !important;
                transform: none !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-content {
                animation: none;
                opacity: 1;
                transform: none;
            }

            .frame-item,
            .frame-heading span {
                opacity: 1 !important;
                transform: none !important;
            }
        }
    </style>
@endpush

@section('title', 'MonkMagic | Snack Sane, Act Nuts')
@section('content')
    <div class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-gray-100">
        @include('components.static-nav')

        <section class="hero-banner relative flex min-h-screen items-center justify-center overflow-hidden text-white"
            style="background-image: url('{{ asset('storage/Monkmagic Cover Pic.png') }}'); background-size: cover; background-position: center;">
            <div class="absolute inset-0 bg-black/55 z-10"></div>
            <div class="relative z-20 w-full max-w-4xl mx-auto px-6 py-24">
                <div class="hero-content max-w-2xl mx-auto space-y-6 text-center">
                    <p class="uppercase tracking-[0.35em] text-sm font-semibold text-white/80">Zero Sugar Indulgence</p>
                    <h1 class="text-4xl md:text-6xl font-bold leading-tight">
                        Snack sane
                        <span class="inline-block bg-yellow-300 text-black px-2 py-1 md:px-4 md:py-2 md:ml-2">Act Nuts</span>
                    </h1>
                    <p class="text-lg md:text-xl text-white/90">
                        MonkMagic makes dessert decisions simple: satisfy cravings with clean ingredients, bold flavours,
                        and no compromise on fun.
                    </p>
                    <a href="{{ url('/instagram-login') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-[#F58529] via-[#DD2A7B] to-[#515BD4] px-6 py-3 text-base font-semibold text-white shadow-lg transition hover:translate-y-1">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                            <path
                                d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.347 3.608 1.322.975.975 1.26 2.242 1.322 3.608.058 1.266.069 1.646.069 4.837s-.012 3.571-.069 4.837c-.062 1.366-.347 2.633-1.322 3.608-.975.975-2.242 1.26-3.608 1.322-1.266.058-1.646.069-4.85.069s-3.584-.012-4.85-.069c-1.366-.062-2.633-.347-3.608-1.322-.975-.975-1.26-2.242-1.322-3.608C2.175 15.584 2.163 15.204 2.163 12s.012-3.571.069-4.837c.062-1.366.347-2.633 1.322-3.608.975-.975 2.242-1.26 3.608-1.322C8.416 2.175 8.796 2.163 12 2.163zm0-2.163C8.741 0 8.332.012 7.052.07 5.773.127 4.638.435 3.678 1.395 2.718 2.355 2.41 3.49 2.353 4.769 2.295 6.049 2.283 6.459 2.283 12s.012 5.951.07 7.231c.057 1.279.365 2.414 1.325 3.374.96.96 2.095 1.268 3.374 1.325 1.28.058 1.689.07 7.231.07s5.951-.012 7.231-.07c1.279-.057 2.414-.365 3.374-1.325.96-.96 1.268-2.095 1.325-3.374.058-1.28.07-1.689.07-7.231s-.012-5.951-.07-7.231c-.057-1.279-.365-2.414-1.325-3.374C21.414.435 20.279.127 19 .07 17.72.012 17.311 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zm0 10.162a3.999 3.999 0 110-7.998 3.999 3.999 0 010 7.998zm6.406-11.845a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z" />
                        </svg>
                        Login with Instagram
                    </a>
                </div>
            </div>
        </section>

        <section class="frame-section js-frame-scroll min-h-[200vh]">
            <div class="frame-sticky py-24 space-y-16">
                <div class="text-center space-y-6 max-w-6xl mx-auto px-6">
                    <p class="frame-label text-sm text-gray-500">Must Be Nuts</p>
                    <div class="space-y-4 frame-heading text-4xl md:text-5xl font-black text-[#111]">
                        <span>Wanted</span>
                        <span class="highlight">For Delicious</span>
                        <span>Crimes</span>
                    </div>
                    <p class="max-w-2xl mx-auto text-lg frame-note">
                        Scroll the case file to watch each flavour slide past the spotlight. Every swipe reveals another
                        accomplice in crunch.
                    </p>
                </div>

                <div class="frame-window">
                    <div class="frame-track">
                        <figure class="frame-item">
                            <img src="{{ asset('storage/Belgian Dark AI.png') }}" alt="Belgian Dark">
                            <figcaption class="frame-card-caption">Belgian Dark</figcaption>
                        </figure>
                        <figure class="frame-item">
                            <img src="{{ asset('storage/Blueberry Cheesecake AI.png') }}" alt="Blueberry Cheesecake">
                            <figcaption class="frame-card-caption">Blueberry Cheesecake</figcaption>
                        </figure>
                        <figure class="frame-item">
                            <img src="{{ asset('storage/Butterscotch AI.png') }}" alt="Butterscotch">
                            <figcaption class="frame-card-caption">Butterscotch</figcaption>
                        </figure>
                        <figure class="frame-item">
                            <img src="{{ asset('storage/Choco Chip AI.png') }}" alt="Choco Chip">
                            <figcaption class="frame-card-caption">Choco Chip</figcaption>
                        </figure>
                        <figure class="frame-item">
                            <img src="{{ asset('storage/Chocolate Mousse AI.png') }}" alt="Chocolate Mousse">
                            <figcaption class="frame-card-caption">Chocolate Mousse</figcaption>
                        </figure>
                        <figure class="frame-item">
                            <img src="{{ asset('storage/Lonavala Walnut Fudge AI.png') }}" alt="Lonavala Walnut Fudge">
                            <figcaption class="frame-card-caption">Lonavala Walnut Fudge</figcaption>
                        </figure>
                        <figure class="frame-item">
                            <img src="{{ asset('storage/Elaichi Rabdi AI.png') }}" alt="Elaichi Rabdi">
                            <figcaption class="frame-card-caption">Elaichi Rabdi</figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <section class="max-w-6xl mx-auto px-6 py-16 space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Video Column -->
                <div class="relative rounded-2xl overflow-hidden shadow-xl aspect-square">
                    <video 
                        id="about-video"
                        class="w-full h-full object-cover"
                        autoplay 
                        muted 
                        loop 
                        playsinline
                        poster="{{ asset('storage/Monkmagic Cover Pic.png') }}"
                    >
                        <source src="{{ asset('storage/WhatsApp Video 2026-02-14 at 12.28.55.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <!-- Sound Toggle Button -->
                    <button 
                        id="sound-toggle"
                        class="absolute bottom-4 right-4 w-10 h-10 flex items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80 transition-colors"
                        aria-label="Toggle sound"
                    >
                        <!-- Muted Icon (visible by default) -->
                        <svg id="icon-muted" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                        </svg>
                        <!-- Unmuted Icon (hidden by default) -->
                        <svg id="icon-unmuted" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        </svg>
                    </button>
                </div>

                <!-- Content Column -->
                <div class="space-y-6">
                    <header class="space-y-2">
                        <h2 class="text-3xl font-bold">About Us</h2>
                        <p class="text-gray-600 dark:text-gray-400 text-lg">We&rsquo;re on a sweet revolution &ndash; minus the
                            sugar.</p>
                    </header>

                    <div class="space-y-4 text-lg leading-relaxed text-gray-700 dark:text-gray-300">
                        <p><span class="font-semibold">&quot;Why should healthy food taste boring? I wanted to change
                                that!&quot;</span></p>
                        <p>A serial entrepreneur with a passion for health and wellness, Nikunj saw the massive gap in India&rsquo;s
                            dessert industry&mdash;where &ldquo;healthy&rdquo; often meant compromising on taste.</p>
                        <p>MonkMagic Desserts Private Limited was launched in February 2025 with the vision of making zero sugar the
                            new normal.</p>
                        <p>He set out to create a brand where indulgence meets nutrition, ensuring that every scoop, sip, and bite
                            of MonkMagic is as satisfying as its sugar-loaded counterparts, minus the guilt.</p>
                    </div>
                </div>
            </div>
        </section>

        @include('components.static-footer')
    </div>

    <script type="module">
        import { animate, scroll, inView } from "https://cdn.jsdelivr.net/npm/motion@latest/+esm";

        // Wait for DOM and images to load
        window.addEventListener('load', () => {
            // Skip on mobile or reduced motion
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const isDesktop = window.matchMedia('(min-width: 641px)').matches;

            if (isDesktop && !prefersReducedMotion) {
                const section = document.querySelector('.js-frame-scroll');
                const track = document.querySelector('.frame-track');
                const frameWindow = document.querySelector('.frame-window');

                if (section && track && frameWindow) {
                    // Calculate the amount to shift
                    const trackWidth = track.scrollWidth;
                    const windowWidth = frameWindow.clientWidth;
                    const maxShift = trackWidth - windowWidth;

                    console.log('Track width:', trackWidth, 'Window width:', windowWidth, 'Max shift:', maxShift);

                    if (maxShift > 0) {
                        scroll(
                            animate(track, {
                                x: [0, -maxShift]
                            }),
                            {
                                target: section,
                                offset: ['start start', 'end end']
                            }
                        );
                    }
                }

                // Animate each card into view with stagger
                const cards = document.querySelectorAll('.frame-item');
                cards.forEach((card, i) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(60px) scale(0.95)';

                    inView(card, () => {
                        animate(card, {
                            opacity: 1,
                            transform: 'translateY(0px) scale(1)'
                        }, {
                            duration: 0.6,
                            delay: i * 0.08,
                            easing: [0.22, 1, 0.36, 1]
                        });
                    }, { margin: '-10%' });
                });

                // Animate heading elements
                const headingItems = document.querySelectorAll('.frame-heading span');
                headingItems.forEach((item, i) => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(30px)';

                    inView(item, () => {
                        animate(item, {
                            opacity: 1,
                            transform: 'translateY(0px)'
                        }, {
                            duration: 0.5,
                            delay: i * 0.12,
                            easing: [0.22, 1, 0.36, 1]
                        });
                    });
                });
            }
        });
    </script>

    <script>
        // Sound toggle for video
        document.addEventListener('DOMContentLoaded', () => {
            const video = document.getElementById('about-video');
            const soundToggle = document.getElementById('sound-toggle');
            const iconMuted = document.getElementById('icon-muted');
            const iconUnmuted = document.getElementById('icon-unmuted');

            if (video && soundToggle) {
                soundToggle.addEventListener('click', () => {
                    video.muted = !video.muted;
                    iconMuted.classList.toggle('hidden', !video.muted);
                    iconUnmuted.classList.toggle('hidden', video.muted);
                });
            }
        });
    </script>
@endsection
