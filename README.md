mediaburstSMS
=============

This class lets you interact with the mediaburst SMS API without 
the hassle of having to create any XML or make HTTP calls.

Installation
------------
Simply download mediaburstSMS.class.php and include it in your project

Usage
-----

Include the class in to your project

	include('mediaburstSMS.class.php');

### Sending a message

You can send a message to a single number or an array of numbers

1. Single

	$sms = new mediaburstSMS('username', 'password');
	$result = $sms->Send('447123456789', 'Hello World');

2. Array
	
	$sms = new mediaburstSMS('username', 'password');
	$result = $sms->Send(array('mobile_number_1', 'mobile_number_2'), 'Hello World');
	
The Send call will return an array of responses, one for each number sent to. 

For a successfull send the response will contain the following:

	Array ( 
		'to'         => '447123456789',
		'id'         => 'message_id',
		'success'    => true
	); 

Whereas if a particular number failed it will look like this:

	Array (
		'to'         => 'not_a_number',
		'error_no'   => 10,
		'error_desc' => "Invalid 'To' Parameter",
		'success'    => false 
	);

The success parameter allows you to quickly see if the message worked.

### Checking your credit

Check how many SMS credits you currently have available

	$sms = new mediaburstSMS('username', 'password');
	$balance = $sms->CheckCredit();

### Handling Errors
The mediaburstSMS class will throw exceptions if the entire call failed. 

For example sending the wrong username:

	try {
		$sms = new mediaburstSMS('wrong_username', 'password');
	} catch (mediaburstException $e) {
		echo "Exception sending SMS: '.$e->getMessage();
	}

will produce the response

	Exception sending SMS: Invalid Username Or Password

Advanced Usage
--------------

This class has a few additional features that some users may find useful

### Optional Parameters

The SMS library supports optional parameters for the following:

* Custom from address - The string displayed on a phone when they receive a message
* Long SMS Support (Default: on) - A standard text can contain 160 characters, a long SMS supports up to 459.
* Use SSL (Default: on) - Use SSL when making an HTTP request to the mediaburst API

### Setting Options
Options can be passed as an array when creating the sms object

	$sms = new mediaburstSMS('username', 'password', array('from'=> 'from') );

Or set individually using properties

	$sms = new mediaburstSMS('username', 'password');
	$sms->from = 'php_code';

### Global Defaults
You can define constants used by the library to save having to pass them each time it is called

* MEDIABURST_USER - API Username
* MEDIABURST_PASS - API Password

For exmaple: 

	define('MEDIABURST_USER', 'username');
	define('MEDIABURST_PASS', 'password');
	$sms = new mediaburstSMS();
	$result = $sms->Send('mobile_number', 'Hello World');

Contributing
------------

If you have any feedback on this class drop us an email at hello@mediaburst.co.uk

To contribute improvements, create a fork and then submit a push request on GitHub

