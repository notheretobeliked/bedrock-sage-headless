/** @type {import('tailwindcss').Config}*/
import tailwindFluidType from 'tailwindcss-fluid-type';

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
		'rotate-3',
    'wp-block-home-section',
		{
			pattern: /bg-+/
		},
		{
			pattern: /text-+/
		},
		...Array.from({ length: 8 }, (_, i) => `pt-${i + 1}`),
		...Array.from({ length: 8 }, (_, i) => `pb-${i + 1}`)
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
			sans: ['Inter', 'sans-serif']
		},
		fontSize: {
			xs: '0.75rem',
			sm: '0.875rem',
			base: '1.2rem',
			lg: '1.35rem',
			xl: '1.5rem',
			'2xl': '1.7rem',
			'3xl': '1.875rem',
			'4xl': '2.25rem',
			'5xl': '3rem',
			'6xl': '4rem'
		},

		extend: {}
	},

	plugins: [
		tailwindFluidType({
			settings: {
				fontSizeMin: 1.125,
				fontSizeMax: 1.25,
				ratioMin: 1.125,
				ratioMax: 1.2,
				screenMin: 20,
				screenMax: 96,
				unit: 'rem',
				prefix: 'fluid-'
			},
			values: {
				base: [
					0,
					{
						lineHeight: 1.1,
						letterSpacing: '-0.1rem'
					}
				],
				lg: [
					9.4,
					{
						lineHeight: 1,
						letterSpacing: '-0.05rem'
					}
				],
				xl: [
					13.4,
					{
						lineHeight: 1,
						letterSpacing: '-0.05rem'
					}
				]
			}
		})
	]
}

export default config;
