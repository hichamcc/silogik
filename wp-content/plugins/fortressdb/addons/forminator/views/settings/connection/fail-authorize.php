<?php
$link = sprintf( '<a href="%s">this page</a>', admin_url( 'admin.php?page=fortressdb_plugin' ) )
?>
<div class="integration-header">
    <h3 class="sui-box-title" id="dialogTitle2">
		<?php echo esc_html( __( 'Something went wrong', FortressDB::DOMAIN ) ); ?>
    </h3>
    <p><?php echo sprintf( '%s is not connected. Go to %s', 'FortressDB', $link ); ?></p>
</div>
<button class="sui-button forminator-addon-close"><?php esc_html_e( 'Close', FortressDB::DOMAIN ); ?></button>
