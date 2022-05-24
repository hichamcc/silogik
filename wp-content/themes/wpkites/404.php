<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package wpkites
 */
get_header();?>
<section class="section-space error-page bg-default">
    <div class="container<?php echo esc_html(wpkites_container());?>">         
        <div class="row">
            <div class="col-lg-12 col-sm-12">
                <div class="text-center justify-content-center">
                    <h2 class="title"><?php esc_html_e('4', 'wpkites' ); ?><img src="<?php echo esc_url(WPKITES_TEMPLATE_DIR_URI.'/assets/images/crack-bulb.png');?>" class="img-fluid" alt="<?php esc_attr_e('cup-tea', 'wpkites'); ?>"><?php esc_html_e('4', 'wpkites' ); ?></h2>
                    <h2 class="contact-title"><?php echo wp_kses_post("The page you were looking for<br> couldn't be found.","wpkites");?></h2>
                    <div class="not-found-btn">
                         <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-small btn-default"><?php esc_html_e('Back to Homepage', 'wpkites' ); ?></a>
                    </div>                
                </div>
            </div>
        </div>          
    </div>
</section>
<?php get_footer();?>