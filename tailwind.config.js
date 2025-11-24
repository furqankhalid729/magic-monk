/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './app/View/Components/**/*.php',
    './app/Livewire/**/*.php',
    './app/Filament/**/*.php',
    './vendor/filament/**/*.blade.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'Poppins', 'Outfit', 'ui-sans-serif', 'system-ui'],
      },
    },
  },
  plugins: [],
}