/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "./templates/**/*.html.twig",
    "./assets/**/*.js",
    "./node_modules/flyonui/dist/js/*.js",
  ],
  safelist: [
    'bg-base-300',
    'bg-base-300/60'
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
