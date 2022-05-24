<?php

// theme sub header breadcrumb functions
if (!function_exists('wpkites_breadcrumbs')):
    function wpkites_breadcrumbs() {
        global $post;
        $homeLink = home_url('/');
        $hide_show_banner = get_theme_mod('banner_enable', true);
        $breadcrumb_overlay=get_theme_mod('breadcrumb_overlay_section_color','rgba(0,0,0,0.6)');

        if ($hide_show_banner == true) {?>
            <section class="page-title-section" <?php if (get_header_image()) { ?> style="background:url('<?php header_image(); ?>')" <?php } ?>>		
                <div class="breadcrumb-overlay"<?php if(get_theme_mod('breadcrumb_image_overlay',true)==true){ echo 'style="background-color:'.esc_attr($breadcrumb_overlay).'"';}?> >
                <div class="container">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                        <?php 
                        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                        if (is_home() || is_front_page()) { 
                            if( ! function_exists( 'spiceb_activate' ) ) {
                                if(get_option('show_on_front')=='page'){
                                    if(is_front_page()){?>
                                        <div class="page-title">
                                            <h3 class="theme-dtl"><?php esc_html_e('Welcome to ', 'wpkites'); echo esc_html(get_bloginfo( 'name' )); ?></h3>
                                            <h1><?php echo esc_html(get_the_title( get_option('page_on_front', true) )); ?></h1>
                                        </div>
                                    <?php   
                                    }
                                    else if(is_home()){?>
                                        <div class="page-title">
                                            <h3 class="theme-dtl"><?php esc_html_e('Welcome to ', 'wpkites'); echo esc_html(get_bloginfo( 'name' )); ?></h3>
                                            <h1><?php echo esc_html(get_the_title( get_option('page_for_posts', true) )); ?></h1>
                                        </div>          
                                    <?php
                                    }
                                }
                                elseif(get_option('show_on_front')=='posts'){?>
                                    <div class="page-title">
                                        <h3 class="theme-dtl"><?php esc_html_e('Welcome to ', 'wpkites'); echo esc_html(get_bloginfo( 'name' )); ?></h3>
                                        <h1><?php echo wp_kses_post(get_theme_mod('blog_page_title_option', __('Home', 'wpkites' ))); ?></h1>
                                    </div>
                                <?php
                                }   
                            }
                            //else condition will run when Spice Box plugin is active
                            else{
                                if(get_option('show_on_front')=='posts'){?>
                                    <div class="page-title">
                                        <h3 class="theme-dtl"><?php esc_html_e('Welcome to ', 'wpkites'); echo esc_html(get_bloginfo( 'name' )); ?></h3>
                                        <h1><?php echo wp_kses_post(get_theme_mod('blog_page_title_option', __('Home', 'wpkites' ))); ?></h1>
                                    </div> 
                                <?php
                                }else{
                                    if(is_front_page()){?>
                                        <div class="page-title">
                                            <h3 class="theme-dtl"><?php esc_html_e('Welcome to ', 'wpkites'); echo esc_html(get_bloginfo( 'name' )); ?></h3>
                                            <h1><?php echo esc_html(get_the_title( get_option('page_on_front', true) )); ?></h1>
                                        </div>
                                    <?php   
                                    }else if(is_home()){?>
                                        <div class="page-title">
                                            <h3 class="theme-dtl"><?php esc_html_e('Welcome to ', 'wpkites'); echo esc_html(get_bloginfo( 'name' )); ?></h3>
                                            <h1><?php echo esc_html(get_the_title( get_option('page_for_posts', true) )); ?></h1>
                                        </div>          
                                    <?php
                                    }
                                }   
                            }
                        } 
                        else{ ?>                   
                            <div class="page-title">
                            <h3 class="theme-dtl"><?php esc_html_e('Welcome to ', 'wpkites'); echo esc_html(get_bloginfo( 'name' )); ?></h3>
                                            
                            <?php if (is_search()){
                                    echo '<h1>'. get_search_query() .'</h1>';
                            }
                            else if(is_404())
                            {
                                echo '<h1>'. esc_html__('Error 404','wpkites' ) .'</h1>';  
                            }
                            else if(is_category())
                            {
                                echo '<h1>'. ( esc_html__('Category:&nbsp;','wpkites' ).single_cat_title( '', false ) ) .'</h1>';   
                            }
                            else if ( !is_single() && !is_page() && get_post_type() != 'post' && !is_404() ){ 
                                if ( class_exists( 'WooCommerce' ) ){
                                    if(is_shop()){ ?>
                                        <h1><?php woocommerce_page_title(); ?></h1>
                                        <?php 
                                        }   
                                     }
                            }
                            elseif( is_tag() )
                            {
                                echo '<h1>'. ( esc_html__('Tag:&nbsp;','wpkites' ) .single_tag_title( '', false ) ) .'</h1>';
                            }
                            else if(is_archive())
                            {   
                            the_archive_title( '<h1>', '</h1>' ); 
                            }
                            else
                            { ?>
                                <h1><?php the_title(''); ?></h1>
                            <?php } ?>
                            </div>  
                        <?php } ?>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12">
                        <?php
                        $breadcrumb_enable = get_theme_mod('breadcrumb_setting_enable',true);
                            if($breadcrumb_enable == true){ 
                                if ( function_exists('yoast_breadcrumb') ) {
                                    $wpseo_titles=get_option('wpseo_titles');
                                    if($wpseo_titles['breadcrumbs-enable']==true){
                                        echo '<ul class="page-breadcrumb text-center">';
                                        echo '<li>';
                                        echo '</li>';
                                    $breadcrumbs = yoast_breadcrumb("","",false);
                                    echo wp_kses_post($breadcrumbs);
                                    echo '</ul>';
                                    }   
                                }
                            }?>
                        </div>

                    </div>	
                </div>
            </div>
            </section>
            <div class="page-seperate"></div>
        <?php } else { ?><div class="page-seperate"></div><?php
        }
    }

endif;
?>