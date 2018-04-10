<?php
/*
Plugin Name: Extra feature for WebCMS
Description: This custom WordPress plugin will enanle all addtional custom features..
Version: 1.0
Author: Dinesh Sharma
*/


/*
* Disable the write / update mode
* Desc: enabling this constant will disallow to add new plugin / theme or 
* disable feature to update plugin / theme / core WP 
*/

define( 'DISALLOW_FILE_MODS', true );

remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );


/* #######################
* Extending Tablepress plugin to allow new button in editor widget to chow single cell shortcode
*/

/*
##############Changes required in the Tablepress Core file
## file name:  .....plugins\tablepress\views\view-editor_button_thickbox.php

##Chnages: Need to update the function column_table_action to change the return value

protected function column_table_action( array $item ) {
	return '
		<input type="button" class="insert-shortcode button" title="' . esc_attr( '[' . TablePress::$shortcode . " id={$item['id']} /]" ) . '" value="' . esc_attr__( 'Insert Shortcode', 'tablepress' ) . '" />
		
		<!-- this is the extra code for button -->
		<input type="button" class="select-metadata button" title="' . esc_attr__( 'Select Metadata', 'tablepress' ) . '" data-id="'.$item["id"].'" value="' . esc_attr__( 'Select Metadata', 'tablepress' ) . '" />			
		<!-- this is the extra ends here-->
	';
}
*******/



/*
* Checks if the tablepress and tablepress single cell plugins are installed 
* and activated and then define the extension logic.
*/

if ( 
	in_array('tablepress/tablepress.php', (array) get_option( 'active_plugins', array())) and 
	in_array('tablepress-single-cell-shortcode/tablepress-single-cell-shortcode.php', (array) get_option( 'active_plugins', array()))
){
	
	
/*
* function : wph_tablepress_script
* Desc: Include the custom JS file on tablepress widget Popup
*/
	if (!function_exists('wph_tablepress_script')) {
		function wph_tablepress_script() 
		{
			echo "<script type='text/javascript' src='". content_url() . "/mu-plugins/inc/wphscript.js'></script>";		
			echo "<link rel='stylesheet' href='". content_url() . "/mu-plugins/inc/wphstyle.css' type='text/css' media='all' />";		
		}
		add_action( 'admin_post_tablepress_editor_button_thickbox', 'wph_tablepress_script' , 50);
	}


/*
* function : wph_tablepress_show_table
* Desc: this will be called on AJAX request to show the table detail 
* Ajax Action: wph_select_metadata
*/

	if (!function_exists('wph_tablepress_show_table')) {		
		function wph_tablepress_show_table() 
		{
			// Load table data, but don't load options or visibility.
			$table = TablePress::$model_table->load( intval( $_POST['tbl_id']), true, true ); 
			
			// filter the blank rows
			$table['data'] = array_filter($table['data'], function ($row){return array_filter($row);});
			
			// Create table html 
			echo wph_load_select_metadata_table($table);
			wp_die();
		}
		add_action( 'wp_ajax_wph_select_metadata', 'wph_tablepress_show_table' );
	}

/*
* function : wph_load_select_metadata_table
* Desc: this function with create the html table for the selected tablepress table. 
* param: tablepress table info
*/

	if (!function_exists('wph_load_select_metadata_table')) {		
		function wph_load_select_metadata_table($table) 
		{	
			// actuall tablepress table ID
			$tableId = intval( $_POST['tbl_id']);
			$table_content = $table['data'];
			$table_title = $table['name'];
			$table_options = $table['options'];
			
			ob_start();
			?>			
			<p>Select the metadata / cell you wish to insert and click the "Insert Shortcode" button.</p>
				<h3><?php echo $table_title; ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<?php 				
					foreach ($table_content as $rowId => $row){
						echo "<tr>";
						$col_tag = ($table_options['table_head'] == 1) ? (($rowId == 0) ? "th" : "td") : "td";
						foreach ($row as $col=>$col_value){	
						$shortcode = "[table-cell id=$tableId row=".($rowId+1)." column=".($col+1)." /]";
							echo "<$col_tag><div class='intd'>$col_value ";
							
							// do not create shortcode button for table header
							if (!($table_options['table_head'] == 1 and $rowId == 0)) {
								echo "<br>
									<input type='button' value='Insert Shortcode'  class='cell_btn button' title='$shortcode' />";
							}
							echo "</div></$col_tag>";
						}
						echo "</tr>";
					}
					?>			
				</table>			
			<?php
			$html = ob_get_clean();
			return $html;
		}
	}    
	

	/**
	 * Change the admin menu name for TablePress.
	 * *
	 * @param string $name Current name in the admin menu.
	 * @return string New name in the admin menu.
	 */
	
	if ( is_admin() ) {
		add_filter( 'tablepress_admin_menu_entry_name', 'tpcustom_change_admin_menu_name' );
	}
	
	function tpcustom_change_admin_menu_name( $name ) {
		return 'Rates';
	}		
}
/* Tablepress extension functionality ends here */


