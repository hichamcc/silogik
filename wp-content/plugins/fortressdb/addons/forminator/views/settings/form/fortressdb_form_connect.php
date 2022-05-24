<?php
// defaults
$vars = array(
	'form_name'                     => '',
	'action'                        => '',
	'error_message'                 => '',
	'fortressdb_form_connect_error' => '',
	'step_description'              => '',
	'app_integration'               => '',
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}

?>
<div class="integration-header">
    <h3 class="sui-box-title"
        id="dialogTitle"><?php echo esc_html( __( 'Form connection', FortressDB::DOMAIN ) ); ?></h3>
    <p><?php echo $vars['step_description']; // wpcs: xss ok ?></p>
	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
        <span class="sui-notice sui-notice-error"><p><?php echo esc_html( $vars['error_message'] ); ?></p></span>
	<?php endif; ?>
</div>
<form>
    <div class="sui-form-field <?php echo esc_attr( ! empty( $vars['fortressdb_form_connect_error'] ) ? 'sui-form-field-error' : '' ); ?>">
		<?php if ( ! empty( $vars['fortressdb_form_connect_error'] ) ) : ?>
            <span class="sui-error-message"><?php echo esc_html( $vars['fortressdb_form_connect_error'] ); ?></span>
		<?php endif; ?>
    </div>
    <input type="hidden" name="action" value="<?php echo $vars['action']; ?>"/>
</form>
