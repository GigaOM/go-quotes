/*global module:false*/

module.exports = function(grunt) {
	'use strict';

	var js_files = [
		'components/js/lib/**/*.js'
	];

	// Project configuration.
	grunt.initConfig({
		uglify: {
			compress: {
				files: [
					{
						expand: true, // enable dynamic expansion
						cwd: 'components/js/lib/', // src matches are relative to this path
						src: ['**/*.js'], // pattern to match
						dest: 'components/js/min/'
					}
				]
			}
		},
		watch: {
			files: js_files,
			tasks: [
				'newer:uglify'
			]
		}
	});

	// Default task.
	grunt.loadNpmTasks( 'grunt-newer' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.registerTask( 'default', ['newer:uglify'] );
};