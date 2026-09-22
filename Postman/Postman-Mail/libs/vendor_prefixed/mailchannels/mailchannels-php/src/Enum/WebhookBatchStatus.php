<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Enum;

/**
 * Status filter values for the `statuses` query parameter of
 * `GET /webhook-batch`. These classify the HTTP response received from the
 * customer's webhook endpoint (or `NoResponse` if none was received within
 * the timeout).
 *
 * NOTE: The Email API uses different strings on the response body of each
 * batch (`1xx_response`, `2xx_response`, ..., `no_response`). The query
 * parameter uses the short form below. This enum represents only the query
 * filter form; the response field is left as a plain string in
 * `Response::get('status')`.
 */
enum WebhookBatchStatus: string
{
    case Informational = '1xx';
    case Success = '2xx';
    case Redirection = '3xx';
    case ClientError = '4xx';
    case ServerError = '5xx';
    case NoResponse = 'no_response';
}
