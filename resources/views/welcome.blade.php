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
                border-radius: 1rem;
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
                padding: 3rem 0;
            }

            .frame-window {
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
                padding: 0 1rem;
            }

            .frame-track {
                scroll-snap-type: x mandatory;
                width: max-content;
                padding: 1rem 0 2rem;
                transform: none !important;
                gap: 1rem;
            }

            .frame-item {
                scroll-snap-align: center;
                opacity: 1 !important;
                transform: none !important;
                flex: 0 0 75vw;
                width: 75vw;
                max-width: 280px;
            }

            .frame-item img {
                height: 280px;
            }

            .frame-heading span {
                opacity: 1 !important;
                transform: none !important;
            }

            .frame-heading {
                font-size: 1.75rem !important;
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

        /* FAQ Section Styles */
        .faq-section {
            background: #fdf6f0;
            position: relative;
            overflow: hidden;
        }

        .faq-container {
            position: relative;
            min-height: 500px;
        }

        .faq-card {
            position: absolute;
            padding: 1.25rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            min-width: 200px;
            max-width: 280px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .faq-card:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .faq-card-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .faq-card-text {
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .faq-card-arrow {
            flex-shrink: 0;
            width: 1rem;
            height: 1rem;
        }

        /* Card colors */
        .faq-card-mint { background: #b8f0e6; }
        .faq-card-mint .faq-card-arrow { color: #0d9488; }

        .faq-card-yellow { background: #fef08a; }
        .faq-card-yellow .faq-card-arrow { color: #854d0e; }

        .faq-card-peach { background: #fecaca; }
        .faq-card-peach .faq-card-arrow { color: #dc2626; }

        .faq-card-purple { background: #ddd6fe; }
        .faq-card-purple .faq-card-arrow { color: #7c3aed; }

        .faq-card-orange { background: #fed7aa; }
        .faq-card-orange .faq-card-arrow { color: #ea580c; }

        .faq-card-pink { background: #fbcfe8; }
        .faq-card-pink .faq-card-arrow { color: #db2777; }

        /* Card positions */
        .faq-card-1 { top: 10%; left: 5%; transform: rotate(-3deg); }
        .faq-card-2 { top: 5%; left: 35%; transform: rotate(1deg); }
        .faq-card-3 { top: 15%; right: 5%; transform: rotate(5deg); }
        .faq-card-4 { top: 45%; left: 8%; transform: rotate(4deg); }
        .faq-card-5 { top: 50%; left: 38%; transform: rotate(-2deg); }
        .faq-card-6 { top: 55%; right: 8%; transform: rotate(3deg); }

        /* Modal styles */
        .faq-modal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .faq-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .faq-modal-content {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            position: relative;
            transform: translateY(20px) scale(0.95);
            transition: transform 0.3s ease;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }

        .faq-modal.active .faq-modal-content {
            transform: translateY(0) scale(1);
        }

        .faq-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #9ca3af;
            font-size: 1.5rem;
            transition: color 0.2s;
        }

        .faq-modal-close:hover {
            color: #374151;
        }

        .faq-modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-right: 2rem;
            color: #ca8a04;
        }

        .faq-modal-body {
            color: #4b5563;
            line-height: 1.75;
        }

        @media (max-width: 768px) {
            .faq-container {
                min-height: auto;
                display: flex;
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .faq-card {
                position: relative !important;
                top: auto !important;
                left: auto !important;
                right: auto !important;
                transform: none !important;
                max-width: 100%;
                width: 100%;
            }

            .faq-card:hover {
                transform: scale(1.02) !important;
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
                    <a href="{{ route('auth.instagram') }}"
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

        <!-- FAQ Section -->
        <section class="faq-section py-16 md:pt-10">
            <div class="max-w-6xl mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-5xl font-black text-[#111] leading-tight">
                        Your Snack<br>Confessions, Answered
                    </h2>
                </div>

                <div class="faq-container">
                    <!-- Card 1 - Mint -->
                    <div class="faq-card faq-card-mint faq-card-1" data-faq="1">
                        <div class="faq-card-content">
                            <span class="faq-card-text">is it really zero sugar? or just marketing?</span>
                            <svg class="faq-card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 17L17 7M17 7H7M17 7V17"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Card 2 - Yellow -->
                    <div class="faq-card faq-card-yellow faq-card-2" data-faq="2">
                        <div class="faq-card-content">
                            <span class="faq-card-text">but what is monk fruit anyway?</span>
                            <svg class="faq-card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 7L7 17M7 17V7M7 17H17"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Card 3 - Peach -->
                    <div class="faq-card faq-card-peach faq-card-3" data-faq="3">
                        <div class="faq-card-content">
                            <span class="faq-card-text">can i eat the whole tub in one sitting?</span>
                            <svg class="faq-card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 17L17 7M17 7H7M17 7V17"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Card 4 - Purple -->
                    <div class="faq-card faq-card-purple faq-card-4" data-faq="4">
                        <div class="faq-card-content">
                            <span class="faq-card-text">what's with all the crazy flavours?</span>
                            <svg class="faq-card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 17L17 7M17 7H7M17 7V17"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Card 5 - Orange -->
                    <div class="faq-card faq-card-orange faq-card-5" data-faq="5">
                        <div class="faq-card-content">
                            <span class="faq-card-text">so do you just sell ice cream, or what?</span>
                            <svg class="faq-card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 7L7 17M7 17V7M7 17H17"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Card 6 - Pink -->
                    <div class="faq-card faq-card-pink faq-card-6" data-faq="6">
                        <div class="faq-card-content">
                            <span class="faq-card-text">what does "no guilt" really mean?</span>
                            <svg class="faq-card-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 17L17 7M17 7H7M17 7V17"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Modal -->
        <div id="faq-modal" class="faq-modal">
            <div class="faq-modal-content">
                <span class="faq-modal-close">&times;</span>
                <h3 id="faq-modal-title" class="faq-modal-title"></h3>
                <p id="faq-modal-body" class="faq-modal-body"></p>
            </div>
        </div>

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

            // FAQ Modal functionality
            const faqData = {
                1: {
                    title: "Is it really zero sugar? Or just marketing?",
                    body: "100% zero sugar. No hidden sweeteners, no artificial nonsense. We use monk fruit extract—a natural sweetener that's been used in Asia for centuries. It's 200x sweeter than sugar but has zero calories and zero glycemic impact. So yes, it's the real deal. Your taste buds get the party, your body gets the peace."
                },
                2: {
                    title: "But what is monk fruit anyway?",
                    body: "Monk fruit (Luo Han Guo) is a small melon native to Southeast Asia. Buddhist monks have been using it for centuries—hence the name. It contains natural compounds called mogrosides that taste incredibly sweet but don't spike blood sugar. It's nature's cheat code for guilt-free sweetness."
                },
                3: {
                    title: "Can I eat the whole tub in one sitting?",
                    body: "Look, we're not here to judge. Our desserts are made with clean ingredients—zero sugar, no artificial preservatives, and real nutrients. But even good things deserve moderation. That said, if you finish a tub at midnight... we understand. We've all been there."
                },
                4: {
                    title: "What's with all the crazy flavours?",
                    body: "Because boring is not on our menu. Indian desserts are legendary—Elaichi Rabdi, Lonavala Fudge—but so are global favorites like Belgian Dark and Blueberry Cheesecake. We believe healthy shouldn't mean limited. Every craving deserves a guilt-free answer."
                },
                5: {
                    title: "So do you just sell ice cream, or what?",
                    body: "We started with ice cream, but we're building a whole dessert revolution. Think fudges, chocolates, spreads—all zero sugar, all delicious. MonkMagic is about proving that 'healthy dessert' doesn't have to be an oxymoron. Stay tuned, more magic is coming."
                },
                6: {
                    title: "What does \"no guilt\" really mean?",
                    body: "It means eating dessert without the mental math. No counting calories, no sugar crash, no regret spiral at 2 AM. Our desserts are made so you can actually enjoy them—not as a 'cheat' but as part of your everyday life. That's the monk way: balance, not sacrifice."
                }
            };

            const modal = document.getElementById('faq-modal');
            const modalTitle = document.getElementById('faq-modal-title');
            const modalBody = document.getElementById('faq-modal-body');
            const modalClose = document.querySelector('.faq-modal-close');
            const faqCards = document.querySelectorAll('.faq-card');

            faqCards.forEach(card => {
                card.addEventListener('click', () => {
                    const faqId = card.dataset.faq;
                    const data = faqData[faqId];
                    if (data) {
                        modalTitle.textContent = data.title;
                        modalBody.textContent = data.body;
                        modal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                });
            });

            const closeModal = () => {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            };

            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    closeModal();
                }
            });
        });
    </script>
@endsection
