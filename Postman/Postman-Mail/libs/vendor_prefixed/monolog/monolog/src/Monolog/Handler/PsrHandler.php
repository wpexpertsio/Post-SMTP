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

use PostSMTP\Vendor\Monolog\Logger;
use PostSMTP\Vendor\Psr\Log\LoggerInterface;
/**
 * Proxies log messages to an existing PSR-3 compliant logger.
 *
 * @author Michael Moussa <michael.moussa@gmail.com>
 */
class PsrHandler extends \PostSMTP\Vendor\Monolog\Handler\AbstractHandler
{
    /**
     * PSR-3 compliant logger
     *
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @param LoggerInterface $logger The underlying PSR-3 compliant logger to which messages will be proxied
     * @param int             $level  The minimum logging level at which this handler will be triggered
     * @param bool            $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(\PostSMTP\Vendor\Psr\Log\LoggerInterface $logger, $level = \PostSMTP\Vendor\Monolog\Logger::DEBUG, $bubble = \true)
    {
        parent::__construct($level, $bubble);
        $this->logger = $logger;
    }
    /**
     * {@inheritDoc}
     */
    public function handle(array $record)
    {
        if (!$this->isHandling($record)) {
            return \false;
        }
        $this->logger->log(\strtolower($record['level_name']), $record['message'], $record['context']);
        return \false === $this->bubble;
    }
}
