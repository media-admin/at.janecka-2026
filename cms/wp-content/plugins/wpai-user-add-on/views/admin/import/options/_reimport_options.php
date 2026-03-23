<?php

	if ( $post['custom_type'] == 'shop_customer' ) {
		include( '_reimport_options_shop_customer.php' );
	} else {
		include( '_reimport_options_import_users.php' );
	}

 ?>