<?php
/*
Plugin Name: TablePress Extension: Single Cell Content Shortcode
Plugin URI: https://tablepress.org/extensions/table-cell-shortcode/
Description: Custom Extension for TablePress that adds a Shortcode to retrieve the content of a single cell
Version: 1.1
Author: Tobias Bäthge
Author URI: https://tobias.baethge.com/
*/

if ( ! is_admin() ) {
	add_shortcode( 'table-cell', 'tablepress_table_cell_shortcode' );
}

/**
 * Handle Shortcode [table-cell id=<ID> cell=<name> /].
 *
 * @since 1.0.0
 *
 * @param array $atts list of attributes that where included in the Shortcode.
 * @return string Text that replaces the Shortcode.
 */
function tablepress_table_cell_shortcode( $shortcode_atts ) {
	$shortcode_atts = (array) $shortcode_atts;
	// Parse Shortcode attributes, only allow those that are specified.
	$default_shortcode_atts = array(
			'id' => 0,
			'row' => 0,
			'column' => 0,
			'cell' => '',
	);
	$shortcode_atts = shortcode_atts( $default_shortcode_atts, $shortcode_atts );

	$table_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', $shortcode_atts['id'] );
	$table = TablePress::$model_table->load( $table_id, true, false ); // Load table data, but don't load options or visibility.
	if ( is_wp_error( $table ) ) {
		return "[table &#8220;{$table_id}&#8221; could not be loaded /]<br />\n";
	}

	$row = absint( $shortcode_atts['row'] );
	$column = absint( $shortcode_atts['column'] );
	$cell = $shortcode_atts['cell'];

	if ( ! empty( $cell ) && 1 === preg_match( '#([A-Z]+)([0-9]+)#', $cell, $matches ) ) {
		$column = TablePress::letter_to_number( $matches[1] );
		$row = $matches[2];
	}

	$row = $row - 1;
	$column = $column - 1;

	if ( isset( $table['data'][ $row ] ) && isset( $table['data'][ $row ][ $column ] ) ) {
		$cell_content = $table['data'][ $row ][ $column ];
		$cell_content = preg_replace( '/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};)/', '&amp;', $cell_content );
		$cell_content = do_shortcode( $cell_content );
	} else {
		return "[table &#8220;{$table_id}&#8221; does not have that cell /]<br />\n";
	}

	return $cell_content;
}
