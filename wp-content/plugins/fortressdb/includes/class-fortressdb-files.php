<?php

/**
 * Class oFile
 */
class FortressDB_File {
	private $name;
	private $mime;
	private $content;
	private $size;
	
	/**
	 * oFile constructor.
	 *
	 * @param      $name
	 * @param null $mime
	 * @param null $content
	 *
	 * @throws Exception
	 */
	public function __construct( $name, $mime = null, $content = null ) {
		if ( is_null( $content ) ) {
			$info = pathinfo( $name );
			if ( ! empty( $info['basename'] ) && is_readable( $name ) ) {
				$this->name = $info['basename'];
				$this->mime = mime_content_type( $name );
				$content    = file_get_contents( $name );
				if ( $content !== false ) {
					$this->content = $content;
				} else {
					throw new Exception( 'Can`t get content - "' . $name . '"' );
				}
			} else {
				throw new Exception( 'Error param' );
			}
		} else {
			$this->name = basename( $name );
			if ( is_null( $mime ) ) {
				$mime = mime_content_type( $name );
			}
			$this->mime    = $mime;
			$this->content = $content;
		}
		$this->size = strlen( $this->content );
	}
	
	public function Name() {
		return $this->name;
	}
	
	public function Mime() {
		return $this->mime;
	}
	
	public function Content() {
		return $this->content;
	}
	
	public function Size() {
		return $this->size;
	}
}

/**
 * Class BodyPost
 */
class FortressDB_BodyPost {
	/**
	 * @param array  $post
	 * @param string $delimiter
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function Get( array $post, $delimiter = '-------------0123456789' ) {
		if ( is_array( $post ) && ! empty( $post ) ) {
			$bool = false;
			
			foreach ( $post as $val ) {
				if ( $val instanceof FortressDB_File ) {
					$bool = true;
					break;
				}
			}
			
			if ( $bool ) {
				$ret = '';
				foreach ( $post as $name => $val ) {
					$ret .= '--' . $delimiter . "\r\n" . self::PartPost( $name, $val );
				}
				$ret .= "--" . $delimiter . "--\r\n";
			} else {
				$ret = http_build_query( $post );
			}
		} else {
			throw new Exception( 'Error input param!' );
		}
		
		return $ret;
	}
	
	/**
	 * @param $name
	 * @param $val
	 *
	 * @return string
	 */
	public static function PartPost( $name, $val ) {
		$body = 'Content-Disposition: form-data; name="' . $name . '"';
		if ( $val instanceof FortressDB_File ) {
			$file = $val->Name();
			$mime = $val->Mime();
			$cont = $val->Content();
			
			$body .= '; filename="' . $file . '"' . "\r\n";
			$body .= 'Content-Type: ' . $mime . "\r\n\r\n";
			$body .= $cont . "\r\n";
		} else {
			$body .= "\r\n\r\n" . urlencode( $val ) . "\r\n";
		}
		
		return $body;
	}
}
