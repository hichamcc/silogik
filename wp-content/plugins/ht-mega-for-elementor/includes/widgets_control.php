<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class HTMega_Widgets_Control{

    private static $instance = null;
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct(){
        // Register custom category
        add_action( 'elementor/elements/categories_registered', [ $this, 'add_category' ] );
        // Add Plugin actions
        // Init Widgets
        if ( htmega_is_elementor_version( '>=', '3.5.0' ) ) {
            add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ] );
        }else{
            add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
        }
        // Add custom control
        add_action( 'elementor/controls/controls_registered', [ $this, 'initiliaze_custom_control' ] );
    }

    public function initiliaze_custom_control(){
        if ( file_exists( HTMEGA_ADDONS_PL_PATH.'admin/include/custom-control/preset-select.php' ) ) {
            \Elementor\Plugin::instance()->controls_manager->register_control('htmega-preset-select', new \HtMega\Preset\Preset_Select);
        }
    }

    // Add custom category.
    public function add_category( $elements_manager ) {
        $elements_manager->add_category(
            'htmega-addons',
            [
                'title' => __( 'HTMega Addons', 'htmega-addons' ),
                'icon' => 'fa fa-snowflake',
            ]
        );
    }

    public function init_widgets(){

        $widget_list = $this->get_widget_list();
        $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
        
        foreach($widget_list as $option_key => $option){

            $widget_path = $option['is_pro'] ? HTMEGA_ADDONS_PL_PATH_PRO : HTMEGA_ADDONS_PL_PATH;

            if(strpos($option['title'], ' ') !== false){
                $widget_file_name = strtolower(str_replace(' ', '_', $option['title']));
                $widget_class = $option['is_pro'] ? 'HTMegaPro\Elementor\Widget\HTMega_'. str_replace(' ', '_', $option['title']).'_Element' : "\Elementor\HTMega_Elementor_Widget_" . str_replace(' ', '_', $option['title']);
            }else{
                $widget_file_name = strtolower($option['title']);
                $widget_class =$option['is_pro'] ? 'HTMegaPro\Elementor\Widget\HTMega_'. $option['title'] .'_Element' : "\Elementor\HTMega_Elementor_Widget_" . $option['title'];
            }

            if(isset($option['third-party-resource'])){
                $widget_status = is_plugin_active($option['third-party-resource']) && ( htmega_get_option( $option_key, $option['option-tab'], 'on' ) === 'on' ) && file_exists( $widget_path.'includes/widgets/htmega_'.$widget_file_name.'.php' ) ? true : false ;
            }else{
                $widget_status = ( htmega_get_option( $option_key, $option['option-tab'], 'on' ) === 'on' ) && file_exists( $widget_path.'includes/widgets/htmega_'.$widget_file_name.'.php' ) ? true : false ;
            }

            if ( $widget_status ){

                require_once $widget_path.'includes/widgets/htmega_'.$widget_file_name.'.php';

                if ( htmega_is_elementor_version( '>=', '3.5.0' ) ){
                    $widgets_manager->register( new $widget_class() );
                }else{
                    $widgets_manager->register_widget_type( new $widget_class() );
                }
                
            }

        }
    }
    
    private function get_widget_list(){

        $widget_list =[
            'accordion'=> [
                'title' => esc_html__('Accordion','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'animatesectiontitle'=> [
                'title' => esc_html__('Animated Heading','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'addbanner' => [
                'title' => esc_html__('Add Banner','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'specialadsbanner' =>[
                'title' => esc_html__('Special day Banner','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'blockquote' =>[
                'title' => esc_html__('Blockquote','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'brandlogo' =>[
                'title' => esc_html__('Brand','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'businesshours' =>[
                'title' => esc_html__('Business Hours','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'button' =>[
                'title' => esc_html__('Button','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'calltoaction' =>[
                'title' => esc_html__('Call To Action','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'carousel' =>[
                'title' => esc_html__('Carousel','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'countdown' =>[
                'title' => esc_html__('Countdown','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'counter' =>[
                'title' => esc_html__('Counter','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'customevent' =>[
                'title' => esc_html__('Custom_Event','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'dualbutton' =>[
                'title' => esc_html__('Double Button','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'dropcaps' =>[
                'title' => esc_html__('Dropcaps','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'flipbox' =>[
                'title' => esc_html__('Flip Box','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'galleryjustify' =>[
                'title' => esc_html__('Gallery Justify','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'googlemap' =>[
                'title' => esc_html__('GoogleMap','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'imagecomparison' =>[
                'title' => esc_html__('Image Comparison','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'imagegrid' =>[
                'title' => esc_html__('Image Grid','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'imagemagnifier' =>[
                'title' => esc_html__('Image Magnifier','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'imagemarker' =>[
                'title' => esc_html__('ImageMarker','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'imagemasonry' =>[
                'title' => esc_html__('Image_Masonry','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'inlinemenu' =>[
                'title' => esc_html__('InlineMenu','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'instagram' =>[
                'title' => esc_html__('Instagram','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'lightbox' =>[
                'title' => esc_html__('Lightbox','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'modal' =>[
                'title' => esc_html__('Modal','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'newtsicker' =>[
                'title' => esc_html__('Newsticker','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'notify' =>[
                'title' => esc_html__('Notify','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'offcanvas' =>[
                'title' => esc_html__('Offcanvas','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'panelslider' =>[
                'title' => esc_html__('Panel Slider','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'popover' =>[
                'title' => esc_html__('Popover','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'postcarousel' =>[
                'title' => esc_html__('Post Carousel','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'postgrid' =>[
                'title' => esc_html__('PostGrid','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'postgridtab' =>[
                'title' => esc_html__('Post Grid Tab','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'postslider' =>[
                'title' => esc_html__('Post Slider','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'pricinglistview' =>[
                'title' => esc_html__('Pricing List View','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'pricingtable' =>[
                'title' => esc_html__('Pricing Table','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'progressbar' =>[
                'title' => esc_html__('Progress Bar','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'scrollimage' =>[
                'title' => esc_html__('Scroll Image','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'scrollnavigation' =>[
                'title' => esc_html__('Scroll Navigation','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'search' =>[
                'title' => esc_html__('Search','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'sectiontitle' =>[
                'title' => esc_html__('Section_Title','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'service' =>[
                'title' => esc_html__('Service','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'singlepost' =>[
                'title' => esc_html__('SinglePost','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'thumbgallery' =>[
                'title' => esc_html__('Slider Thumb Gallery','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'socialshere' =>[
                'title' => esc_html__('SocialShere','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'switcher' =>[
                'title' => esc_html__('Switcher','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'tabs' =>[
                'title' => esc_html__('Tabs','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'datatable' =>[
                'title' => esc_html__('Data Table','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'teammember' =>[
                'title' => esc_html__('TeamMember','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'testimonial' =>[
                'title' => esc_html__('Testimonial','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'testimonialgrid' =>[
                'title' => esc_html__('Testimonial Grid','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'toggle' =>[
                'title' => esc_html__('Toggle','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'tooltip' =>[
                'title' => esc_html__('Tooltip','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'twitterfeed' =>[
                'title' => esc_html__('Twitter_Feed','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'userloginform' =>[
                'title' => esc_html__('User Login Form','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'userregisterform' =>[
                'title' => esc_html__('User Register Form','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'verticletimeline' =>[
                'title' => esc_html__('Verticle Time Line','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'videoplayer' =>[
                'title' => esc_html__('VideoPlayer','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'workingprocess' =>[
                'title' => esc_html__('Working Process','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'errorcontent' =>[
                'title' => esc_html__('ErrorContent','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'template_selector' =>[
                'title' => esc_html__('Template Selector','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],
            'weather' =>[
                'title' => esc_html__('Weather','htmega-addons'),
                'option-tab'=>'htmega_element_tabs', 
                'is_pro'   => false,
            ],

            'bbpress' => [   
                'title' => esc_html__( 'Bbpress', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs', 
                'third-party-resource' => 'bbpress/bbpress.php',
                'is_pro'=>false 
            ],

            'bookedcalender' => [   
                'title' => esc_html__( 'Booked Calender', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs', 
                'third-party-resource' => 'bbpress/bbpress.php',
                'is_pro'=>false 
            ],

            'buddypress' => [   
                'title' => esc_html__( 'Buddy Press', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'buddypress/bp-loader.php', 
                'is_pro'=>false 
            ],

            'calderaform' => [   
                'title' => esc_html__( 'Caldera Form', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'caldera-forms/caldera-core.php', 
                'is_pro'=>false 
            ],

            'contactform' => [   
                'title' => esc_html__( 'Contact Form Seven', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'contact-form-7/wp-contact-form-7.php', 
                'is_pro'=>false 
            ],

            'downloadmonitor' => [   
                'title' => esc_html__( 'Download Monitor', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'download-monitor/download-monitor.php', 
                'is_pro'=>false 
            ],

            'easydigitaldownload' => [   
                'title' => esc_html__( 'Easy Digital Download', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'easy-digital-downloads/easy-digital-downloads.php', 
                'is_pro'=>false 
            ],

            'gravityforms' => [   
                'title' => esc_html__( 'Gravity Forms', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'gravityforms/gravityforms.php', 
                'is_pro'=>false 
            ],

            'instragramfeed' => [   
                'title' => esc_html__( 'Instragram Feed', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'instagram-feed/instagram-feed.php', 
                'is_pro'=>false 
            ],

            'jobmanager' => [   
                'title' => esc_html__( 'Job Manager', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'wp-job-manager/wp-job-manager.php', 
                'is_pro'=>false 
            ],
            'layerslider' => [   
                'title' => esc_html__( 'Layer Slider', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'LayerSlider/layerslider.php', 
                'is_pro'=>false 
            ],

            'mailchimpwp' => [   
                'title' => esc_html__( 'Mailchimp Wp', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'mailchimp-for-wp/mailchimp-for-wp.php', 
                'is_pro'=>false 
            ],

            'ninjaform' => [   
                'title' => esc_html__( 'Ninja Form', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'ninja-forms/ninja-forms.php', 
                'is_pro'=>false 
            ],

           'quforms' => [   
                'title' => esc_html__( 'QUforms', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'quform/quform.php', 
                'is_pro'=>false 
            ],

            'wpforms' => [   
                'title' => esc_html__( 'WPforms', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'wpforms-lite/wpforms.php', 
                'is_pro'=>false 
            ],

            'revolution' => [   
                'title' => esc_html__( 'Revolution Slider', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'revslider/revslider.php', 
                'is_pro'=>false 
            ],

            'tablepress' => [   
                'title' => esc_html__( 'Tablepress', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'tablepress/tablepress.php', 
                'is_pro'=>false 
            ],

            'wcaddtocart' => [   
                'title' => esc_html__( 'WC Add to Cart', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'woocommerce/woocommerce.php', 
                'is_pro'=>false 
            ],

            'categories' => [   
                'title' => esc_html__( 'WC Categories', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'woocommerce/woocommerce.php', 
                'is_pro'=>false 
            ],

            'wcpages' => [   
                'title' => esc_html__( 'WC Element Pages', 'htmega-addon' ),
                'option-tab'=> 'htmega_thirdparty_element_tabs',
                'third-party-resource' => 'woocommerce/woocommerce.php', 
                'is_pro'=>false 
            ],
    
        ];

        return apply_filters( 'htmega_widget_list', $widget_list );
    }

}
HTMega_Widgets_Control::instance();