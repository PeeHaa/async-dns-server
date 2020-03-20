<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Cache;

use Amp\Cache\Cache;
use Amp\Promise;
use Amp\Redis\Redis as RedisClient;
use Amp\Redis\SetOptions;
use function Amp\call;

final class Redis implements Cache
{
    private const DEFAULT_PREFIX = 'AsyncDnsServer';

    private RedisClient $client;

    private string $prefix;

    public function __construct(RedisClient $client, string $prefix = self::DEFAULT_PREFIX)
    {
        $this->client = $client;
        $this->prefix = $prefix;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): Promise
    {
        return call(function () use ($key) {
            return yield $this->client->get($this->buildKey($key));
        });
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, string $value, int $ttl = null): Promise
    {
        return $this->client->set($this->buildKey($key), $value, (new SetOptions())->withTtl($ttl));
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): Promise
    {
        return $this->client->delete($this->buildKey($key));
    }

    private function buildKey(string $key): string
    {
        return sprintf('%s_%s', $this->prefix, $key);
    }
}
