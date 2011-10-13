<?php 
/*
 * mediaburst SMS API wrapper for PHP
 * 
 * Contact hello@mediaburst.co.uk if you have any questions
 *
 * @package     mediaburstSMS
 * @author      mediaburst <hello@mediaburst.co.uk>
 * @copyright   2011 Mediaburst Ltd
 * @license     ISC
 * @version	1.2
 * @since       1.0
 * @link	http://www.mediaburst.co.uk/api/	Mediaburst API Documentation
 * @link	https://github.com/mediaburst/		Latest version of this class
 */

/* 
 * mediaburstSMS class 
 *
 * All the functionality you need to use the mediaburst SMS API is 
 * contained within this class.
 * 
 * If you use this class in multiple places throughout your project
 * you may find it easier to define your API username and password 
 * in two constants MEDIABURST_USER and MEDIABURST_PASS within an
 * include file, this saves having to pass them each time you create
 * the mediaburstSMS object
 * 
 * The latest version of this class can always be found at:
 * https://github.com/mediaburst/
 * 
 * If you have any improvements drop us an email or fork the project 
 * on GitHub and submit a push request.
 * 
 * @package	mediaburstSMS * 
 */
class mediaburstSMS {
	private $url_send = 'sms.message-platform.com/xml/send.aspx';
	private $url_credit = 'sms.message-platform.com/xml/credit.aspx';

	private $username;
	private $password;
	private $from;
	private $long;
	private $truncate;
	private $ssl;
	// Proxy server fields
	private $proxy_host;
	private $proxy_port;
	
	/**
	 * Enables various logging of messages when true.
	 *
	 * @var bool
	 **/
	protected $log;
	
	/*
	 * Create a mediaburstSMS object
	 *
	 * Username and password can be passed here or defined as constants
	 * If you use the MEDIABURST_USER and MEDIABURST_PASS constants 
	 * simpy miss out these parameters when creating the object
	 * e.g. $sms = new mediaburstSMS();
	 * 
	 * @param string username	mediaburst SMS API username
	 *				Leave blank to use the MEDIABURST_USER constant 
	 * @param string password	mediaburst SMS API password
	 *				Leave blank to use the MEDIABURST_PASS constant
	 * @param array	 options	Optional parameters for sending SMS
	 */
	public function __construct( $username="", $password="", $options = array() ) {
		if( empty($username) && !defined('MEDIABURST_USER') ) 
			throw new mediaburstException("Username is blank");
		else
			$this->username = (empty($username)) ? MEDIABURST_USER : $username;
		if( empty($password) && !defined('MEDIABURST_PASS') )
			throw new mediaburstException("Password is blank"); 
		else
			$this->password = (empty($password)) ? MEDIABURST_PASS : $password;
		

		// options and defaults
		$this->long = (array_key_exists('long', $options)) ? $options['long'] : true;
		$this->from = (array_key_exists('from', $options)) ? $options['from'] : null;
		$this->truncate = (array_key_exists('truncate', $options)) ? $options['truncate'] : false;
		$this->ssl = (array_key_exists('ssl' , $options)) ? $options['ssl'] : mediaburstHTTP::SSLSupport();
		$this->proxy_host = (array_key_exists('proxy_host', $options)) ? $options['proxy_host'] : null;
		$this->proxy_port = (array_key_exists('proxy_port', $options)) ? $options['proxy_port'] : null;
		$this->log = (array_key_exists('log', $options)) ? $options['log'] : false;
	}

