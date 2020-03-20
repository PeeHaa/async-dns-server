<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Resolver;

use Amp\Promise;
use PeeHaa\AsyncDnsServer\Message;

interface Resolver
{
    /**
     * @return Promise<Message>
     */
    public function query(Message $message): Promise;
}
