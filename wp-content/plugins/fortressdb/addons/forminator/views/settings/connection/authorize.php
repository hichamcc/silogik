<?php
// defaults
$vars = array(
	'connected_account' => array(),
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>
<div class="integration-header">
    <h3 class="sui-box-title"
        id="dialogTitle2"><?php echo esc_html( sprintf( __( 'Authorize %1$s', FortressDB::DOMAIN ), 'FortressDB' ) ); ?></h3>
	<?php if ( ! empty( $vars['connected_account'] ) ) : ?>
        <p><?php esc_html_e( sprintf( __( 'Your %1$s account is now authorized', FortressDB::DOMAIN ), 'FortressDB' ) ); ?> </p>
        <strong><?php echo esc_html( $vars['connected_account']['email'] ); ?></strong>
	<?php endif ?>
</div>
