<nav class="w-full bg-white dark:bg-[#111] border-b border-gray-200 dark:border-gray-800 relative z-50">
    <div class="max-w-[1300px] mx-auto px-6 py-4 md:py-6 flex items-center justify-between text-sm font-medium">
        <!-- Logo -->
        <a href="{{ url('/') }}"
            class="flex items-center gap-2 text-gray-900 dark:text-gray-100 text-lg font-semibold z-50">
            <img src="{{ asset('storage/logo.jpg') }}" alt="MonkMagic Logo" class="max-w-[140px] md:max-w-[200px] w-full h-auto">
        </a>

        <!-- Desktop Menu -->
        <div class="hidden lg:flex flex-wrap items-center justify-end gap-6 text-gray-800 dark:text-gray-200">
            <a href="{{ url('/') }}" class="hover:underline">Home</a>
            <a href="{{ url('/') }}#products" class="hover:underline">Products</a>
            <a href="{{ url('/about') }}" class="hover:underline">About</a>
            <a href="{{ url('/privacy-policy') }}" class="hover:underline">Privacy Policy</a>
            <a href="{{ url('/terms-and-conditions') }}" class="hover:underline">Terms &amp; Conditions</a>
            <a href="{{ url('/refund-and-cancellation') }}" class="hover:underline">Refund &amp; Cancellation</a>
        </div>

        <!-- Mobile Menu Button -->
        <button 
            id="mobile-menu-btn"
            class="lg:hidden flex items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors z-50"
            aria-label="Toggle menu"
            aria-expanded="false"
        >
            <!-- Hamburger Icon -->
            <svg id="menu-icon-open" class="w-6 h-6 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <!-- Close Icon (hidden by default) -->
            <svg id="menu-icon-close" class="w-6 h-6 text-gray-800 dark:text-gray-200 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</nav>

<!-- Mobile Drawer Overlay -->
<div id="mobile-drawer-overlay" class="fixed inset-0 bg-black/50 z-40 opacity-0 invisible transition-all duration-300 lg:hidden"></div>

<!-- Mobile Drawer -->
<div id="mobile-drawer" class="fixed top-0 right-0 h-full w-[280px] max-w-[85vw] bg-white dark:bg-[#111] z-40 transform translate-x-full transition-transform duration-300 ease-out lg:hidden shadow-2xl">
    <div class="pt-24 px-6 pb-8 h-full overflow-y-auto">
        <nav class="flex flex-col gap-1">
            <a href="{{ url('/') }}" class="mobile-nav-link py-3 px-4 rounded-lg text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium transition-colors">
                Home
            </a>
            <a href="{{ url('/') }}#products" class="mobile-nav-link py-3 px-4 rounded-lg text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium transition-colors">
                Products
            </a>
            <a href="{{ url('/about') }}" class="mobile-nav-link py-3 px-4 rounded-lg text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium transition-colors">
                About
            </a>
            
            <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>
            
            <span class="px-4 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold mb-2">Legal</span>
            
            <a href="{{ url('/privacy-policy') }}" class="mobile-nav-link py-3 px-4 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm transition-colors">
                Privacy Policy
            </a>
            <a href="{{ url('/terms-and-conditions') }}" class="mobile-nav-link py-3 px-4 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm transition-colors">
                Terms &amp; Conditions
            </a>
            <a href="{{ url('/refund-and-cancellation') }}" class="mobile-nav-link py-3 px-4 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm transition-colors">
                Refund &amp; Cancellation
            </a>
        </nav>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('mobile-menu-btn');
    const drawer = document.getElementById('mobile-drawer');
    const overlay = document.getElementById('mobile-drawer-overlay');
    const iconOpen = document.getElementById('menu-icon-open');
    const iconClose = document.getElementById('menu-icon-close');
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

    let isOpen = false;

    const openDrawer = () => {
        isOpen = true;
        drawer.classList.remove('translate-x-full');
        drawer.classList.add('translate-x-0');
        overlay.classList.remove('opacity-0', 'invisible');
        overlay.classList.add('opacity-100', 'visible');
        iconOpen.classList.add('hidden');
        iconClose.classList.remove('hidden');
        menuBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    };

    const closeDrawer = () => {
        isOpen = false;
        drawer.classList.add('translate-x-full');
        drawer.classList.remove('translate-x-0');
        overlay.classList.add('opacity-0', 'invisible');
        overlay.classList.remove('opacity-100', 'visible');
        iconOpen.classList.remove('hidden');
        iconClose.classList.add('hidden');
        menuBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };

    const toggleDrawer = () => {
        isOpen ? closeDrawer() : openDrawer();
    };

    menuBtn.addEventListener('click', toggleDrawer);
    overlay.addEventListener('click', closeDrawer);

    // Close drawer when clicking a link
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', () => {
            closeDrawer();
        });
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            closeDrawer();
        }
    });

    // Close drawer on resize to desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024 && isOpen) {
            closeDrawer();
        }
    });
});
</script>
