<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package wpkites
 */
get_header();?>
<section class="page-section-space blog bg-default">
    <div class="container<?php echo esc_html(wpkites_single_post_container());?>">
        <div class="row">           
            <?php
            if ( is_active_sidebar( 'sidebar-1' ) ):        
                echo '<div class="col-lg-8 col-md-7 col-sm-12">';
            else:
                echo '<div class="col-lg-12 col-md-12 col-sm-12">';   
            endif;
                while (have_posts()): the_post();
                    if ( ! function_exists( 'wpkites_plus_activate' ) ){
                        get_template_part('template-parts/content', 'single');
                    }
                    else{
                        include(WPKITESP_PLUGIN_DIR.'/inc/template-parts/content-single.php');
                    }
                endwhile;
                if(function_exists( 'wpkites_plus_activate' )):
                    if(get_theme_mod('wpkites_enable_related_post',true ) ===true ):
                        include(WPKITESP_PLUGIN_DIR.'/inc/template-parts/related-posts.php');
                    endif;
                endif;
                if (get_theme_mod('wpkites_enable_single_post_admin_details', true) === true):
                    get_template_part('template-parts/auth-details');
                endif;

                // If comments are open or we have at least one comment, load up the comment template.
                if (comments_open() || get_comments_number()) : comments_template();
                endif;
                
                echo '</div>';             
            get_sidebar();?>
        </div>
    </div>
</section>
<?php get_footer(); ?>