<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
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
                echo '<div class="col-lg-8 col-md-7 col-sm-12">';
            else:
                echo '<div class="col-lg-12 col-md-12 col-sm-12">';   
            endif;
                if (have_posts()): 
                    while (have_posts()): the_post();
                        if(! function_exists( 'wpkites_plus_activate' ) ){
                            get_template_part( 'template-parts/content');
                        }
                        else{
                            if(get_theme_mod('post_nav_style_setting','pagination')=='pagination'):
                                include(WPKITESP_PLUGIN_DIR.'/inc/template-parts/content.php' );
                            endif;
                        }
                    endwhile;
                else:
                    get_template_part('template-parts/content', 'none');
                endif;

                // pagination
                if ( ! function_exists( 'wpkites_plus_activate' ) ){
                 do_action('wpkites_post_navigation');
                }else{
                 do_action('wpkites_plus_post_navigation');
                }
                // pagination                
                
                echo '</div>';

            get_sidebar();?>
        </div>
    </div>
</section>
<?php get_footer(); ?>