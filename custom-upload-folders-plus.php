<?php
/**
 * Plugin Name: Custom Upload Folders Plus
 * Plugin URI:
 * Description: Organize file uploads by File Type (mov, gif, png, mp3...) and Logged in user (nickname,first-name last-name...).
 * Version: 1.0.2
 * Author: John Wight
 * Author URI: http://wight-space.com/
 * Text Domain: jwcuf
 * Domain Path: /languages/
 * License: GPLv2 or later
 * Contributor: Rodolfo Buaiz
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */


if (!class_exists('Custom_Upload_Folders_Plus')) {


	class Custom_Upload_Folders_Plus
	{
		/**
		 * URL to this plugin's directory.
		 *
		 * @type string
		 */
		public $plugin_url = '';


		/**
		 * Path to this plugin's directory.
		 *
		 * @type string
		 */
		public $plugin_path = '';

		/**
		 * Path to this upload dirctory.
		 *
		 * @type string
		 */
		public $upload_dir = '';

		/**
		 * Constructor.
		 *
		 * @see plugin_setup()
		 * @since 2012.09.12
		 */

		public function __construct() {

			$this->plugin_url	= plugins_url( '/', __FILE__ );
			$this->plugin_path	= plugin_dir_path( __FILE__ );
			$this->plugin_slug	= dirname( plugin_basename( __FILE__ ) );
			$this->upload_dir 	= wp_upload_dir();
			//$this->load_language( 'jwcuf' );

			add_filter(
				'wp_handle_upload_prefilter',
				array( $this, 'handle_upload_prefilter' )
			);
			add_filter(
				'wp_handle_upload',
				array( $this, 'handle_upload' )
			);

			add_filter(
				'admin_init' ,
				array( $this, 'register_fields' )
			);
			add_filter(
				'plugin_action_links',
				array( $this, 'settings_plugin_link' ),
				10,
				2
			);
			add_action(
				'admin_enqueue_scripts',
				array($this , 'jwcuf_load_scripts')
			);

		}


		/**
		 * CUSTOM UPLOAD DIR
		 * Change upload folder
		 *
		 * @param type $file
		 * @return type
		 */
		public function handle_upload_prefilter( $file )
		{
			add_filter( 'upload_dir', array($this, 'custom_upload_dir') );
			return $file;
		}


		/**
		 * CUSTOM UPLOAD DIR
		 * Remove upload folder filter
		 *
		 * @param type $fileinfo
		 * @return type
		 */
		public function handle_upload( $fileinfo )
		{
			remove_filter('upload_dir', array($this, 'custom_upload_dir') );
			return $fileinfo;
		}
		/**
		 * CUSTOM UPLOAD DIR
		 * Organize the Uploads Folder
		 *
		 * @param type $path
		 * @return string
		 */
		public function custom_upload_dir( $path )
		{

			if( $path['error'] ){
				return $path; //error on uploading
			}

			$custom_dir = '';
			$select = get_option( 'jwcuf_select' );

			if ($select != -1) {

				$folder_default = (get_option( 'jwcuf_default_folder_name' )) ? get_option( 'jwcuf_default_folder_name' ) : 'general' ;
				$uploads_use_yearmonth_folders  = get_option('uploads_use_yearmonth_folders ');

				switch( $select )
				{
					case 'by_user':

						$custom_dir = '/' . $this->get_formatted_user_data();
						break;

					case 'by_file_type':

						$extension = pathinfo( $_POST['name'], PATHINFO_EXTENSION );
						$extension = strtolower($extension);
						$file_types = get_option( 'jwcuf_file_types' );

						foreach ($file_types as $key => $value) {
							$array = explode(",", $value);

							if ( in_array( $extension, $array) ) {
								$custom_dir = '/' . $key;
								break;
							}else {
								$custom_dir = '/'. $folder_default;
							}
						}

						break;
					default:
						$custom_dir = '/'. $folder_default;
						break;
				}

				if ($uploads_use_yearmonth_folders) {
					$path['path']    = $path['path'];
					$path['url']     = $path['url'];
					$path['subdir']  = $custom_dir;
					$path['path']   .= $custom_dir;
					$path['url']    .= $custom_dir;

				}else {
					$path['path']    = str_replace( $path['subdir'], '', $path['path'] );
					$path['url']     = str_replace( $path['subdir'], '', $path['url'] );
					$path['subdir']  = $custom_dir;
					$path['path']   .= $custom_dir;
					$path['url']    .= $custom_dir;
				}
			}

			return $path;
		}

		/**
		 * Add settings to wp-admin/options-general.php page
		 */
		public function register_fields()
		{
			register_setting( 'media', 'jwcuf_select', 'esc_attr' );
			register_setting( 'media', 'jwcuf_user_folder_name', array( $this, 'validate_folder_builder') );
			register_setting( 'media', 'jwcuf_file_types', array( $this, 'validate_file_types'));
			register_setting( 'media', 'jwcuf_default_folder_name', array( $this, 'validate_folder_name_default') );

			add_settings_field('jwcuf_settings', 'Custom Upload Folders', array( $this, 'jwcuf_settings_page' ), 'media');
		}

		/**
		 * Error checking for file type feilds
		 */
		public function validate_folder_builder($input){

			$select = get_option( 'jwcuf_select' );

			if ($select == "by_user") {
				// check form empty field
				if ($input == "") {
					add_settings_error(
						'jwcuf_validate_folder_builder_input',
						'jwcuf_validate_folder_builder',
						'Oops, Please fill out Folder Name Builder!',
						'error'
					);
				}

				if ($input == "underscore" || $input == "dash") {
					add_settings_error(
						'jwcuf_validate_folder_builder_input',
						'jwcuf_validate_folder_builder',
						'Use more than just _ - for a folder name.',
						'error'
					);
				}
			}

			return $input;

		}

		public function validate_folder_name_default($input){

			$select = get_option( 'jwcuf_select' );

			if ($select == "by_file_type") {
				//check to see if feild is empty
				if ($input == "") {
					add_settings_error(
						'jwcuf_folder_name_input',
						'jwcuf_default_folder_name',
						'Please enter a Default Folder Name',
						'error'
					);
				}
			}

			return $input;

		}

		/**
		 * Error checking for file type feilds
		 */
		public function validate_file_types($input){


			$select = get_option( 'jwcuf_select' );

			if ($select == "by_file_type") {
				// check to see if length
				if (count($input) == 0) {
					add_settings_error(
						'jwcuf_validate_file_types_array',
						'jwcuf_validate_file_types',
						'Oops, Please fill out Folder Name & select a File Extention!',
						'error'
					);
				}else {

				}
			}

			return $input;

		}


		/**
		 * Settings Options
		 */
		public function jwcuf_settings_page ()
		{
			$select = get_option( 'jwcuf_select' );
			$folder_name = get_option( 'jwcuf_user_folder_name' );
			$folder_name_default = get_option( 'jwcuf_default_folder_name' );

			$show_hide_by_user = ($select == "by_user") ? 'jwcuf-show' : 'jwcuf-hide' ;
			$show_hide_by_file_type = ($select == "by_file_type") ? 'jwcuf-show' : 'jwcuf-hide' ;

			$select_options = array(
				'-1'			=> __( '-none-', 'jwcuf' ),
				'by_user'		=> __( 'By Logged in User', 'jwcuf' ),
				'by_file_type'	=> __( 'By File Type', 'jwcuf' )
			);

			$selected = selected( '', $select, false );

			?>

			<select id="jwcuf-select" name="jwcuf_select">
			<?php foreach( $select_options as $key => $value ): $selected = selected( $key, $select, false );?>
				<option value="<?php echo $key; ?>" <?php echo $selected; ?> ><?php echo $value; ?></option>
			<?php endforeach; ?>

			</select>

			<div id="jwcuf-by-user-group" class="<?php echo $show_hide_by_user; ?>">

				<table id="jwcuf-by-user-input" class="jwcuf-table widefat">
					<tbody>
						<tr>
							<th>Folder Name Builder</th>
						</tr>
						<tr>
							<td width="100%">
								<div id="jwcuf-user-select" class="widefat"></div>
							</td>
						</tr>
						<tr>
							<td width="100%">
								<input id="jwcuf-by-user-input" class="jwcuf-hide" type="hidden" name="jwcuf_user_folder_name" value="<?php echo $folder_name; ?>">
								<label><?php echo $this->upload_dir['url'] ?>/<span id="jwcuf-user-folder-name"><?php echo $this->get_formatted_user_data(); ?></span></label>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<?php $file_types = (get_option( 'jwcuf_file_types' )) ? get_option( 'jwcuf_file_types' ) : null ?>

			<div id="jwcuf-by-file-type-group" class="<?php echo $show_hide_by_file_type; ?>">

				<table id="jwcuf-by-file-type-input" class="jwcuf-table widefat">
					<tbody>
						<tr>
							<th>Folder Name</th>
							<th>Select Extentions</th>
						</tr>
						<tr>
							<td width="50%">
								<input class="widefat" type="text">
							</td>
							<td width="50%">
								<div id="jwcuf-by-file-type-allowed-mime-types" name="allowed_mime_types" class="widefat"></div>
							</td>
						</tr>
					</tbody>
				</table>

				<button id="jwcuf-add-folder-btn" data-pos="0" class="button">Add Folder</button>

				<br />

				<table id="jwcuf-extension-list" class="jwcuf-table wp-list-table widefat">
					<tbody>
						<tr>
							<th>Folder Path</th>
							<th>Selected Extentions</th>
						</tr>

					<?php if ($file_types != null): ?>
						<?php if(count($file_types) > 0): ?>
							<?php foreach ($file_types as $key => $value): ?>
								<tr id="jwcuf-<?php echo $key; ?>">
									<td width="50%">
										<input class="jwcuf-hide" type="hidden" name="jwcuf_file_types[<?php echo $key; ?>]" value="<?php echo $value; ?>">
										<label><?php echo $this->upload_dir['url'] . '/' . $key; ?></label>
									</td>
									<td width="50%">
										<label> <?php echo $value; ?> </label>
										<ul class="jwcuf-tools">
											<li class="jwcuf-delete-btn" data-delete="#jwcuf-<?php echo $key; ?>" data-values="<?php echo $value; ?>">
												<i class="jwcuf-delete-icon"></i>
											</li>
										</ul>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					<?php endif ?>

					</tbody>
				</table>
				<hr>
				<table id="jwcuf-default-folder-input" class="jwcuf-table widefat">
					</tbody>
						<tr>
							<th>Default Folder Name</th>
						</tr>
						<tr>
							<td width="100%">
								<input class="widefat" name="jwcuf_default_folder_name" value="<?php echo $folder_name_default; ?>" type="text">

							</td>
						</tr>
						<tr>
							<td width="100%">
								<label><?php echo $this->upload_dir['url'] ?>/<span id="jwcuf-default-folder-name"><?php echo $folder_name_default; ?></span></label>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- this is the template for the js add button -->
			<tr id="jwcuf-template">
				<td>
					<input class="jwcuf-hide" type="hidden" name="" value="">
					<label class="jwcuf-user-folder-name"><?php echo $this->upload_dir['url'] . '/'; ?><span></span></label>
				</td>
				<td>
					<label class="jwcuf-folder-extentions"></label>
					<ul class="jwcuf-tools">
						<li class="jwcuf-delete-btn">
							<i class="jwcuf-delete-icon"></i>
						</li>
					</ul>
				</td>
			</tr>

			<?php

		}


		/**
		 * Add settings link to plugin action row
		 *
		 * @param type $links
		 * @param type $file
		 * @return type
		 */
		public function settings_plugin_link( $links, $file )
		{
			if ( $file == plugin_basename( dirname(__FILE__) . '/custom-upload-folders-plus.php' ) )
			{
				$in = '<a href="options-media.php">' . __( 'Settings', 'jwcuf' ) . '</a>';
				array_unshift( $links, $in );
			}
			return $links;
		}

		/**
		 * Add scripts to media admin pages /wp-admin/options-media.php
		 *
		 * @param type $scripts
		 * @param type $file
		 * @return type
		 */
		public function jwcuf_load_scripts($hook) {

			if( $hook == "options-media.php" ){

				wp_enqueue_style( 'select2-css',	$this->plugin_url . 'css/select2.min.css' );
				wp_enqueue_style( 'styles-css',		$this->plugin_url . 'css/styles.css' );
				wp_enqueue_script( 'select2-js',	$this->plugin_url . 'js/select2.js', array('jquery','jquery-ui-sortable'), '1.0.0', true );

				wp_register_script( 'scripts-js',	$this->plugin_url . 'js/scripts.js', array('jquery','jquery-ui-sortable','select2-js'), '1.0.0', true );
				wp_localize_script( 'scripts-js',	'select2_user_data', $this->get_select2_user_data() );
				wp_localize_script( 'scripts-js',	'select2_selected_user_data', $this->get_select2_selected_user_data());
				wp_localize_script( 'scripts-js',	'select2_used_mime_types', $this->get_select2_used_mime_types());
				wp_localize_script( 'scripts-js',	'select2_allowed_mime_types', $this->get_select2_allowed_mime_types());

				wp_enqueue_script( 'scripts-js');
			}

		}

		/**
		 * Gets logged in user data and formats it for file path preview.
		 * @wp-hook init
		 * @param   string $domain
		 * @since   2015.02.1
		 * @return  string
		 */
		public function get_formatted_user_data() {

			$current_user = wp_get_current_user();
			$folder_user_name = get_option( 'jwcuf_user_folder_name' );
			$array = explode(",", $folder_user_name);
			$build_path = '';

			if (count($array) > 0) {

				foreach ($array as $value) {

					if ($value) {

						$dash = strstr($value,'dash');
						$underscore = strstr($value,'underscore');

						if ($dash) {
							$build_path .= '-';

						}else if ($underscore) {
							$build_path .= '_';

						}else {
							$build_path .= $current_user->$value;
						}
					}

				}

			}

			return strtolower($build_path);
		}

		/**
		 * Gets logged in user data.
		 * @param   none
		 * @since   2015.02.1
		 * @return  array
		 */
		public function get_select2_user_data(){

			// select2 select options for user data
			$current_user_options = $this->get_select2_user_data_array();

			// remove any user data that is not present/empty in the Users -> your profile page
			// if the user data is emty in the Users -> your profile page then this function take them out.

			for ($r=0; $r < count($current_user_options[0]['children']); $r++) {

				if($current_user_options[0]['children'][$r]['preview'] == ''){
					unset($current_user_options[0]['children'][$r]);
				};
			}

			//resets array keys, if they are unset/out of order it will break select2 js obj

			$temp = $current_user_options[0]['children'];
			$current_user_options[0]['children'] = array();

			foreach($temp as $value) {
				$current_user_options[0]['children'][] = $value;
			}

			return $current_user_options;
		}

		public function get_select2_user_data_array(){

			$current_user = wp_get_current_user();
			// select2 select options for user data
			$return_array = array(
				array('text' => 'User Data', 'children' => 	array(
						array('id' => 'user_login', 	'text' => __( 'Username', 'jwcuf'),		'preview' => str_replace(' ', '-', $current_user->user_login)),
						array('id' => 'user_firstname', 'text' => __( 'First Name', 'jwcuf'),	'preview' => str_replace(' ', '-', $current_user->user_firstname)),
						array('id' => 'user_lastname', 	'text' => __( 'Last Name', 'jwcuf'),	'preview' => str_replace(' ', '-', $current_user->user_lastname)),
						array('id' => 'display_name', 	'text' => __( 'Display Name', 'jwcuf'),	'preview' => str_replace(' ', '-', $current_user->display_name)),
						array('id' => 'nickname', 		'text' => __( 'Nick Name', 'jwcuf'),	'preview' => str_replace(' ', '-', $current_user->nickname )),
						array('id' => 'ID', 			'text' => __( 'User ID', 'jwcuf'),		'preview' => str_replace(' ', '-', $current_user->ID)))
				),
				array('text' => 'Spacers', 'children' => 	array(
						array('id' => 'dash', 	  'text' => __( 'dash: -', 'jwcuf'),		'preview' => '-'),
						array('id' => 'underscore', 'text' => __( 'underscore: _', 'jwcuf'),	'preview' => '_'))
				)
			);

			return $return_array;
		}

		/**
		 * Gets selected logged in user data.
		 * @param   none
		 * @since   2015.02.1
		 * @return  array
		 */
		public function get_select2_selected_user_data(){

			$return_array = array();
			$user_folder_name = (get_option( 'jwcuf_user_folder_name' )) ? explode( ',', get_option( 'jwcuf_user_folder_name' )) : array();
			$current_user_options = $this->get_select2_user_data_array();
			$dash_count = 0;
			$underscore_count = 0;

			for ($q = 0; $q < count($user_folder_name); $q++) {

				$dash = strstr($user_folder_name[$q],'dash');
				$underscore = strstr($user_folder_name[$q],'underscore');

				if ($dash) {
					$return_array[] = array('id' => 'dash_'. $dash_count , 'text' => __( 'dash: -', 'jwcuf'), 'preview' => '-');
					$dash_count++;

				}elseif($underscore){
					$return_array[] = array('id' => 'underscore_' . $underscore_count, 'text' => __( 'underscore: _', 'jwcuf'),	'preview' => '_');
					$underscore_count++;

				}else {

					for($r = 0; $r < count($current_user_options[0]['children']); $r++) {

						if($current_user_options[0]['children'][$r]['id'] == $user_folder_name[$q]){

							$return_array[] = array('id' => $current_user_options[0]['children'][$r]['id'], 'text' => $current_user_options[0]['children'][$r]['text'], 'preview' => str_replace(' ', '-', $current_user_options[0]['children'][$r]['preview']));

						}
					}

				}

			}

			return $return_array;
		}



		/**
		 * Get DB data and formats it so it can be used for the select2 select box.
		 * @param   none
		 * @since   2015.02.1
		 * @return  array
		 */
		public function get_select2_used_mime_types(){

			$file_types = (get_option( 'jwcuf_file_types' )) ? get_option( 'jwcuf_file_types' ) : null;
			$array = array();
			$return_array = array();

			if ($file_types || $file_types != null) {

				foreach ($file_types as $key => $value) {
					$array = explode(",", $value);

					foreach ($array as $key => $value) {
						$return_array[] = array('id' => $value , 'text' => strtoupper($value) );
					}
				}

			}else {
				$return_array = array();
			}

			return $return_array;

		}
		/**
		 * Get saved file extensions being used.
		 * @param   none
		 * @since   2015.02.1
		 * @return  array
		 */
		public function get_select2_allowed_mime_types(){

			$file_types = get_allowed_mime_types();
			$array = array();
			$return_array = array();

			foreach ($file_types as $key => $value) {
				$array = explode("|", $key);

				foreach ($array as $key => $value) {
					$return_array[] = array('id' => $value , 'text' => strtoupper($value) );
				}
			}

			return $return_array;
		}


		/**
		 * Loads translation file.
		 *
		 * Accessible to other classes to load different language files (admin and
		 * front-end for example).
		 *
		 * @wp-hook init
		 * @param   string $domain
		 * @since   2012.09.11
		 * @return  void
		 */
		public function load_language( $domain )
		{
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

			load_textdomain(
					$domain, WP_LANG_DIR . '/plugins/custom-upload-folders-plus/' . $domain . '-' . $locale . '.mo'
			);

			load_plugin_textdomain(
					$domain, FALSE, $this->plugin_slug . '/languages'
			);
		}

	}
}

new Custom_Upload_Folders_Plus();