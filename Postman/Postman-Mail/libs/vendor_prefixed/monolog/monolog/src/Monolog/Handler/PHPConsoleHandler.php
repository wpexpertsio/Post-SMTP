<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PostSMTP\Vendor\Monolog\Handler;

use Exception;
use PostSMTP\Vendor\Monolog\Formatter\LineFormatter;
use PostSMTP\Vendor\Monolog\Logger;
use PostSMTP\Vendor\Monolog\Utils;
use PostSMTP\Vendor\PhpConsole\Connector;
use PostSMTP\Vendor\PhpConsole\Handler;
use PostSMTP\Vendor\PhpConsole\Helper;
/**
 * Monolog handler for Google Chrome extension "PHP Console"
 *
 * Display PHP error/debug log messages in Google Chrome console and notification popups, executes PHP code remotely
 *
 * Usage:
 * 1. Install Google Chrome extension https://chrome.google.com/webstore/detail/php-console/nfhmhhlpfleoednkpnnnkolmclajemef
 * 2. See overview https://github.com/barbushin/php-console#overview
 * 3. Install PHP Console library https://github.com/barbushin/php-console#installation
 * 4. Example (result will looks like http://i.hizliresim.com/vg3Pz4.png)
 *
 *      $logger = new \Monolog\Logger('all', array(new \Monolog\Handler\PHPConsoleHandler()));
 *      \Monolog\ErrorHandler::register($logger);
 *      echo $undefinedVar;
 *      $logger->addDebug('SELECT * FROM users', array('db', 'time' => 0.012));
 *      PC::debug($_SERVER); // PHP Console debugger for any type of vars
 *
 * @author Sergey Barbushin https://www.linkedin.com/in/barbushin
 */
