<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package wpkites
 */
get_header();?>
<section class="page-section-space blog bg-default">
    <div class="container<?php echo esc_html(wpkites_blog_post_container());?>">
        <div class="row">
            <?php
            if ( is_active_sidebar( 'sidebar-1' ) ):        
                echo '<div class="col-lg-8 col-md-7 col-sm-12 standard-view">';
            else:
                echo '<div class="col-lg-12 col-md-12 col-sm-12 standard-view">';   
            endif; 

            if (have_posts()): 
                    while (have_posts()): the_post();
                        if(! function_exists( 'wpkites_plus_activate' ) ){
                            get_template_part( 'template-parts/content');
                        }
                        else{
                            include(WPKITESP_PLUGIN_DIR.'/inc/template-parts/content.php');
                        }
                    endwhile;
                else:
                    get_template_part('template-parts/content', 'none');
                endif;
                // pagination
                    do_action('wpkites_post_navigation');
                // pagination
                ?>
            </div>	
            <?php get_sidebar();?>
        </div>
    </div>
</section>
<?php get_footer(); ?>