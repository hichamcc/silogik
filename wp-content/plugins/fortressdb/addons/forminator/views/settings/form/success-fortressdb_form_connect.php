<div class="integration-header">
	<h3 class="sui-box-title" id="dialogTitle">
		<?php echo esc_html( sprintf( __( 'Congratulations %1$s is connected to form', FortressDB::DOMAIN ), 'FortressDB' ) ); ?>
	</h3>
	<p><?php esc_html_e( 'You can now go to form settings and manage its fields.', FortressDB::DOMAIN ); ?></p>
</div>
<button class="sui-button forminator-addon-close"><?php esc_html_e( 'Close', FortressDB::DOMAIN ); ?></button>
<script>
  jQuery( "#forminator-module-publish" ).trigger("click");
</script>
