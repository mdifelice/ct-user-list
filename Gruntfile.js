module.exports = function( grunt ) {
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		uglify: {
			dist: {
				src: 'src/js/index.js',
				dest: 'assets/js/<%= pkg.name %>.min.js'
			}
		},
		sass: {
			options: {
				style: 'compressed'
			},
			dist: {
				src: 'src/css/index.scss',
				dest: 'assets/css/<%= pkg.name %>.min.css'
			}
		},
		makepot: {
			target: {
				options: {
					type: 'wp-plugin'
				}
			}
		}
	} );

	grunt.loadNpmTasks( 'grunt-contrib-sass' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	grunt.registerTask( 'default', [ 'uglify', 'sass', 'makepot' ] );
};
