/* jshint node:true */
module.exports = function( grunt ) {
	require( 'load-grunt-tasks' )( grunt );

	// Project configuration.
	grunt.initConfig( {
		// Package
		pkg: grunt.file.readJSON( 'package.json' ),

		// PHP Code Sniffer
		phpcs: {
			application: {
				src: [
					'**/*.php',
					'!node_modules/**',
					'!vendor/**'
				]
			},
			options: {
				bin: 'vendor/bin/phpcs',
				standard: 'phpcs.ruleset.xml',
				showSniffCodes: true
			}
		},

		// PHPLint
		phplint: {
			options: {
				phpArgs: {
					'-lf': null
				}
			},
			all: [ 'src/**/*.php' ]
		},

		// PHP Mess Detector
		phpmd: {
			application: {
				dir: 'src'
			},
			options: {
				exclude: 'node_modules',
				reportFormat: 'xml',
				rulesets: 'phpmd.ruleset.xml'
			}
		},
		
		// PHPUnit
		phpunit: {
			application: {}
		},

		// JSHint
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: [ 'Gruntfile.js' ],
			admin: [
				'js/admin.js'
			]
		},

		// Uglify
		uglify: {
			options: {
				sourceMap: true
			},
			scripts: {
				files: {
					// Admin
					'js/admin.min.js': 'js/admin.js'
				}
			}
		},

		// Compass
		compass: {
			build: {
				options: {
					sassDir: 'sass',
					cssDir: 'css'
				}
			}
		},

		// PostCSS
		postcss: {
			options: {
				map: {
					inline: false,
					annotation: false
				},

				processors: [
					require( 'autoprefixer' )( { browsers: 'last 2 versions' } )
				]
			},
			dist: {
				src: 'css/admin.css'
			}
		},

		// CSS min
		cssmin: {
			assets: {
				files: {
					'css/admin.min.css': 'css/admin.css'
				}
			}
		}
	} );

	// Default task(s).
	grunt.registerTask( 'default', [ 'jshint', 'phplint', 'phpmd', 'phpcs' ] );
	grunt.registerTask( 'assets', [ 'jshint', 'uglify', 'compass', 'postcss', 'cssmin' ] );
};
