<?php

/**
 * Export Import class for Workflow
 *
 * @copyright   Copyright (c) 2015, Nugget Solutions, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.5
 *
 */
// Exit if accessed directly
if ( ! defined ( 'ABSPATH' ) ) {
	exit ();
}

/**
 * OW_Workflow_Export_Import Class
 *
 * @since 3.5
 */
class OW_Workflow_Export_Import {

	/**
	 * Set things up.
	 *
	 * @since 3.5
	 */

	public function __construct() {
		add_action ( 'admin_init', array ($this, 'export_workflows' ) );
	}

	/**
	 * Validate selected workflows for the following
	 * 1.
	 * Workflows should be valid
	 * 2. Only one version of the workflow should be allowed to export
	 *
	 * @global type $wpdb
	 * @param array $workflow_ids
	 * @return boolean true or false, if false validation failed
	 * @since 3.5
	 */
	public function validate_workflows( $workflow_ids ) {
		global $wpdb;

		// sanitize the values
		$workflow_ids = array_map ( 'intval', $workflow_ids );

		$is_export_valid = TRUE;
		$int_place_holders = array_fill ( 0, count ( $workflow_ids ), '%d' );
		$place_holders_for_workflow_ids = implode ( ",", $int_place_holders );

		// lets check if any of the selected workflows are invalid
		$sql = "SELECT ID FROM " . OW_Utility::instance ()->get_workflows_table_name () . " WHERE ID IN (" . $place_holders_for_workflow_ids . ") AND is_valid != '1'";

		$invalid_workflows = $wpdb->get_results ( $wpdb->prepare ( $sql, $workflow_ids ) );
		if ($invalid_workflows) { // looks like we found invalid workflows
			add_action ( 'admin_notices', array (
					$this,
					'invalid_workflow_selected_notice'
			) );
			$is_export_valid = FALSE;
		}

		// check if multiple versions of the workflow are selected
		$sql = "SELECT name, count(1) as workflow_count FROM " . OW_Utility::instance ()->get_workflows_table_name () . " WHERE ID IN (" . $place_holders_for_workflow_ids . ") GROUP BY name";

		$multiple_versions = $wpdb->get_results ( $wpdb->prepare ( $sql, $workflow_ids ) );
		if ($multiple_versions) {
			foreach ( $multiple_versions as $workflow ) {
				$workflow_count = $workflow->workflow_count;
				if ($workflow_count > 1) {
					add_action ( 'admin_notices', array (
							$this,
							'multiple_versions_selected_notice'
					) );
					$is_export_valid = FALSE;
				}
			}
		}

		return $is_export_valid;
	}

	/**
	 * Notice: If invalid workflows are selected then show this notice
	 *
	 * @since 3.5
	 */
	public function invalid_workflow_selected_notice() {
		$export_error = OW_Utility::instance ()->admin_notice ( array (
				'type' => 'error',
				'message' => 'Only valid workflows can be exported.'
		) );
		echo $export_error;
	}

	/**
	 * Notice: If multiple versions of the workflow are selected, then show this notice
	 *
	 * @since 3.5
	 */
	public function multiple_versions_selected_notice() {
		$export_error = OW_Utility::instance ()->admin_notice ( array (
				'type' => 'error',
				'message' =>  __('Only one version of the workflow can be exported.', 'oasisworkflow' )
		) );
		echo $export_error;
	}

	/**
	 * Notice: If there is no selected workflows, then show the notice
	 *
	 * @since 3.5
	 */
	public function no_workflow_selected_notice() {
		$export_error = OW_Utility::instance ()->admin_notice ( array (
				'type' => 'error',
				'message' => __( 'Please select at least one workflow to export.', 'oasisworkflow' )
		) );
		echo $export_error;
	}


	/**
	 * Notice: Import was successful
	 *
	 * @since 3.5
	 */
	public function successful_import_notice() {
		$import_success = OW_Utility::instance ()->admin_notice ( array (
				'type' => 'update',
				'message' => __( 'Workflows were imported successfully.', 'oasisworkflow' )
		) );
		echo $import_success;
	}

	/**
	 * Notice: file not found
	 *
	 * @since 3.5
	 */
	public function file_not_found_import_notice() {
		$import_error = OW_Utility::instance ()->admin_notice ( array (
				'type' => 'update',
				'message' => __( 'File not found.', 'oasisworkflow' )
		) );
		echo $import_error;
	}

