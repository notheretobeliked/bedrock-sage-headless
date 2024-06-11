/** @type {import('tailwindcss').Config} config */
const config = {
  content: ['./app/**/*.php', './resources/**/*.{php,vue,js}'],
  safelist: [
    'md:grid-cols-2',
    'md:grid-cols-3',
    'bg-black',
    'bg-white',
    'object-cover',
    'object-fit',
    'object-fill',
    'object-none',
    'text-xl',
    'object-contain',
    'stroke-green',
    'stroke-black',
    'bg-extremecaution',
    'stroke-black',
    'stroke-white',
    'fill-black',
    'fill-white',
		{
			pattern: /bg-+/
		},
		{
			pattern: /text-+/
		},
    ...Array.from({ length: 8 }, (_, i) => `pt-${i + 1}`),
    ...Array.from({ length: 8 }, (_, i) => `pb-${i + 1}`),

  ],
	theme: {
		colors: {
			white: '#ffffff',
			black: '#000000',
			caution: '#e8ff4a',
			extremecaution: '#ffa05b',
			danger: '#ff521a',
			extremedanger: '#cd0000'
			
		},
		fontFamily: {
			anton: ['Anton', 'sans-serif'],
			sans: ['Inter', 'sans-serif'],
		},

		extend: {
		}
	},
  plugins: [],
};

export default config;
