<?php

require_once( 'class-fortressdb-api-exception.php' );
require_once( 'class-fortressdb-api-not-found-exception.php' );
require_once( 'class-fortressdb-api-unauthorized-exception.php' );

/**
 * Class FortressDB_Wp_Api
 */
class FortressDB_Wp_Api {

	const MAX_ENUM_NAME_LENGTH = 255;

	protected $default_args = array();
	/**
	 * FortressDB API endpoint
	 *
	 * @var string
	 */
	private $_endpoint = FORTRESSDB_BACKEND_URL;

	/**
	 * FortressDB Plugin Token
	 *
	 * @var string
	 */
	private $_plugin_token = '';

	/**
	 * FortressDB user Token
	 *
	 * @var string
	 */
	private $_token = '';

	/**
	 * Last data sent to fortressdb
	 *
	 * @var array
	 */
	private $_last_data_sent = array();

	/**
	 * Last data received from fortressdb
	 *
	 * @var array
	 */
	private $_last_data_received = array();

	/**
	 * Last URL requested
	 *
	 * @var string
	 */
	private $_last_url_request = '';

	/**
	 * FortressDB_Wp_Api constructor.
	 */
	public function __construct() {
		add_filter( 'fortressdb_api_request_headers', array( $this, 'filter_default_api_headers' ), 10, 4 );
		add_filter( 'http_headers_useragent', array( $this, 'filter_user_agent' ) );
	}

	/**
	 * @param null $token
	 *
	 * @return $this|string
	 */
	public function accessToken( $token = null ) {
		if ( $token ) {
			$this->_token = $token;

			return $this;
		}

		if ( ! $this->_token ) {
			$settings     = fortressdb_get_plugin_options();
			$this->_token = fdbarg( $settings, 'shortAccessToken', '' );
		}

		return $this->_token;
	}

	/**
	 * @param string|null $token
	 *
	 * @return string
	 */
	public function pluginToken( $token = null ) {
		if ( $token ) {
			$this->_plugin_token = $token;

			return $this;
		}

		if ( ! $this->_plugin_token ) {
			$settings            = fortressdb_get_plugin_options();
			$this->_plugin_token = fdbarg( $settings, 'accessToken', '' );
		}

		return $this->_plugin_token;
	}

	/**
	 * Default filter for header
	 *
	 * its add / change Authorization header
	 * - on get access token it uses Basic realm of encoded client id and secret
	 * - on web API request it uses Bearer realm of access token which default of @param $headers
	 *
	 * @param $verb
	 * @param $path
	 * @param $args
	 *
	 * @return array
	 *
	 * @see   FortressDB_Wp_Api
	 *
	 */
	public function filter_default_api_headers( $headers, $verb, $path, $args ) {
		$headers['Authorization'] = 'Bearer ' . $this->accessToken();
		$headers['Content-Type']  = 'application/json';

		if ( isset( $args['headers'] ) ) {
			foreach ( $args['headers'] as $header => $value ) {
				$headers[ $header ] = $value;
			}
		}

		return $headers;
	}

	/**
	 * Add custom user agent on request
	 *
	 * @param $user_agent
	 *
	 * @return string
	 *
	 */
	public function filter_user_agent( $user_agent ) {
		$user_agent .= ' FortressDB/' . FORTRESSDB_VERSION;

		/**
		 * Filter user agent to be used by fortressdb api
		 *
		 * @param string $user_agent current user agent
		 */
		$user_agent = apply_filters( 'fortressdb_api_user_agent', $user_agent );

		return $user_agent;
	}