	/**
	 * Export selected workflow(s) to csv file
	 *
	 * Validate selected workflows before export
	 * If validation passes, create/download CSV file with workflow and step information
	 * One line per workflow and all it's steps
	 *
	 * @global type $wpdb
	 * @return CSV File
	 * @since 3.5
	 */
	public function export_workflows() {
		global $wpdb;

		$selected_action = -1;
		// check if "Export Workflows" is selected as bulk action
		if ( isset ( $_POST ['ow-workflow-bulk-action'] ) && sanitize_text_field ( $_POST ["ow-workflow-bulk-action"] ) ) {

			check_admin_referer ( 'owf_export_workflows', 'owf_export_workflows' );
			$selected_action = sanitize_text_field ( $_POST ["action"] );

		}

		// If any other action is selected, then exit
		if ( $selected_action != 'export') {
			return FALSE;
		}

		$sel_workflows = array ();
		if ( isset ( $_POST ['workflows'] ) && count ( $_POST ['workflows'] ) > 0 ) {
			$sel_workflows = $_POST ['workflows'];
			$sel_workflows = array_map( 'esc_attr', $sel_workflows );
			// if user has not selected any workflow then do not do anything
		} else {
			add_action ( 'admin_notices', array (
					$this, 'no_workflow_selected_notice' ) );
			return FALSE;
		}

		if ( ! $this->validate_workflows ( $sel_workflows ) ) {
			return;
		}

		$data [] = array (
			'ID',
			'Name',
			'Description',
			'Workflow Info',
			'Version',
			'Parent ID',
			'Start Date',
			'End Date',
			'Is Auto Submit',
			'Auto Submit Info',
			'Is Valid',
			'Create DateTime',
			'Update DateTime',
			'Workflow Additional Info',
			'Steps'
		);

		$workflow_service = new OW_Workflow_Service ();
		$workflows = $workflow_service->get_multiple_workflows_by_id ( $sel_workflows );
		foreach ( $workflows as $workflow ) {
			$workflow_id = $workflow->ID;
			$workflow_name = $workflow->name;
			$description = $workflow->description;
			$version = $workflow->version;
			$parent_id = $workflow->parent_id;
			$start_date = $workflow->start_date;
			$end_date = $workflow->end_date;
			$wf_info = $workflow->wf_info;
			$is_auto_submit = $workflow->is_auto_submit;
			$auto_submit_info = $workflow->auto_submit_info;
			$is_valid = $workflow->is_valid;
			$create_datetime = current_time('mysql');
			$update_datetime = current_time('mysql');
			$wf_additional_info = $workflow->wf_additional_info;

			$steps_table = OW_Utility::instance ()->get_workflow_steps_table_name ();
			$results = $wpdb->get_results ( $wpdb->prepare ( "SELECT *  FROM $steps_table WHERE `workflow_id` = '%d'", $workflow_id ) );
			$steps = array ();
			if ( $results ) {
				foreach ( $results as $step ) {
					$step_data = array ();
					$step_data ['step_info'] = $step->step_info;
					$step_data ['process_info'] = $step->process_info;
					$step_data ['workflow_id'] = $step->workflow_id;
					$step_data ['create_datetime'] = current_time('mysql');
					$step_data ['update_datetime'] = current_time('mysql');
					$steps [] = $step_data;
				}
			}

			$data [] = array (
				$workflow_id,
				$workflow_name,
				$description,
				$wf_info,
				$version,
				$parent_id,
				$start_date,
				$end_date,
				$is_auto_submit,
				$auto_submit_info,
				$is_valid,
				$create_datetime,
				$update_datetime,
				$wf_additional_info,
				json_encode( $steps )
			);
		}

		$today = date ( "Ymd-His" );
		$fileName = "oasis-workflow-export-" . $today . ".csv";

		// output headers so that the file is downloaded rather than displayed
		header ( 'Content-Type: text/csv; charset=UTF-8' );
		header ( "Content-Disposition: attachment; filename={$fileName}" );

		$fh = @fopen ( 'php://output', 'w' );

		foreach ( $data as $key => $val ) {
			@fputcsv ( $fh, $val ); // Put the data into stream
		}

		@fclose ( $fh );
		exit ();
	}

