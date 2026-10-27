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
use PostSMTP\Vendor\Monolog\Formatter\FormatterInterface;
use PostSMTP\Vendor\Monolog\Formatter\LineFormatter;
use PostSMTP\Vendor\Swift;
/**
 * SwiftMailerHandler uses Swift_Mailer to send the emails
 *
 * @author Gyula Sallai
 */
class SwiftMailerHandler extends \PostSMTP\Vendor\Monolog\Handler\MailHandler
{
    protected $mailer;
    private $messageTemplate;
    /**
     * @param \Swift_Mailer           $mailer  The mailer to use
     * @param callable|\Swift_Message $message An example message for real messages, only the body will be replaced
     * @param int                     $level   The minimum logging level at which this handler will be triggered
     * @param bool                    $bubble  Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(\PostSMTP\Vendor\Swift_Mailer $mailer, $message, $level = \PostSMTP\Vendor\Monolog\Logger::ERROR, $bubble = \true)
    {
        parent::__construct($level, $bubble);
        $this->mailer = $mailer;
        $this->messageTemplate = $message;
    }
    /**
     * {@inheritdoc}
     */
    protected function send($content, array $records)
    {
        $this->mailer->send($this->buildMessage($content, $records));
    }
    /**
     * Gets the formatter for the Swift_Message subject.
     *
     * @param  string             $format The format of the subject
     * @return FormatterInterface
     */
    protected function getSubjectFormatter($format)
    {
        return new \PostSMTP\Vendor\Monolog\Formatter\LineFormatter($format);
    }
    /**
     * Creates instance of Swift_Message to be sent
     *
     * @param  string         $content formatted email body to be sent
     * @param  array          $records Log records that formed the content
     * @return \Swift_Message
     */
    protected function buildMessage($content, array $records)
    {
        $message = null;
        if ($this->messageTemplate instanceof \PostSMTP\Vendor\Swift_Message) {
            $message = clone $this->messageTemplate;
            $message->generateId();
        } elseif (\is_callable($this->messageTemplate)) {
            $message = \call_user_func($this->messageTemplate, $content, $records);
        }
        if (!$message instanceof \PostSMTP\Vendor\Swift_Message) {
            throw new \InvalidArgumentException('Could not resolve message as instance of Swift_Message or a callable returning it');
        }
        if ($records) {
            $subjectFormatter = $this->getSubjectFormatter($message->getSubject());
            $message->setSubject($subjectFormatter->format($this->getHighestRecord($records)));
        }
        $message->setBody($content);
        if (\version_compare(\PostSMTP\Vendor\Swift::VERSION, '6.0.0', '>=')) {
            $message->setDate(new \DateTimeImmutable());
        } else {
            $message->setDate(\time());
        }
        return $message;
    }
    /**
     * BC getter, to be removed in 2.0
     */
    public function __get($name)
    {
        if ($name === 'message') {
            \trigger_error('SwiftMailerHandler->message is deprecated, use ->buildMessage() instead to retrieve the message', \E_USER_DEPRECATED);
            return $this->buildMessage(null, array());
        }
        throw new \InvalidArgumentException('Invalid property ' . $name);
    }
}