	/**
	 * @param       $verb
	 * @param       $path
	 * @param array $args
	 *
	 * @return array|mixed|object
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	private function safe_request( $verb, $path, $args = array() ) {
		try {
			$response = $this->request( $verb, $path, $args );
		} catch ( FortressDB_Wp_Api_Unauthorized_Exception $e ) {
			fortressdb_log( 'Unauthorized request -> Re-authorizing...' );
			try {
				$api = new FortressDB_Wp_Api();
				$api->use_short_token( true );
				$this->accessToken( $api->accessToken() );
				$response = $this->request( $verb, $path, $args );
			} catch ( Exception $e ) {
				throw new FortressDB_Wp_Api_Exception( $e->getMessage() );
			}
			fortressdb_log( 'Re-authorizing... -> OK' );
		}

		return $response;
	}

	/**
	 * HTTP Request
	 *
	 * @param string $verb
	 * @param        $path
	 * @param array  $args
	 *
	 * @return array|mixed|object
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 * @throws FortressDB_Wp_Api_Unauthorized_Exception
	 *
	 */
	private function request( $verb, $path, $args = array() ) {
		$args = array_merge( $this->default_args, $args );

		$verb = $verb ?: 'GET';
		$url  = $this->baseUrl( $path );

		/**
		 * Filter fortressdb url to be used on sending api request
		 *
		 * @param string $url  full url with scheme
		 * @param string $verb `GET` `POST` `PUT` `DELETE` `PATCH`
		 * @param string $path requested path resource
		 * @param array  $args argument sent to this function
		 */
		$url = apply_filters( 'fortressdb_api_url', $url, $verb, $path, $args );

		$this->_last_url_request = $url;
		$headers                 = array();

		/**
		 * Filter fortressdb headers to sent on api request
		 *
		 * @param array  $headers
		 * @param string $verb `GET` `POST` `PUT` `DELETE` `PATCH`
		 * @param string $path requested path resource
		 * @param array  $args argument sent to this function
		 */
		$headers = apply_filters( 'fortressdb_api_request_headers', $headers, $verb, $path, $args, $this );
		if ( isset( $args['headers'] ) ) {
			unset( $args['headers'] );
		}

		$_args        = array(
			'method'  => $verb,
			'headers' => $headers,
			'body'    => array(),
		);
		$request_data = $args;

		/**
		 * Filter fortressdb request data to be used on sending api request
		 *
		 * @param array  $request_data it will be `http_build_query`-ed when `GET` or `wp_json_encode`-ed otherwise
		 * @param string $verb         `GET` `POST` `PUT` `DELETE` `PATCH`
		 * @param string $path         requested path resource
		 */
		$args = apply_filters( 'fortressdb_api_request_data', $request_data, $verb, $path );

		if ( 'GET' === $verb ) {
			$url .= '?' . http_build_query( $args );
		} else {
			$_args['body'] = json_encode( $args );
		}

		$this->_last_data_sent = $args;
		$response              = wp_remote_request( $url, $_args );
		$wp_response           = $response;
		
		if ( ! $response ) {
			$this->_last_data_received =
				'Failed to process request, make sure you authorized FortressDB and your server has internet connection.';
			throw new FortressDB_Wp_Api_Exception( $this->_last_data_received );
		}

		if ( is_wp_error( $response ) ) {
			$this->_last_data_received = $response;
			throw new FortressDB_Wp_Api_Exception( $response->get_error_message() );
		}
		
		$body = wp_remote_retrieve_body( $response );
		
		if ( isset( $response['response']['code'] ) ) {
			$status_code = $response['response']['code'];
			$msg         = '';
			if ( $status_code >= 400 ) {
				if ( isset( $body ) ) {
					try {
						// if $body is HTML
						$m = array();
						preg_match( '/>error:[\s]*([^<]*)[\s]*</mi', $body, $m );
						if ( count( $m ) > 0 ) {
							$msg = $m[1];
						} else {
							preg_match( '/<body>[\s\S]*<p>[\s]*([\s\S]*)[\s]*<\/p>/mi', $body, $m );
							if ( count( $m ) > 0 ) {
								$msg = $m[1];
							} else {
								// if $body is JSON
								$body = json_decode( $body, true );
								$msg  = isset( $body['message'] ) ? $body['message'] : $response['response']['message'];
							}
						}
					} catch ( Exception $e ) {
						$msg = $response['response']['message'];
					}
				} elseif ( isset( $response['response']['message'] ) ) {
					$msg = $response['response']['message'];
				}

				$this->_last_data_received = sprintf( 'Request processing failed: %s', $msg );

				if ( 401 === $status_code ) {
					throw new FortressDB_Wp_Api_Unauthorized_Exception( $this->_last_data_received );
				}

				if ( 404 === $status_code ) {
					throw new FortressDB_Wp_Api_Not_Found_Exception( $this->_last_data_received );
				}

				throw new FortressDB_Wp_Api_Exception( $this->_last_data_received );
			}
		}

		// probably silent mode
		if ( ! empty( $body ) ) {
			$response = json_decode( $body, true );
		}

		/**
		 * Filter fortressdb api response returned to addon
		 *
		 * @param mixed          $response    original wp remote request response or decoded body if available
		 * @param string         $body        original content of http response's body
		 * @param array|WP_Error $wp_response original wp remote request response
		 */
		$response = apply_filters( 'fortressdb_api_response', $response, $body, $wp_response );

		$this->_last_data_received = $response;

		return $response;
	}

