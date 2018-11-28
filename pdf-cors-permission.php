<?php
/**
 * Plugin Name:       PDF CORS Permission
 * Plugin URI:        https://connections-pro.com/
 * Description:       Add .htaccess CORS directive to permit PDFs to load on external domains.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        http://connections-pro.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       undefined
 * Domain Path:       /languages
 */

register_activation_hook( __FILE__, 'insertPDFCORSDirective' );
register_deactivation_hook( __FILE__, 'removePDFCORSDirective' );

function insertPDFCORSDirective() {

	// Get path to main .htaccess for WordPress
	$htaccess = get_home_path() . '.htaccess';

	$lines = array(
		'<IfModule mod_headers.c>',
		'AddType application/pdf .pdf',
		'<FilesMatch ".(pdf)">',
		'Header set Access-Control-Allow-Origin "*"',
		'</FilesMatch>',
		'</IfModule>',
	);

	insert_with_markers( $htaccess, 'PDF CORS Permission', $lines );
}

function removePDFCORSDirective() {

	$result = FALSE;

	// Get path to main .htaccess for WordPress
	$htaccess = get_home_path() . '.htaccess';

	$lines = extract_from_markers( $htaccess, 'PDF CORS Permission' );

	if ( ! empty( $lines ) ) {

		ob_start();
		$creds = request_filesystem_credentials( '', FALSE, FALSE, dirname( $htaccess ), NULL, TRUE );
		ob_end_clean();

		if ( $creds ) {

			$filesystem = WP_Filesystem( $creds, dirname( $htaccess ), TRUE );

			if ( $filesystem ) {

				/**
				 * @var WP_Filesystem_Direct $wp_filesystem
				 */
				global $wp_filesystem;

				$contents    = $wp_filesystem->get_contents( $htaccess );
				$newContents = remove_with_markers( $contents, 'PDF CORS Permission' );
				$result      = $wp_filesystem->put_contents( $htaccess, $newContents, FS_CHMOD_FILE );
			}
		}

	}

	return $result;
}

function remove_with_markers( $contents, $marker ) {

	$beginPosition = strpos( $contents, '# BEGIN ' . $marker );
	$endPosition   = strpos( $contents, '# END ' . $marker ) + strlen( '# END ' . $marker );
	$newContent    = substr( $contents, 0, $beginPosition - 1 );
	$newContent    .= trim( substr( $contents, $endPosition, strlen( $contents ) ) );

	return $newContent;
}
