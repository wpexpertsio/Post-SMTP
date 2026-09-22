<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Response\UsageResponse;

final class Usage extends AbstractResource
{
    public function retrieve(): UsageResponse
    {
        return new UsageResponse($this->request('GET', '/usage'));
    }
}