	public function baseUrl( $path = '' ) {
		return trailingslashit( $this->_endpoint ) . $path ?: '';
	}

	/**
	 * @param bool $force
	 *
	 * @return FortressDB_Wp_Api
	 *
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function use_short_token( $force = false ) {
		$settings     = fortressdb_get_plugin_options();
		$access_token = fdbarg( $settings, 'shortAccessToken', '' );
		if ( is_array( $access_token ) ) {
			$access_token = '';
		}

		if ( $force || empty( $access_token ) ) {
			$args = array(
				'data'    => array(
					array(
						'permissions' => array(
							'isAdmin' => true,
							'tables'  => new stdClass(),
						),
						'userId'      => get_current_user_id(),
					),
				),
				'context' => array(
					'locale' => fortressdb_user_locale(),
				),
			);

			try {
				$this->accessToken( $this->pluginToken() );
				$res = $this->request( 'POST', 'auth/subscribe', $args );

				if ( ! isset( $res['accessToken'] ) ) {
					throw new FortressDB_Wp_Api_Exception( 'Could not retrieve Access Token' );
				}

				$access_token = $res['accessToken'];
				$settings     = fortressdb_get_plugin_options();
				fdbars( $settings, 'shortAccessToken', $access_token );
				fortressdb_update_plugin_options( $settings );
			} catch ( FortressDB_Wp_Api_Exception $e ) {
				fortressdb_log( $e->getMessage(), FORTRESSDB_LOG_LEVEL_ERROR );
				throw $e;
			}
		}

		return $this->accessToken( $access_token );
	}

	/**
	 * Send POST Request
	 *
	 * @param string $path
	 * @param array  $args
	 *
	 * @return array|mixed|object
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function post( $path, $args = array() ) {
		return $this->safe_request( 'POST', $path, $args );
	}

	/**
	 * Send GET Request
	 *
	 * @param string $path
	 * @param array  $args
	 *
	 * @return array|mixed|object
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function get( $path, $args = array() ) {
		$args['headers']['X-HTTP-Method-Override'] = 'GET';

		return $this->safe_request( 'POST', $path, $args );
	}

	/**
	 * Send PUT Request
	 *
	 * @param string $path
	 * @param array  $args
	 *
	 * @return array|mixed|object
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function put( $path, $args = array() ) {
		return $this->safe_request( 'PUT', $path, $args );
	}

	/**
	 * Send DELETE Request
	 *
	 * @param string $path
	 * @param array  $args
	 *
	 * @return array|mixed|object
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function delete( $path, $args = array() ) {
		return $this->safe_request( 'DELETE', $path, $args );
	}

	/**
	 * Get last data sent
	 *
	 * @return array
	 */
	public function get_last_data_sent() {
		return $this->_last_data_sent;
	}

	/**
	 * Get last data received
	 *
	 * @return array
	 */
	public function get_last_data_received() {
		return $this->_last_data_received;
	}

	/**
	 * Get last data received
	 *
	 * @return string
	 */
	public function get_last_url_request() {
		return $this->_last_url_request;
	}

