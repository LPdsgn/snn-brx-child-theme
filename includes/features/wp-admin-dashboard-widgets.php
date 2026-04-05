<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'admin_head', 'admin_customization' );
function admin_customization() {
	?>

	<style>
		/* body:not(.acf-internal-post-type) .acf-label label {
			font-weight: 700 !important;
			font-size: 1.5em;
		}

		body:not(.acf-internal-post-type) .acf-input .acf-label label {
			font-weight: 600 !important;
			font-size: 1.25em;
		}
		body:not(.acf-internal-post-type) .acf-th label {
			font-weight: 600 !important;
		} */
	</style>

	<?php
}

