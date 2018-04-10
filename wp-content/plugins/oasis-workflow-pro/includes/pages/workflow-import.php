<?php
/*
 * Workflow Import Page
 *
 * @copyright   Copyright (c) 2016, Nugget Solutions, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
   exit();
}
?>
<h2><?php _e( 'Import Workflows', 'oasisworkflow' ); ?></h2>
<div class="wrap">
	<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form"
		action="admin.php?page=oasiswf-import&security=<?php echo wp_create_nonce( 'workflow-csv-import' ); ?>">
		<div id="settingstuff">
		   <p>
		      <label for="upload"><?php _e( 'Choose a file from your computer:', 'oasisworkflow' ); ?></label>
		      <input type="file" id="upload" name="filename" size="50">
		      <input type="hidden" name="hidden_oasiswf_import" />
		   </p>
		   <?php submit_button( __( 'Upload file and import' ), 'primary' ); ?>
		</div>
	</form>
	<div id="poststuff">
		<div class="owf-sidebar">
			<div class="postbox" style="float: left;">
				<h3 style="cursor: default;">
					<span><?php _e("Instructions:", "oasisworkflow"); ?> </span>
				</h3>
				<div class="inside inside-section">
	         	<ol>
	         		<li><?php _e("Go to the workflows list page", "oasisworkflow"); ?> - <a href="admin.php?page=oasiswf-admin">
	         			<?php _e("All Workflows", "oasisworkflow"); ?></a>
	         		</li>
	         		<li><?php _e("Export the required workflows using the bulk action - Export Workflows.", "oasisworkflow"); ?></li>
	         		<li><?php _e("Upload the downloaded csv file to import the workflows.", "oasisworkflow"); ?></li>
	         	</ol>
	         </div>
	   	</div>
		</div>
	</div>
</div>