	/**
	 * @param array      $data
	 * @param string|int $table_name
	 *
	 * @return mixed
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function post_objects( $data, $table_name ) {
		if ( ! isset( $data[0] ) ) {
			$data = array( $data );
		}

		foreach ( $data as $index => $package ) {
			foreach ( $package as $key => $value ) {
				if ( is_string( $value ) ) {
					$data[ $index ][ $key ] = stripslashes( $value );
				}
			}
		}

		$payload = array(
			'context' => array(
				'tableName' => $table_name,
			),
			'data'    => $data,
		);

		$response = $this->post( 'objects', $payload );

		if ( empty( $response ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Error occurred when objects creation' );
		}

		return $response[0];
	}

	/**
	 * @param string $uuid
	 *
	 * @return array|null
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function get_metadata( $uuid ) {
		$payload  = array(
			'filter' => array(
				'where' => array(
					'operator' => '=',
					'args'     => array( 'tableName', $uuid ),
				),
			),
		);
		$response = $this->get( 'metadata', $payload );

		return fdbarg( $response, array( 'rows', 0 ) );
	}

	/**
	 * @param         $name
	 * @param string  $description
	 * @param array   $options
	 *
	 * @return mixed
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function post_metadata( $name, $description = '', $options = array() ) {
		$payload  = array(
			'data' => array(
				array(
					'name'        => stripslashes( $name ),
					'description' => stripslashes( $description ),
					'options'     => $options ?: new stdClass(),
				),
			),
		);
		$response = $this->post( 'metadata', $payload );

		if ( empty( $response ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Error occurred when table creation' );
		}

		return $response[0];
	}

	/**
	 * @param array $data
	 *
	 * @return array|null
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function update_metadata( $data ) {
		if ( ! isset( $data['id'] ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Invalid argument \'data\'' );
		}

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$data[ $key ] = stripslashes( $value );
			}
		}

		$payload = array( 'data' => array( $data ) );
		$this->put( 'metadata', $payload );

		return $this->get_metadata( $data['tableName'] );
	}

	/**
	 * @param string $uuid
	 *
	 * @return array|null
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function delete_metadata( $uuid ) {
		$response = $this->delete( 'metadata', array(
			'filter' => array(
				'where' => array(
					'operator' => '=',
					'args'     => array( 'tableName', $uuid ),
				),
			),
		) );

		return fdbarg( $response, array( 'rows', 0 ) );
	}

	/**
	 * @param int $id
	 *
	 * @return array|null
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function get_enum( $id ) {
		$payload  = array(
			'filter' => array(
				'where' => array(
					'operator' => '=',
					'args'     => array( 'id', $id ),
				),
			),
		);
		$response = $this->get( 'enums', $payload );

		return fdbarg( $response, array( 'rows', 0 ) );
	}

	/**
	 * @param string $name
	 * @param string $description
	 * @param array  $options
	 *
	 * @return mixed
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function post_enum( $name, $description = '', $options = array() ) {
		// sanitize values
		$rnd_length = 10;
		if ( mb_strlen($name) > self::MAX_ENUM_NAME_LENGTH ) {
			$name = mb_substr($name, 0, self::MAX_ENUM_NAME_LENGTH - $rnd_length) . bin2hex(random_bytes($rnd_length / 2));
		}

		$payload  = array(
			'data' => array(
				array(
					'name'        => stripslashes( $name ),
					'description' => stripslashes( $description ),
					'options'     => $options ?: new stdClass(),
				),
			),
		);
		$response = $this->post( 'enums', $payload );

		if ( empty( $response ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Error occurred when enum creation' );
		}

		return $response[0];
	}

	/**
	 * @param array $data
	 *
	 * @return mixed|null
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function update_enum( $data ) {
		if ( ! isset( $data['id'] ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Invalid argument \'data\'' );
		}

		// sanitize values
		if (isset($data['name'])) {
			$rnd_length = 10;
			if (mb_strlen($data['name']) > self::MAX_ENUM_NAME_LENGTH) {
				$data['name'] = mb_substr($data['name'], 0, self::MAX_ENUM_NAME_LENGTH - $rnd_length) . bin2hex(random_bytes($rnd_length / 2));
			}
		}

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$data[ $key ] = stripslashes( $value );
			}
		}

		$payload = array(
			'data' => array( $data ),
		);

		$this->put( 'enums', $payload );

		$response = $this->get_enum( $data['id'] );

		if ( empty( $response ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Error occurred when enums updating' );
		}

		return $response;
	}

	/**
	 * @param array $ids
	 *
	 * @return mixed
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function delete_enums( $ids ) {
		$payload  = array(
			'filter' => array(
				'where' => array( "id" => $ids )
			),
		);
		$response = $this->delete( 'enums', $payload );

		if ( empty( $response ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Error occurred when enums deleting' );
		}

		return $response[0];
	}

	/**
	 * @param int $enum_id
	 *
	 * @return array
	 * @throws FortressDB_Wp_Api_Exception
	 * @throws FortressDB_Wp_Api_Not_Found_Exception
	 */
	public function get_enum_values( $enum_id ) {
		$payload  = array(
			'context' => array( 'enumId' => $enum_id ),
		);
		$response = $this->get( 'enum_values', $payload );

		return fdbarg( $response, 'rows', array() );
	}

