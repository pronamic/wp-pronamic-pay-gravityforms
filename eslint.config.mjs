import js from '@eslint/js';
import json from '@eslint/json';
import globals from 'globals';

export default [
	{
		ignores: [
			'build/',
			'node_modules/',
			'vendor/',
			'vendor-bin/',
			'wordpress/',
			'wp-content/',
			'packages/',
			'docs/',
			'**/*.min.js',
		],
	},
	{
		files: [ '**/*.js' ],
		...js.configs.recommended,
		languageOptions: {
			ecmaVersion: 5,
			sourceType: 'script',
			globals: {
				...globals.browser,
				_: 'readonly',
				Backbone: 'readonly',
				jQuery: 'readonly',
				wp: 'readonly',
			},
		},
		rules: {
			...js.configs.recommended.rules,
			curly: 'error',
			eqeqeq: [ 'error', 'smart' ],
			'no-caller': 'error',
			quotes: [ 'error', 'single' ],
			'no-trailing-spaces': 'error',
		},
	},
	{
		files: [ '**/*.json' ],
		ignores: [ 'package-lock.json' ],
		plugins: { json },
		language: 'json/json',
		...json.configs.recommended,
	},
];
