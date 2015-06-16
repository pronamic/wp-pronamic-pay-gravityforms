/* jshint node:true */
module.exports = function( grunt ) {
	// Project configuration.
	grunt.initConfig( {
		// Package
		pkg: grunt.file.readJSON( 'package.json' ),

		// JSHint
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			all: [ 'Gruntfile.js' ]
		},

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
		}
	} );

	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-phplint' );
	grunt.loadNpmTasks( 'grunt-phpmd' );

	// Default task(s).
	grunt.registerTask( 'default', [ 'jshint', 'phplint', 'phpmd', 'phpcs' ] );
};
