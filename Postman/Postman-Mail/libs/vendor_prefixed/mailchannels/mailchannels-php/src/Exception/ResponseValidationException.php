<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Exception;

/**
 * Raised when the MailChannels API returns a 2xx response whose body doesn't
 * match the SDK's expected shape for that endpoint (missing required fields
 * or fields with the wrong type).
 *
 * Distinct from {@see ValidationException}, which is raised pre-flight when
 * the *request* payload built by the caller is malformed.
 */
class ResponseValidationException extends MailChannelsException
{
}
