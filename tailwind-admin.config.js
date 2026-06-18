/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/admin/**/*.php"
  ],
  darkMode: 'class',
  theme: {
      extend: {
          colors: {
              primary: '#F06D1F',
              'primary-hover': '#D95E10',
              'primary-light': '#FFA633',
              'primary-bg': '#FFF3EB',
              'app-bg': '#F5F4F0',
              'card-bg': '#ffffff',
              'section-bg': '#F8F7F5',
              'input-bg': '#F2F1EE',
              border: '#E8E7E3',
              't1': '#1A1A1A',
              't2': '#5C5C5C',
              't3': '#A0A0A0',
          },
          fontFamily: {
              sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
          }
      }
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/container-queries'),
  ]
}
