# php-mediaburst-sms Changelog

## 1.4.1 (20th March 2012)
*	Escaping bad characters in the message to stop a DOMDocument error being thrown when sending an &, <, >, " or '.

## 1.4 (15th Feb 2012)
*	Add request logging support, set the log option and the XML will be output to the PHP error log
*	Allow a different class to be used for HTTP transport.	Primarily used for developing 
	plugins in systems that have their own HTTP wrappers such as WordPress

## 1.3.1 (24th Oct 2011)
*	Change internal API Urls to use api.mediaburst.co.uk 	

## 1.3 (28th Sep 2011)
*	Add support for Proxy servers, set the proxy_host and proxy_port parameters.

## 1.2 (24th Aug 2011)
*	Update CheckCredit function to use the mediaburst XML API, no change to 
	functionality, brings this wrapper in to line with API documentation.

## 1.1 (15th Aug 2011)
*   New mediaburstHTTP class
    Compatability wrapper around cURL and PHP stream interfaces for HTTP requests,
    this should make the mediaburst class work on more PHP installs without tweaking

*   Only enable SSL by default on installs where PHP supports SSL

*   Update readme to demonstrate optional parameters and clarify error handling

## 1.0 (21st Jul 2011)
* Initial public release

## 0.1 
* Internal release for http://snappr.eu
