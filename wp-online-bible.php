<?php
/*
Plugin Name: Online Bible VP for Wordpress 
Plugin URI: http://www.vivendoapalavra.org/
Version: 1.6.0
Description: Plugin for implementation of Online Bible in your Wordpress blog. With it, you can make available the Word of God and bless your website's users. The plugin allows to consult all 66 books of the Holy Bible versions: King James Edition - English, Almeida Corrigida Fiel - Português (1994), Spanish Reina Valera (1960) and the French version Louis Segond (1910).
Author: André Brum Sampaio
Author URI: http://www.web117.com.br/
*/

define( 'BOVP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BOVP_PATH', plugin_dir_path( __FILE__ ) );

$folder = explode( '/', plugin_basename( __FILE__ ) );

define( 'BOVP_FOLDER', $folder[0] );

include ( BOVP_PATH . '/includes/functions.php');

if( get_option( 'bovp_settings' ) ) {

$bovp_sets = (object) array_merge( get_option( 'bovp_settings' ),get_option( 'bovp_user_settings' ));

}

bovp_set_info();

if( file_exists( BOVP_PATH . '/themes/' . $bovp_sets->theme . '.php' ) ) {

	include( BOVP_PATH . '/themes/' . $bovp_sets->theme . '.php' );

} 

if( isset( $_REQUEST['settings-updated'] ) AND $bovp_sets->translate != 'not_set' ) { 

	if( $bovp_sets->translate != $bovp_sets->active_translate ) { 

		$bovp_table_to_install = $wpdb->prefix .  'bovp_' . $bovp_sets->translate;
		$file_path_to_install = BOVP_PATH . 'data/'. $bovp_sets->translate;

		echo "<!--  FALHA: " . $file_path_to_install . ".csv -->";

		if( !file_exists( $file_path_to_install . ".csv" ) ) { 

			bovp_set_option( 'translate', $bovp_sets->active_translate );
			bovp_set_option( 'message', array( 'error', __( 'CSV file not Found!', 'bovp' ) ) );

		} else { 

			$installed = bovp_install_table( $bovp_table_to_install, $file_path_to_install );

			if( $installed ) {

				bovp_set_option( 'active_translate', $bovp_sets->translate );
				bovp_set_option( 'table', $wpdb->prefix . 'bovp_' . $bovp_sets->active_translate);
				bovp_set_option( 'message', array( 'updated', __( 'Table installation complete!.', 'bovp' ) ) );
					
			} else { 

				bovp_set_option( 'translate', 'not_set' );
				bovp_set_option( 'active_translate', 'not_set' );
	    		bovp_set_option( 'message', array( 'error', __( 'Unable to create the table.', 'bovp' ) ) );
			}

		}

	} 

}

include  BOVP_PATH .'includes/bovp_widget_index.class.php';
include  BOVP_PATH .'includes/bovp_widget_verse.class.php';

register_activation_hook(__FILE__,'bovp_install');
register_deactivation_hook(__FILE__,'bovp_uninstall');