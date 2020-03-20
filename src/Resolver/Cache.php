<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Resolver;

use Amp\Cache\Cache as AmpCache;
use Amp\Promise;
use PeeHaa\AsyncDnsServer\Message;
use function Amp\call;

final class Cache implements Resolver
{
    private Resolver $resolver;

    private AmpCache $cache;

    public function __construct(Resolver $resolver, AmpCache $cache)
    {
        $this->resolver = $resolver;
        $this->cache    = $cache;
    }

    /**
     * @return Promise<Message>
     */
    public function query(Message $message): Promise
    {
        return call(function () use ($message) {
            $cachedData = yield $this->cache->get($this->buildCacheKey($message));

            if ($cachedData !== null) {
                return $this->buildMessageFromCache($cachedData);
            }

            $message = yield $this->resolver->query($message);

            // @todo: figure out the correct ttl
            yield $this->cache->set($this->buildCacheKey($message), $this->buildCacheData($message), 1);

            return $message;
        });
    }

    private function buildCacheKey(Message $message): string
    {
        return sha1(json_encode($message->toArray()));
    }

    private function buildMessageFromCache(string $data): Message
    {
        return Message::fromArray(json_decode($data, true));
    }

    private function buildCacheData(Message $message): string
    {
        return json_encode($message->toArray());
    }
}