	/* 
	 * Sends a text message.
	 * 
	 * The response is returned as an array of responses, each 
	 * response looking similar to:
	 * Array (
	 * 		[to] => 447971687295
	 * 		[id] => VE_127890871
	 * 		[success] => 1
	 * )
	 * 
	 * @param mixed	$to	Either a string containing a single mobile 
	 *				number or an array of numbers
	 * @param string $message The text message to send
	 * @param array $add_params An associative array of additional parameters to send with the request
	 * return An array of responses, each response as an array 
	 */
	public function Send( $to, $message, $add_params = array() ) {
		// Make single number in to array for easy processing
		if (!is_array($to)) 
			$to = array($to);
		
		$req_doc = new DOMDocument('1.0', 'UTF-8');
		$root = $req_doc->createElement('Message');
		$req_doc->appendChild($root);
		$root->appendChild($req_doc->createElement('Username', $this->username));
		$root->appendChild($req_doc->createElement('Password', $this->password));

		foreach($to as $number) {
			$sms_node = $req_doc->createElement('SMS');
			$sms_node->appendChild($req_doc->createElement('To', $number)); 
			$sms_node->appendChild($req_doc->createElement('Content', $message));
			if($this->long)
				$sms_node->appendChild($req_doc->createElement('Concat', 3));
			if($this->from)
				$sms_node->appendChild($req_doc->createElement('From', $this->from));
			if($this->truncate)
				$sms_node->appendChild($req_doc->createElement('Truncate', 1));
			foreach ( $add_params as $param => $value )
				$sms_node->appendChild($req_doc->createElement($param, $value));
			$root->appendChild($sms_node);
		}

		$req_xml = $req_doc->saveXML();
		if ( $this->log )
			$this->LogXML( 'Send SMS Request', $req_xml );
		$resp_xml = $this->PostToAPI($this->url_send, $req_xml);
		if ( $this->log )
			$this->LogXML( 'Send SMS Reply', $resp_xml );
		$resp_doc = new DOMDocument();
		$resp_doc->loadXML($resp_xml);

		$resp = array(); 
		$err_no = null;
		$err_desc = null;
		foreach($resp_doc->documentElement->childNodes AS $doc_child) {
			switch($doc_child->nodeName) {
				case "SMS_Resp":
					$sms = array();
					foreach($doc_child->childNodes AS $resp_node) {
						switch($resp_node->nodeName) {
							case "To":
								$sms['to'] = $resp_node->nodeValue;
								break;
							case "MessageID":
								$sms['id'] = $resp_node->nodeValue;
								break;
							case "ErrNo":
								$sms['error_no'] = $resp_node->nodeValue;
								break;
							case "ErrDesc":
								$sms['error_desc'] = $resp_node->nodeValue;
								break;
							default:
								$sms[strtolower($resp_node->nodeName)] = $resp_node->nodeValue;
								break;
						}
					}
					$sms['success'] = !array_key_exists('error_no', $sms);
					array_push($resp, $sms);
					break;
				case "ErrNo":
					$err_no = $doc_child->nodeValue;
					break;
				case "ErrDesc":
					$err_desc = $doc_child->nodeValue;
					break;
			}
		}
		if(isset($err_no)) {
			throw new mediaburstException($err_desc, $err_no);
		}

		return $resp;
	}

	/*
	 * Check how much credit you have available on your mediaburst API account
	 *
	 * @returns	long		Number of SMS you can send
	 */
	public function CheckCredit() {
		$req_doc = new DOMDocument('1.0', 'UTF-8');
		$root = $req_doc->createElement('Credit');
		$req_doc->appendChild($root);
		$root->appendChild($req_doc->createElement('Username', $this->username));
		$root->appendChild($req_doc->createElement('Password', $this->password));
		
		$req_xml = $req_doc->saveXML();		
		if ( $this->log )
			$this->LogXML( 'Credit Balance Request', $req_xml );
		$resp_xml = $this->PostToAPI($this->url_credit, $req_xml);
		if ( $this->log )
			$this->LogXML( 'Credit Balance Reply', $resp_xml );
		
		$resp_doc = new DOMDocument();
		$resp_doc->loadXML($resp_xml);
		
		$credit;
		$err_no = null;
		$err_desc = null;
		foreach($resp_doc->documentElement->childNodes AS $doc_child) {
			switch($doc_child->nodeName) {
				case "Credit":
					$credit = $doc_child->nodeValue;
					break;
				case "ErrNo":
					$err_no = $doc_child->nodeValue;
					break;
				case "ErrDesc":
					$err_desc = $doc_child->nodeValue;
					break;
			}
		}
		if(isset($err_no)) 
			throw new mediaburstException($err_desc, $err_no);
		
		return $credit;
	}

	/*
	 * Make an HTTP POST to the mediaburst API server
	 * 
	 * @param	string	url	URL to send to
	 * @param	string	data	Data to post
	 * @return	string		Server response
	 */
	private function PostToAPI($url, $data) {
		if($this->ssl)
			$url = 'https://'.$url;
		else
			$url = 'http://'.$url;
		
		$http = new mediaburstHTTP();
		$http->proxy_host = isset($this->proxy_host) ? $this->proxy_host : null;
		$http->proxy_port = isset($this->proxy_port) ? $this->proxy_port : null;

		return $http->Post($url, 'text/xml', $data);
	}

	/**
	 * Log some XML, tidily if possible, in the PHP error log
	 *
	 * @param string $log_msg The log message to prepend to the XML
	 * @param string $xml An XML formatted string
	 * @return void
	 **/
	protected function LogXML( $log_msg, $xml ) {
		// Tidy if possible
		if ( class_exists( 'tidy' ) ) {
			$tidy = new tidy;
			$config = array(
				'indent' => true,
				'input-xml'	=> true,
				'output-xml' => true,
				'wrap' => 200
			);
			$tidy->parseString( $xml, $config, 'utf8' );
			$tidy->cleanRepair();
			$xml = $tidy;
		}
		// Output
		error_log( "MBSMS $log_msg:  $xml" );
	}