/* feature to disable activation and deactivation of plugin from backend*/

/*
	disable_plugin_link_action
	@desc disable_plugin_link_action is a hook to plugin_action_links which will achek and remove the action links for pluigns
	
*/

if (!function_exists('disable_plugin_link_action') ) {
	
	
	add_filter( 'plugin_action_links', 'disable_plugin_link_action', 10, 4 );
	
	function disable_plugin_link_action( $actions, $plugin_file, $plugin_data, $context ) 
	{
		// Liat of alll stager Plugin which is being onboard to WP by default
		
		$standerdPlugins = array (
			"oasis-workflow-pro/oasis-workflow-pro.php",
			"revslider/revslider.php",
			"saml-20-single-sign-on/samlauth.php",
			"tablepress-single-cell-shortcode/tablepress-single-cell-shortcode.php",
			"tablepress/tablepress.php",
			"ubermenu/ubermenu.php",
			"wp-migrate-db-pro-cli/wp-migrate-db-pro-cli.php",
			"wp-migrate-db-pro/wp-migrate-db-pro.php",
		);		
		
		if ( in_array( $plugin_file, $standerdPlugins ) ) {
				
			if ( array_key_exists( 'deactivate', $actions ) )
				unset( $actions['deactivate'] );
			
			if ( array_key_exists( 'activate', $actions ) )
				unset( $actions['activate'] );
			
		}
		return $actions;
	}
}


/*
	disable_plugin_bulk_action
	@desc disable_plugin_bulk_action is a hook to bulk_actions-screenid which will check and remove the activate / deactivate bulk opiont for pluigns
	
*/

if (!function_exists('disable_plugin_bulk_action') ) {
	
	add_filter('bulk_actions-plugins','disable_plugin_bulk_action');
	
	function disable_plugin_bulk_action($actions)
	{
	
		if ( array_key_exists( 'activate-selected', $actions ) )
			unset( $actions['activate-selected'] );
		
		if ( array_key_exists( 'deactivate-selected', $actions ) )
			unset( $actions['deactivate-selected'] );
	
	
        return $actions;
    }    
}
	
/* --- Ends Here --- 
 * feature to disable activation and deactivation of plugin from backend */


 
/* force Login functionality for dev , stag and test region*/
 
if (!function_exists('wph_forcelogin')) {	
	
	add_action('template_redirect', 'wph_forcelogin');
	function wph_forcelogin()
	{
		// Exceptions for AJAX, Cron, or WP-CLI requests
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		
		// Redirect unauthorized visitors
		if (!is_user_logged_in() && (strstr(@$_SERVER['HTTP_REFERER'], 'connect/c/portal/saml/sso') === false)) {						
			preg_match('@^(?:http(s)?://)?([^/]+)\/([^/]+)\/([^/]+)@i', home_url(), $matches);			
			
			//$regions = array ('dev', 'newsite', 'workarea' , 'live');			
			$regions = array ();			
			
			if(in_array ( @$matches[4], $regions)){
				wp_safe_redirect( "/connect/group/web-cms/dashboard/", 302 ); 
				exit();
			}
		}	 
	}
}

 
 /*ends here*/
 /* force Login functionality for dev , stag and test region*/

 
 
 
 
 
 
 /*
* function: wph_reduce_autologout_time
* desc: this function will check admin and users' login session and force logout user after 2 idle mins
*/

if (!function_exists('wph_reduce_autologout_time')) {
	
	add_action ('template_redirect', 'wph_reduce_autologout_time');
	add_action ('admin_init', 'wph_reduce_autologout_time');
	
	function wph_reduce_autologout_time(){    
		if ( is_user_logged_in()) {
			$user_id = get_current_user_id();
			if (get_user_meta ($user_id, 'last_activity_time', true)){
				if ((time() - get_user_meta ($user_id, 'last_activity_time', true)) > 900 ) {
					delete_user_meta($user_id, 'last_activity_time');
					wp_logout ();
					die ;
				}
			}
			update_user_meta ($user_id, 'last_activity_time', time());
		}
	}
}
