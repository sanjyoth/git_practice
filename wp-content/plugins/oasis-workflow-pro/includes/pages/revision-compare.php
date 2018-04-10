<?php
/*
 * Revision Compare
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

if( ! wp_verify_nonce( $_GET['_nonce'], 'owf_compare_revision_nonce' ) ) {
   return;
}

include( OASISWF_PATH . "includes/class-ow-diff.php" );


$revision_post_id = intval( $_GET['revision'] );
$original_post_id = get_post_meta( $revision_post_id, '_oasis_original', true );
$post = get_post( $original_post_id );
$revision_post = get_post( $revision_post_id );
$revision_edit_link = get_edit_post_link( $revision_post->ID );
$original_edit_link = get_edit_post_link( $post->ID );
$h2 = __( 'Comparison', 'oasisworkflow' );
$return_to_editor = '<a href="' . $revision_edit_link . '">' . "&larr;" . __( ' Return to Post editor' ) . '</a>';

$ow_revision_service = new OW_Revision_Service();

$revision_notice = __( 'Note : If you made any changes to the post, the updates are being saved while preparing the compare window. At times, if the "save" takes more time, you may not see the recent changes made to the post.
In those cases, simply close this window and click the "Compare With Original"  button again.', 'oasisworkflow' );
$return_to_revision = '<a href="' . $revision_edit_link . '">' . esc_html( $revision_post->post_title ) . '</a>';
$return_to_original = '<a href="' . $original_edit_link . '">' . esc_html( $post->post_title ) . '</a>';

$compare_by = 'content';
if( isset( $_POST['compare_by'] ) && ! empty( $_POST['compare_by'] ) ) {
   $compare_by = sanitize_text_field( $_POST['compare_by'] );
}

switch ( $compare_by ) {
   case 'raw':
      $original_content = $post->post_content;
      $revision_content = $revision_post->post_content;
      break;
   case 'content':
   default :
      $original_content = strip_tags( $post->post_content );
      $revision_content = strip_tags( $revision_post->post_content );
      break;
}

// compare two strings line by line
$ow_diff = new OW_Diff();
$diff = $ow_diff->compare( wordwrap( $original_content, 50 ), wordwrap( $revision_content, 50 ) );
?>
<div class="wrap">
   <h2 class="long-header"><?php echo $h2; ?></h2>
   <span class="revision-message"><?php echo $revision_notice; ?></span>
   <!--
   <div class="revision-middle-box"><?php echo __( 'This is the test revision.', 'oasisworkflow' ); ?></div>
   -->
   <form method="post" onchange="">
      <p>
         <input type="radio" name="compare_by" value="raw" <?php checked( $compare_by, 'raw' ); ?> onclick="javascript: submit()" /> <?php _e( 'HTML(raw) Compare', 'oasisworkflow' ); ?>
         &nbsp;
         <input type="radio" name="compare_by" value="content" <?php checked( $compare_by, 'content' ); ?> onclick="javascript: submit()" /> <?php _e( 'Text Compare', 'oasisworkflow' ); ?>
      </p>
   </form>
   <div class="revision-wrap">
      <table class="diff">
         <tbody>
            <tr>
               <td align="center"><?php echo __( 'Original: ', 'oasisworkflow' ); ?> <?php echo $return_to_original; ?></td>
               <td align="center"><?php echo __( 'Revision: ', 'oasisworkflow' ); ?> <?php echo $return_to_revision; ?></td>
            </tr>
            <tr>
               <td colspan=2><hr/></td>
            </tr>
         </tbody>
      </table>
      <?php
      $data = '<h3>' . __( 'Contents', 'oasisworkflow' ) . '</h3>';
      $data .= $ow_diff->to_table( $diff );
      $data .= '<h3>' . __( "Tags", "oasisworkflow" ) . '</h3>';
      $diff_data = $ow_revision_service->compare_tags( $original_post_id, $revision_post_id, $ow_diff );
      $data .= $diff_data;

      //compare categories
      $data .= '<h3>' . __( "Category", "oasisworkflow" ) . '</h3>';
      $diff_data = $ow_revision_service->compare_categories( $original_post_id, $revision_post_id, $ow_diff );
      $data .= $diff_data;

      //compare featured_image
      $data .= '<h3>' . __( "Featured Image", "oasisworkflow" ) . '</h3>';
      $diff_data = $ow_revision_service->compare_featured_image( $original_post_id, $revision_post_id, $ow_diff );
      $data .= $diff_data;

      echo $data;

      // to display custom data for add-ons
      do_action( 'owf_display_revision_compare_tab', $original_post_id, $revision_post_id, $ow_diff );
      ?>

      <?php
      apply_filters( 'owf_display_custom_fields', $original_post_id, $revision_post_id, $ow_diff );
      ?>
   </div>
</div>