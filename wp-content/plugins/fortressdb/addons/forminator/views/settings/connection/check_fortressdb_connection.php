<?php
// defaults
$vars = array(
	'error_message' => '',
	'is_close'      => false,
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="integration-header">
    <h3 class="sui-box-title"
        id="dialogTitle2"><?php echo esc_html( __( 'Check FortressDB Connection', FortressDB::DOMAIN ) ); ?></h3>
	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
        <span class="sui-notice sui-notice-error">
			<p><?php echo esc_html( $vars['error_message'] ); ?></p>
		</span>
	<?php endif; ?>
</div>

<?php if ( empty( $vars['error_message'] ) ) : ?>
    <form>
        <label class="sui-label"><?php esc_html_e( 'App Key', FortressDB::DOMAIN ); ?></label>
        <input class="sui-form-control" disabled name="app_key"
               placeholder="<?php echo esc_attr( __( 'App Key', FortressDB::DOMAIN ) ); ?>"
               value="">

        <label class="sui-label"><?php esc_html_e( 'App Secret', FortressDB::DOMAIN ); ?></label>
        <input class="sui-form-control" disabled name="app_secret"
               placeholder="<?php echo esc_attr( __( 'App Secret', FortressDB::DOMAIN ) ); ?>"
               value="">

        <input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
    </form>
<?php endif; ?>