	// Use PHP magic function to create GET property accessor 
	// i.e. $sms->from will call get_from() on the object
	public function __get($name) {
		if(method_exists($this, "get_$name"))
			return $this->{"get_$name"}();
		throw new Exception("get_$name does not exist");
	}
	// Use PHP magic function to create SET property accessor
	// i.e. $sms->from = $val will call set_from($val) on the object
	public function __set($name, $value) {
		if(method_exists($this, "set_$name"))
			return $this->{"set_$name"}($value);
		throw new Exception("set_$name does not exist");
	}

	/*
	 *  Property accessors for internal variables that need to be public
	 */
	private function get_from() {
		return $this->from;
	}
	private function set_from($value) {
		$this->from = $value;
	}

	private function get_long() {
		return $this->long;
	}
	private function set_long($value) {
		$this->long = $value;
	}
	
	private function get_truncate() {
		return $this->truncate;
	}
	private function set_truncate($value) {
		$this->truncate = $value;
	}

	private function get_ssl() {
		return $this->ssl;
	}
	private function set_ssl($value) {
		$this->ssl = $value;
	}

	private function get_proxy_host() {
		return $this->proxy_host;
	}
	private function set_proxy_host($value) {
		$this->proxy_host = $value;
	}

	private function get_proxy_port() {
		return $this->proxy_port;
	}
	private function set_proxy_port($value) {
		$this->proxy_port = $value;
	}
}

/*
 * mediaburstException class
 *
 * The mediaburstSMS class will throw these if an error occurs
 *
 * @package     mediaburstSMS
 * @since	1.0
 */
class mediaburstException extends Exception {

        public function __construct( $message, $code=0 ) {
                // make sure everything is assigned properly
                parent::__construct($message, $code);
        }
}

/* 
 * mediaburstHTTP class
 * 
 * Wrapper class for HTTP calls, attempts to work round the 
 * differences in PHP versions, such as SSL & curl support
 * 
 * @package	mediaburstSMS
 * @since	1.1
 */
class mediaburstHTTP { 
	// Optional parameters for proxy servers
	public $proxy_host;
	public $proxy_port;

	/*
	 * Check if PHP has SSL support compiled in
	 *
	 * @returns     True if SSL is supported
	 */
	public static function SSLSupport() {
		$ssl = false;
		// See if PHP compiled with cURL
		if(extension_loaded('curl')) {
			$version = curl_version();
			$ssl = ($version['features'] & CURL_VERSION_SSL) ? true : false;
		} elseif (extension_loaded('openssl')) { 
			$ssl = true;
		}
		return $ssl;
	}

	/* 
	 * Make an HTTP POST 
	 * 
	 * cURL will be used if available, otherwise tries the PHP stream functions
	 * The PHP stream functions require at least PHP 5.0, cURL should work with PHP 4
	 *
	 * @param	string	url	URL to send to
	 * @param	string	type	MIME Type of data
	 * @param	string	data	Data to POST
	 * @return	string		Response returned by server
	 */
	public function Post($url, $type, $data) {
		if(extension_loaded('curl')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: $type"));
			curl_setopt($ch, CURLOPT_USERAGENT, "mediaburst PHP Wrapper/1.1.0");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			if(isset($this->proxy_host) && isset($this->proxy_port)) {
				curl_setopt($ch, CURLOPT_PROXY, $this->proxy_host);
				curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_port);
			}

			$response = curl_exec($ch);
			$info = curl_getinfo($ch);

			if ($response === false || $info['http_code'] != 200) {
				throw new Exception('HTTP Error calling SMS API - HTTP Status: '.$info['http_code'].' - cURL Erorr: '.curl_error($ch));
			} elseif(curl_errno($ch) > 0) {
				throw new Exception('HTTP Error calling SMS API - cURL Error: '.curl_error($ch));
			}

			curl_close($ch);

			return $response;
		} elseif(function_exists('stream_get_contents')) {

			// Enable error Track Errors
			$track = ini_get('track_errors');
			ini_set('track_errors',true);

			$params = array('http' => array(
				'method' => 'POST',
				'header' => "Content-Type: $type\r\nUser-Agent: mediaburst PHP Wrapper/1.1.0\r\n",
				'content' => $data
			));

			if(isset($this->proxy_host) && isset($this->proxy_port)) {
				$params['http']['proxy'] = 'tcp://'.$this->proxy_host.':'.$this->proxy_port;
				$params['http']['request_fulluri'] = True;
			}
	
			$ctx = stream_context_create($params);
			$fp = @fopen($url, 'rb', false, $ctx);
			if (!$fp) {
				ini_set('track_errors',$track);
				throw new Exception("HTTP Error calling SMS API - fopen Error: $php_errormsg");
			}
			$response = @stream_get_contents($fp);
			if ($response === false) {
				ini_set('track_errors',$track);
				throw new Exception("HTTP Error calling SMS API - Stream Error: $php_errormsg");
			}
			ini_set('track_errors',$track);
			return $response;
		} else {
			throw new Exception("mediaburstSMS requires PHP5 or PHP4 with cURL");
		}		
	}
}