class PHPConsoleHandler extends \PostSMTP\Vendor\Monolog\Handler\AbstractProcessingHandler
{
    private $options = array(
        'enabled' => \true,
        // bool Is PHP Console server enabled
        'classesPartialsTraceIgnore' => array('PostSMTP\\Vendor\\Monolog\\'),
        // array Hide calls of classes started with...
        'debugTagsKeysInContext' => array(0, 'tag'),
        // bool Is PHP Console server enabled
        'useOwnErrorsHandler' => \false,
        // bool Enable errors handling
        'useOwnExceptionsHandler' => \false,
        // bool Enable exceptions handling
        'sourcesBasePath' => null,
        // string Base path of all project sources to strip in errors source paths
        'registerHelper' => \true,
        // bool Register PhpConsole\Helper that allows short debug calls like PC::debug($var, 'ta.g.s')
        'serverEncoding' => null,
        // string|null Server internal encoding
        'headersLimit' => null,
        // int|null Set headers size limit for your web-server
        'password' => null,
        // string|null Protect PHP Console connection by password
        'enableSslOnlyMode' => \false,
        // bool Force connection by SSL for clients with PHP Console installed
        'ipMasks' => array(),
        // array Set IP masks of clients that will be allowed to connect to PHP Console: array('192.168.*.*', '127.0.0.1')
        'enableEvalListener' => \false,
        // bool Enable eval request to be handled by eval dispatcher(if enabled, 'password' option is also required)
        'dumperDetectCallbacks' => \false,
        // bool Convert callback items in dumper vars to (callback SomeClass::someMethod) strings
        'dumperLevelLimit' => 5,
        // int Maximum dumped vars array or object nested dump level
        'dumperItemsCountLimit' => 100,
        // int Maximum dumped var same level array items or object properties number
        'dumperItemSizeLimit' => 5000,
        // int Maximum length of any string or dumped array item
        'dumperDumpSizeLimit' => 500000,
        // int Maximum approximate size of dumped vars result formatted in JSON
        'detectDumpTraceAndSource' => \false,
        // bool Autodetect and append trace data to debug
        'dataStorage' => null,
    );
    /** @var Connector */
    private $connector;
    /**
     * @param  array          $options   See \Monolog\Handler\PHPConsoleHandler::$options for more details
     * @param  Connector|null $connector Instance of \PhpConsole\Connector class (optional)
     * @param  int            $level
     * @param  bool           $bubble
     * @throws Exception
     */
    public function __construct(array $options = array(), \PostSMTP\Vendor\PhpConsole\Connector $connector = null, $level = \PostSMTP\Vendor\Monolog\Logger::DEBUG, $bubble = \true)
    {
        if (!\class_exists('PostSMTP\\Vendor\\PhpConsole\\Connector')) {
            throw new \Exception('PHP Console library not found. See https://github.com/barbushin/php-console#installation');
        }
        parent::__construct($level, $bubble);
        $this->options = $this->initOptions($options);
        $this->connector = $this->initConnector($connector);
    }
    private function initOptions(array $options)
    {
        $wrongOptions = \array_diff(\array_keys($options), \array_keys($this->options));
        if ($wrongOptions) {
            throw new \Exception('Unknown options: ' . \implode(', ', $wrongOptions));
        }
        return \array_replace($this->options, $options);
    }
    private function initConnector(\PostSMTP\Vendor\PhpConsole\Connector $connector = null)
    {
        if (!$connector) {
            if ($this->options['dataStorage']) {
                \PostSMTP\Vendor\PhpConsole\Connector::setPostponeStorage($this->options['dataStorage']);
            }
            $connector = \PostSMTP\Vendor\PhpConsole\Connector::getInstance();
        }
        if ($this->options['registerHelper'] && !\PostSMTP\Vendor\PhpConsole\Helper::isRegistered()) {
            \PostSMTP\Vendor\PhpConsole\Helper::register();
        }
        if ($this->options['enabled'] && $connector->isActiveClient()) {
            if ($this->options['useOwnErrorsHandler'] || $this->options['useOwnExceptionsHandler']) {
                $handler = \PostSMTP\Vendor\PhpConsole\Handler::getInstance();
                $handler->setHandleErrors($this->options['useOwnErrorsHandler']);
                $handler->setHandleExceptions($this->options['useOwnExceptionsHandler']);
                $handler->start();
            }
            if ($this->options['sourcesBasePath']) {
                $connector->setSourcesBasePath($this->options['sourcesBasePath']);
            }
            if ($this->options['serverEncoding']) {
                $connector->setServerEncoding($this->options['serverEncoding']);
            }
            if ($this->options['password']) {
                $connector->setPassword($this->options['password']);
            }
            if ($this->options['enableSslOnlyMode']) {
                $connector->enableSslOnlyMode();
            }
            if ($this->options['ipMasks']) {
                $connector->setAllowedIpMasks($this->options['ipMasks']);
            }
            if ($this->options['headersLimit']) {
                $connector->setHeadersLimit($this->options['headersLimit']);
            }
            if ($this->options['detectDumpTraceAndSource']) {
                $connector->getDebugDispatcher()->detectTraceAndSource = \true;
            }
            $dumper = $connector->getDumper();
            $dumper->levelLimit = $this->options['dumperLevelLimit'];
            $dumper->itemsCountLimit = $this->options['dumperItemsCountLimit'];
            $dumper->itemSizeLimit = $this->options['dumperItemSizeLimit'];
            $dumper->dumpSizeLimit = $this->options['dumperDumpSizeLimit'];
            $dumper->detectCallbacks = $this->options['dumperDetectCallbacks'];
            if ($this->options['enableEvalListener']) {
                $connector->startEvalRequestsListener();
            }
        }
        return $connector;
    }
    public function getConnector()
    {
        return $this->connector;
    }
    public function getOptions()
    {
        return $this->options;
    }
    public function handle(array $record)
    {
        if ($this->options['enabled'] && $this->connector->isActiveClient()) {
            return parent::handle($record);
        }
        return !$this->bubble;
    }
    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record)
    {
        if ($record['level'] < \PostSMTP\Vendor\Monolog\Logger::NOTICE) {
            $this->handleDebugRecord($record);
        } elseif (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Exception) {
            $this->handleExceptionRecord($record);
        } else {
            $this->handleErrorRecord($record);
        }
    }
    private function handleDebugRecord(array $record)
    {
        $tags = $this->getRecordTags($record);
        $message = $record['message'];
        if ($record['context']) {
            $message .= ' ' . \PostSMTP\Vendor\Monolog\Utils::jsonEncode($this->connector->getDumper()->dump(\array_filter($record['context'])), null, \true);
        }
        $this->connector->getDebugDispatcher()->dispatchDebug($message, $tags, $this->options['classesPartialsTraceIgnore']);
    }
    private function handleExceptionRecord(array $record)
    {
        $this->connector->getErrorsDispatcher()->dispatchException($record['context']['exception']);
    }
    private function handleErrorRecord(array $record)
    {
        $context = $record['context'];
        $this->connector->getErrorsDispatcher()->dispatchError(isset($context['code']) ? $context['code'] : null, isset($context['message']) ? $context['message'] : $record['message'], isset($context['file']) ? $context['file'] : null, isset($context['line']) ? $context['line'] : null, $this->options['classesPartialsTraceIgnore']);
    }
    private function getRecordTags(array &$record)
    {
        $tags = null;
        if (!empty($record['context'])) {
            $context =& $record['context'];
            foreach ($this->options['debugTagsKeysInContext'] as $key) {
                if (!empty($context[$key])) {
                    $tags = $context[$key];
                    if ($key === 0) {
                        \array_shift($context);
                    } else {
                        unset($context[$key]);
                    }
                    break;
                }
            }
        }
        return $tags ?: \strtolower($record['level_name']);
    }
    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter()
    {
        return new \PostSMTP\Vendor\Monolog\Formatter\LineFormatter('%message%');
    }
}
