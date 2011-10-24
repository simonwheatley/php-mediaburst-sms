# php-mediaburst-sms Changelog

## 1.3.1 (24th Oct 2011)
*	Change internal API Urls to use api.mediaburst.co.uk 	

## 1.3 (28th Sep 2011)
*	Add support for Proxy servers, set the proxy_host and proxy_port 
	parameters.

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
