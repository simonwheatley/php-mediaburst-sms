mediaburstSMS
=============

This class lets you interact with the mediaburst SMS API without 
the hassle or having to create any XML or make HTTP calls.

Installation
------------
Simply download mediaburstSMS.class.php and include it in your project

Usage
-----

Include the class in to your project
	include('mediaburstSMS.class.php');

### Sending a message
	$sms = new mediaburstSMS('username', 'password');
	$result = $sms->Send('mobile_number', 'Hello World');
	
### Checking your credit
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
* Custom from address
	The string displayed on a phone when they receive a message
* Long SMS Support (Default: on)
	A standard text can contain 160 characters, a long SMS supports up to 459.
* Use SSL (Default: on)
	Use SSL when making an HTTP request to the mediaburst API

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

	define('MEDIABURST_USER', 'username');
	define('MEDIABURST_PASS', 'password');
	$sms = new mediaburstSMS();
	$result = $sms->Send('mobile_number', 'Hello World');

Contributing
------------

If you have any feedback on this class drop us an email at hello@mediaburst.co.uk

To contribute improvements, create a fork and then submit a push request on GitHub

