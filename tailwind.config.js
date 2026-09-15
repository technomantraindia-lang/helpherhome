/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.html",
    "./services/*.html",
    "./assets/js/*.js"
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          black: '#050505',
          dark: '#111111',
          gold: {
            DEFAULT: '#D1A55C',
            rich: '#C38F38',
            light: '#E0BA78',
            champagne: '#E7CD9F',
          },
          ivory: '#FFF9ED',
          offwhite: '#F6F4EF',
          text: '#191919',
          white: '#FFFDF8',
        }
      },
      fontFamily: {
        heading: ['"Nunito Sans"', 'sans-serif'],
        sans: ['Inter', 'sans-serif'],
      },
      boxShadow: {
        'gold-sm': '0 2px 10px rgba(209, 165, 92, 0.15)',
        'gold-md': '0 4px 20px rgba(209, 165, 92, 0.22)',
        'gold-lg': '0 8px 32px rgba(195, 143, 56, 0.28)',
        'dark-soft': '0 10px 30px rgba(0, 0, 0, 0.08)',
        'dark-card': '0 20px 40px rgba(0, 0, 0, 0.4)',
      },
      backgroundImage: {
        'gold-gradient': 'linear-gradient(135deg, #C38F38 0%, #E0BA78 48%, #E7CD9F 72%, #C38F38 100%)',
        'gold-gradient-hover': 'linear-gradient(135deg, #D1A55C 0%, #E7CD9F 50%, #C38F38 100%)',
        'dark-gradient': 'linear-gradient(180deg, #111111 0%, #050505 100%)',
        'hero-gradient': 'linear-gradient(to right, rgba(5,5,5,0.95) 0%, rgba(5,5,5,0.8) 50%, rgba(5,5,5,0.4) 100%)',
        'ivory-gradient': 'linear-gradient(180deg, #FFF9ED 0%, #F6F4EF 100%)',
      }
    },
  },
  plugins: [],
}
