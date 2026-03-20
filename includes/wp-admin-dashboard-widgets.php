<?php

add_action( 'admin_head', 'my_custom_fonts' );
function my_custom_fonts() {
	?>

	<style>
		body:not(.acf-internal-post-type) .acf-label label {
			font-weight: 700 !important;
			font-size: 1.5em;
		}

		body:not(.acf-internal-post-type) .acf-input .acf-label label {
			font-weight: 600 !important;
			font-size: 1.25em;
		}
		body:not(.acf-internal-post-type) .acf-th label {
			font-weight: 600 !important;
		}
	</style>

	<?php
}

