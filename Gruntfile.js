/* jshint node:true */
module.exports = function( grunt ) {
	require( 'load-grunt-tasks' )( grunt );

	// Project configuration.
	grunt.initConfig( {
		// Package
		pkg: grunt.file.readJSON( 'package.json' ),

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

		// Sass Lint
		sasslint: {
			options: {
				configFile: '.sass-lint.yml'
			},
			target: [
				'scss/**/*.scss'
			]
		},

		// Compass
		compass: {
			build: {
				options: {
					sassDir: 'scss',
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
					require( 'autoprefixer' )()
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
	grunt.registerTask( 'default', [ 'jshint' ] );
	grunt.registerTask( 'assets', [ 'sasslint', 'jshint', 'uglify', 'compass', 'postcss', 'cssmin' ] );
};
