<?php

namespace HtMeaga\ElementorTemplate;
use Elementor\TemplateLibrary\Source_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Library_Source extends Source_Base {

	/**
	 * New library option key.
	 */
	public static $library_option_key = 'htmega_info_library_data';

	/**
	 * API info URL.
	 */
	public static $endpoint = 'https://wphtmega.com/library/wp-json/htmega/v1/templates';

	/**
	 * API get template content URL.
	 */
	public static $templateapi = 'https://wphtmega.com/library/wp-json/htmega/v1/templates/%s';

	function __construct(){
		if( is_plugin_active('htmega-pro/htmega_pro.php') && function_exists('htmega_pro_template_endpoint') ){
			$option_key = get_option('htmega_info_library_data');
			if(false !== $option_key){
				self::$library_option_key = 'htmega_pro_info_library_data';
	            delete_option( 'htmega_info_library_data' );
	        }
		}else{
			if(self::$library_option_key != 'htmega_info_library_data'){
				delete_option( self::$library_option_key );
			}
		}
	}

	// Setter Endpoint
    function set_api_endpoint( $endpoint ){
        self::$endpoint = $endpoint;
    }
    
    // Setter Template API
    function set_api_templateapi( $templateapi ){
        self::$templateapi = $templateapi;
    }

    // Get Endpoint
    public static function get_api_endpoint(){
        if( is_plugin_active('htmega-pro/htmega_pro.php') && function_exists('htmega_pro_template_endpoint') ){
            self::$endpoint = htmega_pro_template_endpoint();
        }
        return self::$endpoint;
    }

    // Get Template API
    public static function get_api_templateapi(){
        if( is_plugin_active('htmega-pro/htmega_pro.php') && function_exists('htmega_pro_template_url') ){
            self::$templateapi = htmega_pro_template_url();
        }
        return self::$templateapi;
    }

	/**
	 * [get_id] Get remote template ID.
	 * @return [string] The remote template ID.
	 */
	public function get_id() {
		return 'htmega-library';
	}
	
	/**
	 * [get_title] Get remote template title. 
	 * Retrieve the remote template title.
	 * @return [string] The remote template title.
	 */
	public function get_title() {
		return __( 'HT Mega Library', 'htmega-addons' );
	}
	
	/**
	 * [register_data] Register remote template data.
	 * Used to register custom template data like a post type, a taxonomy or any
	 * other data.
	 * @return [void]
	 */
	public function register_data() {}
	
	/**
	 * [get_items] Retrieve htmega templates from htmega library servers.
	 * @param  array  $args Optional. Nou used in htmega source.
	 * @return [array] Move templates.
	 */
	public function get_items( $args = [] ) {
		$library_data = self::get_library_data();

		$templates = [];
		if ( ! empty( $library_data['templates'] ) ) {
			foreach ( $library_data['templates'] as $template_data ) {
				$templates[] = $this->prepare_template( $template_data );
			}
		}
		return $templates;
	}

	/**
	 * [get_library_data] Retrieve the templates data from a htmega server.
	 * @param  boolean $force_update Optional. Whether to force the data update or
	 *  not. Default is false.
	 * @return [array] The templates data.
	 */
	public static function get_library_data( $force_update = false ) {
		self::get_info_library_data( $force_update );

		$data = get_option( self::$library_option_key );

		if ( empty( $data ) ) {
			return [];
		}

		return $data;
	}

	/**
	 * [get_info_library_data] This function notifies the user of upgrade notices, new templates and contributors.
	 * @param  boolean $force_update Optional. Whether to force the data retrieval or
	 * not. Default is false.
	 * @return [array|false] Info data, or false.
	 */
	private static function get_info_library_data( $force_update = false ) {
		$data = get_option( self::$library_option_key );

		if ( $force_update || false === $data ) {
			$timeout = ( $force_update ) ? 25 : 8;

			$response = wp_remote_get( self::get_api_endpoint(), [
				'timeout' => $timeout,
			] );

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				update_option( self::$library_option_key, [] );
				return false;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $data ) || ! is_array( $data ) ) {
				update_option( self::$library_option_key, [] );
				return false;
			}

			update_option( self::$library_option_key, $data, 'no' );
		}

		return $data;
	}
	
	/**
	 * [get_item Get remote template.] Retrieve a single remote template from Elementor.com servers.
	 * @param  [int] $template_id The template ID.
	 * @return [array] Remote template.
	 */
	public function get_item( $template_id ) {
		$templates = $this->get_items();

		return $templates[ $template_id ];
	}

	/**
	 * [save_item Save remote template.] Remote template from htmega servers cannot be saved on the
	 * database as they are retrieved from remote servers.
	 * @param  [array] $template_data Remote template data.
	 * @return \WP_Error 
	 */
	public function save_item( $template_data ) {
		return new \WP_Error( 'invalid_request', 'Cannot save template to a htmega source' );
	}

	/**
	 * [update_item Update remote template] Remote template from htmega servers cannot be updated on the database as they are retrieved from remote servers.
	 * @param  [array] $new_data New template data.
	 * @return \WP_Error
	 */
	public function update_item( $new_data ) {
		return new \WP_Error( 'invalid_request', 'Cannot update template to a htmega source' );
	}

	/**
	 * [delete_template Delete remote template.] Remote template from htmega servers cannot be deleted from the database as they are retrieved from remote servers.
	 * @param  [int] $template_id The template ID.
	 * @return \WP_Error 
	 */
	public function delete_template( $template_id ) {
		return new \WP_Error( 'invalid_request', 'Cannot delete template from a htmega source' );
	}
	
	/**
	 * [export_template Export remote template.] Remote template from htmega servers cannot be exported from the database as they are retrieved from remote servers.
	 * @param  [int] $template_id The template ID.
	 * @return \WP_Error
	 */
	public function export_template( $template_id ) {
		return new \WP_Error( 'invalid_request', 'Cannot export template from a htmega source' );
	}
	
	/**
	 * [get_data Get remote template data.] Retrieve the data of a single remote template from htmega servers.
	 * @param  array  $args    Custom template arguments.
	 * @param  string $context Optional. The context. Default is `display`.
	 * @return [array] Remote Template data.
	 */
	public function get_data( array $args, $context = 'display' ) {
		$data = self::get_template_content( $args['template_id'] );

		$data = json_decode( $data, true );

		if ( empty( $data ) || empty( $data['content'] ) ) {
			throw new \Exception( __( 'Template does not have any content', 'htmega-addons' ) );
		}

		$data['content'] = $this->replace_elements_ids( $data['content']['content'] );
		$data['content'] = $this->process_export_import_content( $data['content'], 'on_import' );

		$post_id = $args['editor_post_id'];
		$document = htmega_get_elementor()->documents->get( $post_id );

		if ( $document ) {
			$data['content'] = $document->get_elements_raw_data( $data['content'], true );
		}

		return $data;
	}

	/**
	 * [get_template_content Get template content.]
	 * @param  [int] $template_id The template ID.
	 * @return [array] The template content.
	 */
	public static function get_template_content( $template_id ) {
		if ( empty( $template_id ) ) {
			return;
		}

		$body = [
			'api_version'	=> HTMEGA_VERSION,
			'site_lang'		=> get_bloginfo( 'language' ),
			'home_url'		=> trailingslashit( home_url() ),
		];

		$content_url = sprintf( self::get_api_templateapi(), $template_id );
		$response = wp_remote_get(
			$content_url,
			[
				'body' => $body,
				'timeout' => 25
			]
		);

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * [prepare_template Prepare template items to match model]
	 * @param  array  $template_data [description]
	 * @return [array]  template list
	 */
	private function prepare_template( array $template_data ) {
		return [
			'template_id' => $template_data['id'],
			'title'       => $template_data['title'],
			'type'        => $template_data['type'],
			'thumbnail'   => $template_data['thumbnail'],
			'date'        => $template_data['human_date'],
			'tags'        => $template_data['tags'],
			'isPro'       => $template_data['isPro'],
			'url'         => $template_data['url'],
		];
	}

}