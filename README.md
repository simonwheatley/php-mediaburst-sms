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

	// Create the SMS object
	$sms = new mediaburstSMS('username', 'password');

	// Send to a single number
	$result = $sms->Send('447123456789', 'Hello World');

	// Send to multiple numbers
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
These will be type mediaburstException for API errors and Exception 
for other errors such as unable to connect to the server.  This allows
you to handle the errors in your application.

For example sending the wrong username:

	try {
		$sms = new mediaburstSMS('wrong_username', 'password');
	} catch (mediaburstException $e) {
		echo 'SMS Exception: '.$e->getMessage();
	} catch (Exception $e) {
		echo 'Exception: '.$e->getMessage();
	}

will produce the response

	SMS Exception: Invalid Username Or Password

whereas, trying to send without an internet connection would produce

        Exception: HTTP Error calling SMS API - HTTP Status: 0 - cURL Erorr: Couldn't resolve host 'sms.message-platform.com'	

Advanced Usage
--------------

This class has a few additional features that some users may find useful

### Optional Parameters

The SMS library supports optional parameters for the following:

*   from [string]

    The from address displayed on a phone when they receive a message

*   long [boolean, default: true]  

    Enable long SMS. A standard text can contain 160 characters, a long SMS supports up to 459.

*   ssl [boolean, default: true]

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

For exmaple: 

	define('MEDIABURST_USER', 'username');
	define('MEDIABURST_PASS', 'password');
	$sms = new mediaburstSMS();
	$result = $sms->Send('mobile_number', 'Hello World');

Contributing
------------

If you have any feedback on this class drop us an email at hello@mediaburst.co.uk

To contribute improvements, create a fork and then submit a push request on GitHub

