/** @type {import('tailwindcss').Config} config */
const config = {
  content: ['./app/**/*.php', './resources/**/*.{php,vue,js,css}'],
  theme: {
    extend: {
      colors: {
        black: '#262625',
        white: '#FFFFFF',
        'white-pure': '#FFFFFF',
        'white-off': '#fbf9f2',
        green: '#00a15d',
        yellow: '#F0BF08',
        blue: '#509FB9',
        red: '#F47932',
        sand: '#DEC68C',
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      fontSize: {
        xs: ['0.5rem', { lineHeight: '1.25' }],
        sm: ['0.875rem', { lineHeight: '1.3' }],
        base: ['1.063rem', { lineHeight: '1.35' }],
        lg: ['1.8rem', { lineHeight: '1.05' }],
        xl: ['2.5rem', { lineHeight: '1.05' }],
        '2xl': ['2.875rem', { lineHeight: '1.2' }],
        '3xl': ['3.275rem', { lineHeight: '1.2' }],
        '4xl': ['4.313rem', { lineHeight: '1.2' }],
      },
      spacing: {
        1: '0.25rem',
        2: '0.5rem',
        3: '0.7rem',
        4: '1rem',
        5: '1.5rem',
        6: '2.25rem',
        7: '3.5rem',
        8: '5rem',
      },
    },
  },
  plugins: [],
};

export default config;
