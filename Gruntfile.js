module.exports = function( grunt ) {
	// Project configuration.
	grunt.initConfig( {
		// Package
		pkg: grunt.file.readJSON( 'package.json' ),

		// JSHint
		jshint: {
			all: [ 'Gruntfile.js', 'composer.json', 'package.json' ]
		},

		// PHP Code Sniffer
		phpcs: {
			application: {
				dir: [ 'src' ],
			},
			options: {
				standard: 'phpcs.ruleset.xml',
				extensions: 'php',
				ignore: 'node_modules'
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
			application: {},
		},
	} );

	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-phplint' );
	grunt.loadNpmTasks( 'grunt-phpmd' );

	// Default task(s).
	grunt.registerTask( 'default', [ 'jshint', 'phplint', 'phpmd', 'phpcs' ] );
};