	/**
	 * @param int   $enum_id
	 * @param array $data
	 *
	 * @return mixed
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function post_enum_values( $enum_id, $data ) {
		if ( ! isset( $data[0] ) ) {
			$data = array( $data );
		}

		foreach ( $data as $index => $package ) {
			foreach ( $package as $key => $value ) {
				if ( is_string( $value ) ) {
					$data[ $index ][ $key ] = stripslashes( $value );
				}
				if ($key === 'label') {
					if (mb_strlen($data[$index]['label']) > self::MAX_ENUM_NAME_LENGTH) {
						$data[$index]['label'] = mb_substr($data[$index]['label'], 0, self::MAX_ENUM_NAME_LENGTH);
					}
				}
			}
		}

		$payload  = array(
			'data'    => $data,
			'context' => array(
				'enumId' => $enum_id,
			),
		);
		$response = $this->post( 'enum_values', $payload );

		if ( empty( $response ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Error occurred when enumValues creation' );
		}

		return $response[0];
	}

	/**
	 * @param int   $enum_id
	 * @param array $data
	 *
	 * @return mixed
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function update_enum_values( $enum_id, $data ) {
		if ( ! isset( $data[0] ) ) {
			$data = array( $data );
		}

		foreach ( $data as $index => $package ) {
			foreach ( $package as $key => $value ) {
				if ( is_string( $value ) ) {
					$data[ $index ][ $key ] = stripslashes( $value );
				}
				if ($key === 'label') {
					if (mb_strlen($data[$index]['label']) > self::MAX_ENUM_NAME_LENGTH) {
						$data[$index]['label'] = mb_substr($data[$index]['label'], 0, self::MAX_ENUM_NAME_LENGTH);
					}
				}
			}
		}

		$payload  = array(
			'data'    => $data,
			'context' => array(
				'enumId' => $enum_id,
			),
		);
		$response = $this->put( 'enum_values', $payload );

		if ( empty( $response ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Error occurred when enumValues updating' );
		}

		return $response[0];
	}

	/**
	 * @param int   $enum_id
	 * @param int[] $ids
	 *
	 * @return mixed
	 * @throws FortressDB_Wp_Api_Exception
	 */
	public function delete_enum_values( $enum_id, $ids = array() ) {
		$payload = array(
			'context' => array(
				'enumId' => $enum_id,
			),
		);

		$ids = is_array($ids) ? $ids : array($ids);

		if ( ! empty( $ids ) ) {
			$payload['filter'] = array(
				'where' => array( 'id' => $ids )
			);
		}
		$response = $this->delete( 'enum_values', $payload );

		if ( empty( $response ) ) {
			throw new FortressDB_Wp_Api_Exception( 'Error occurred when enumValues deleting' );
		}

		return $response;
	}
}
