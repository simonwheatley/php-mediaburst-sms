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
 * @version	1.0
 * @since       1.0
 * @link	http://www.mediaburst.co.uk/api/	Mediaburst API Documentation
 * @link	https://github.com/mediaburst/  	Latest version of this class
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
	private $url_credit = 'sms.message-platform.com/http/credit.aspx';
	private $username;
	private $password;
	private $from;
	private $long;
	private $ssl;
	
	/*
	 * Create a mediaburstSMS object
	 *
	 * Username and password can be passed here or defined as constants
	 * If you use the MEDIABURST_USER and MEDIABURST_PASS constants 
	 * simpy miss out these parameters when creating the object
	 * e.g. $sms = new mediaburstSMS();
	 * 
	 * @param string username	mediaburst SMS API username
	 * 				Leave blank to use the MEDIABURST_USER constant 
	 * @param string password	mediaburst SMS API password
	 * 				Leave blank to use the MEDIABURST_PASS constant
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
		$this->ssl  = (array_key_exists('ssl' , $options)) ? $options['ssl'] : true;
	}

	/* 
	 * Sends a text message
	 * 
	 * @param mixed 	to 	Either a string containing a single mobile 
	 *				number or an array of numbers
	 * @param string	message	The text message to send
	 */
	public function Send( $to, $message ) {
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

			$root->appendChild($sms_node);
		}

		$req_xml = $req_doc->saveXML();
		$resp_xml = $this->PostToAPI($this->url_send, 'text/xml', $req_xml);
		$resp_doc = DOMDocument::loadXML($resp_xml);

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
		$params = http_build_query(array('username' => $this->username, 'password' => $this->password));
		$resp = $this->PostToAPI($this->url_credit, 'application/x-www-form-urlencoded', $params);
		if(preg_match('/Error (\d+): (.*)/', $resp, $err_match)) {
			throw new mediaburstException($err_match[2], $err_match[1]);
		} elseif(preg_match('/Current Credit: (\d+)/', $resp, $credit_match)) {
			return $credit_match[1];
		} else {
			throw new mediaburstException("Invalid Response from API: $resp");
		}
	}

	/* 
	 * Make an HTTP POST to the mediaburst API server
	 *
	 * @param 	string	url	URL to send to
	 * @param 	string	xml	XML data to POST
	 * @return	string		XML data returned by server
	 */
	private function PostToAPI($url, $type, $data) {

		if($this->ssl)
			$url = 'https://'.$url;
		else
			$url = 'http://'.$url;

		$params = array('http' => array(
			'method' => 'POST',
			'header' => "Content-Type: $type\r\n",
			'content' => $data
		));

		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		if (!$fp) {
			throw new Exception("Problem with $url");
		}
		$response = @stream_get_contents($fp);
		if ($response === false) {
			throw new Exception("Problem reading data from $url");
		}
		return $response;
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
	
	private function get_ssl() {
		return $this->ssl;
	}
	private function set_ssl($value) {
		$this->ssl = $value;
	}
}

/*
 * mediaburstException class
 *
 * The mediaburstSMS class will throw these if an error occurs
 *
 * @package     mediaburstSMS
 */
class mediaburstException extends Exception {

        public function __construct( $message, $code=0 ) {
                // make sure everything is assigned properly
                parent::__construct($message, $code);
        }
}

