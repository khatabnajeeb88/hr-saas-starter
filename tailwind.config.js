/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "./templates/**/*.html.twig",
    "./assets/**/*.js",
  ],
  safelist: [
    'bg-base-300',
    'bg-base-300/60'
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Noto Sans Arabic"', 'sans-serif'],
      },
    },
  },
  plugins: [
    function ({ addVariant }) {
      addVariant('is-drawer-open', '.drawer-toggle:checked ~ .drawer-side &')
      addVariant('is-drawer-close', '.drawer-toggle:not(:checked) ~ .drawer-side &')
    },
  ],
}
