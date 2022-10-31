<?php

namespace PostSMTP\Vendor;

if (\class_exists('PostSMTP\\Vendor\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['PostSMTP\\Vendor\\Google\\Client' => 'Google_Client', 'PostSMTP\\Vendor\\Google\\Service' => 'Google_Service', 'PostSMTP\\Vendor\\Google\\AccessToken\\Revoke' => 'Google_AccessToken_Revoke', 'PostSMTP\\Vendor\\Google\\AccessToken\\Verify' => 'Google_AccessToken_Verify', 'PostSMTP\\Vendor\\Google\\Model' => 'Google_Model', 'PostSMTP\\Vendor\\Google\\Utils\\UriTemplate' => 'Google_Utils_UriTemplate', 'PostSMTP\\Vendor\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'Google_AuthHandler_Guzzle6AuthHandler', 'PostSMTP\\Vendor\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'Google_AuthHandler_Guzzle7AuthHandler', 'PostSMTP\\Vendor\\Google\\AuthHandler\\Guzzle5AuthHandler' => 'Google_AuthHandler_Guzzle5AuthHandler', 'PostSMTP\\Vendor\\Google\\AuthHandler\\AuthHandlerFactory' => 'Google_AuthHandler_AuthHandlerFactory', 'PostSMTP\\Vendor\\Google\\Http\\Batch' => 'Google_Http_Batch', 'PostSMTP\\Vendor\\Google\\Http\\MediaFileUpload' => 'Google_Http_MediaFileUpload', 'PostSMTP\\Vendor\\Google\\Http\\REST' => 'Google_Http_REST', 'PostSMTP\\Vendor\\Google\\Task\\Retryable' => 'Google_Task_Retryable', 'PostSMTP\\Vendor\\Google\\Task\\Exception' => 'Google_Task_Exception', 'PostSMTP\\Vendor\\Google\\Task\\Runner' => 'Google_Task_Runner', 'PostSMTP\\Vendor\\Google\\Collection' => 'Google_Collection', 'PostSMTP\\Vendor\\Google\\Service\\Exception' => 'Google_Service_Exception', 'PostSMTP\\Vendor\\Google\\Service\\Resource' => 'Google_Service_Resource', 'PostSMTP\\Vendor\\Google\\Exception' => 'Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, 'PostSMTP\\Vendor\\' . $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \PostSMTP\Vendor\Google\Task\Composer
{
}
if (\false) {
    class Google_AccessToken_Revoke extends \PostSMTP\Vendor\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \PostSMTP\Vendor\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \PostSMTP\Vendor\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle5AuthHandler extends \PostSMTP\Vendor\Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \PostSMTP\Vendor\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \PostSMTP\Vendor\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \PostSMTP\Vendor\Google\Client
    {
    }
    class Google_Collection extends \PostSMTP\Vendor\Google\Collection
    {
    }
    class Google_Exception extends \PostSMTP\Vendor\Google\Exception
    {
    }
    class Google_Http_Batch extends \PostSMTP\Vendor\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \PostSMTP\Vendor\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \PostSMTP\Vendor\Google\Http\REST
    {
    }
    class Google_Model extends \PostSMTP\Vendor\Google\Model
    {
    }
    class Google_Service extends \PostSMTP\Vendor\Google\Service
    {
    }
    class Google_Service_Exception extends \PostSMTP\Vendor\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \PostSMTP\Vendor\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \PostSMTP\Vendor\Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \PostSMTP\Vendor\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \PostSMTP\Vendor\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \PostSMTP\Vendor\Google\Utils\UriTemplate
    {
    }
}
