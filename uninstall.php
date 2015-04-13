<?php
/**
 * Uninstall Procedure for Custom Upload Folders
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;


// delete options
delete_option( 'jwcuf_select' );
delete_option( 'jwcuf_user_folder_name' );
delete_option( 'jwcuf_default_folder_name' );
delete_option( 'jwcuf_file_types' );

if ( is_multisite() )
{
    delete_option( 'jwcuf_uploads_use_yearmonth_folders' );
}