	/**
	 * Import workflows into database from a file
	 *
	 * Read the file, line by line
	 * One line represents the workflow and all it's steps
	 * Closes the file handle after import
	 */
	public function import_workflows() {
		global $wpdb;

		// nonce check
		check_admin_referer ( 'workflow-csv-import', 'security' );

		// get the file handle of the uploaded file
		try {
			$handle = fopen ( $_FILES ['filename'] ['tmp_name'], "r" );
			if ( ! $handle ) {
				add_action ( 'admin_notices', array (
						$this, 'file_not_found_import_notice' ) );
				return;
			}
		} catch ( Exception $e ) {
			OW_Utility::instance()->logger( $e );
    	}

		$row = 1; // start with 1, since the 0th line is header

		// get the table names
		$workflow_table = OW_Utility::instance()->get_workflows_table_name();
		$workflow_steps_table = OW_Utility::instance()->get_workflow_steps_table_name();

		// loop through each line of the csv file
		while ( ( $data = fgetcsv ( $handle, 0, "," ) ) !== FALSE ) {

			// do not process header row
			if ( $row ++ == 1 ) {
				continue;
			}

			$workflows = array (
				'name' => sanitize_text_field( $data [1] ), // workflow name
				'description' => sanitize_text_field( $data [2] ), // workflow description
				'wf_info' => sanitize_text_field( $data [3] ), // workflow graphic info
				'version' => 1, // $data[4], reset the workflow version to 1
				'parent_id' => 0, // $data[5], since we are resetting workflow version so parent-id = 0
				'start_date' => sanitize_text_field( $data [6] ), //start date of workflow
				'end_date' => sanitize_text_field( $data [7] ), // end date of workflow
				'is_auto_submit' => (int) $data [8], //auto submit flag
				'auto_submit_info' => sanitize_text_field( $data [9] ), // auto submit info
				'is_valid' => (int) $data [10], // is valid flag
				'create_datetime' => sanitize_text_field( $data [11] ), // create date time
				'update_datetime' => sanitize_text_field( $data [12] ), // update date time
				'wf_additional_info' => sanitize_text_field( $data [13] ) //additional info, like post types, user roles
			);

			$format = array (
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%s',
					'%s',
					'%d',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s'
			);
			$wpdb->insert ( $workflow_table, $workflows, $format );
			$workflow_id = $wpdb->insert_id;

			// we need to update the wf_info on the workflow with the new step_ids (fc_dbid)
			$wf_info_decoded = json_decode( $data[3] );
			$wf_steps = $wf_info_decoded->steps;

			// now let's insert the steps data
			$steps_data = json_decode ( $data [14] ); // all step info
			$count = count ( $steps_data );
			$step_format = array (
					'%s',
					'%s',
					'%d',
					'%s',
					'%s'
			);

			foreach( $steps_data as $step ) {
				$steps = array (
					'step_info' => $step->step_info,
					'process_info' => $step->process_info,
					'workflow_id' => $workflow_id, // use the newly inserted workflow_id
					'create_datetime' => $step->create_datetime,
					'update_datetime' => $step->update_datetime
				);
				$wpdb->insert( $workflow_steps_table, $steps, $step_format );
				$step_id = $wpdb->insert_id; // get the newly created step id

				// We need to update the wf_info (which represents graphical info in the workflow table)
				// with the updated step_id

				// get the step name
            $step_info = json_decode( $step->step_info );
				$step_name_temp = $step_info->step_name;

				foreach ( $wf_steps as $k => $v ) {
					if ( $step_name_temp == $v->fc_label ) { // match the step name with the label name in the graphical info
						$v->fc_dbid = $step_id; // update the fc_dbid with the newly inserted step id
					}
				}
			}

			// update the workflow table with the modified wf_info
			$result = $wpdb->update( $workflow_table,
					array(
						'wf_info' => json_encode( $wf_info_decoded )
					),
					array( 'ID' => $workflow_id )
			);
		}

		fclose ( $handle );

		add_action ( 'admin_notices', array (
				$this, 'successful_import_notice' ) );
	}
}

$ow_workflow_export_import = new OW_Workflow_Export_Import ();

if ( isset ( $_GET ['page'] ) && sanitize_text_field ( $_GET ['page'] ) == 'oasiswf-import' && isset ( $_POST ['hidden_oasiswf_import'] ) ) {
	$ow_workflow_export_import->import_workflows();
}
?>