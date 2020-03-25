<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Configuration;

use Amp\Cache\ArrayCache;
use Amp\Promise;
use Amp\Redis\Config as RedisConfig;
use Amp\Redis\Redis as RedisClient;
use Amp\Redis\RemoteExecutor;
use LibDNS\Decoder\Decoder;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\Encoder;
use LibDNS\Encoder\EncoderFactory;
use PeeHaa\AsyncDnsServer\Cache\Redis;
use PeeHaa\AsyncDnsServer\Logger\Factory;
use PeeHaa\AsyncDnsServer\Logger\Logger;
use PeeHaa\AsyncDnsServer\Resolver\Cache;
use PeeHaa\AsyncDnsServer\Resolver\External;
use PeeHaa\AsyncDnsServer\Resolver\Recursive;
use PeeHaa\AsyncDnsServer\Resolver\Resolver;
use PeeHaa\AsyncDnsServer\Resolver\Stack;
use function Amp\call;
use function Amp\File\get;

final class Configuration
{
    private Logger $logger;

    private Resolver $resolver;

    /** @var array<ServerAddress> */
    private array $serverAddresses = [];

    public function __construct(Logger $logger, Resolver $resolver, ServerAddress $serverAddress, ServerAddress ...$serverAddresses)
    {
        $this->logger          = $logger;
        $this->resolver        = $resolver;
        $this->serverAddresses = array_merge([$serverAddress], $serverAddresses);
    }

    /**
     * @return Promise<self>
     */
    public static function fromConfigurationFile(Encoder $encoder, Decoder $decoder, string $filename): Promise
    {
        return call(function () use ($filename) {
            $configuration = yield get($filename);

            $parsedConfiguration = parse_ini_string($configuration, true);

            $loggerFactory = sprintf('%s::build%s', Factory::class, $parsedConfiguration['log']['type']);

            $logger  = $loggerFactory();
            $encoder = (new EncoderFactory())->create();
            $decoder = (new DecoderFactory())->create();

            $resolvers = array_map(
                fn (string $resolver) => self::buildResolver($logger, $encoder, $decoder, $resolver),
                $parsedConfiguration['resolvers']['resolver'],
            );

            if (count($resolvers) > 1) {
                $resolver = new Stack(...$resolvers);
            } else {
                $resolver = $resolvers[0];
            }

            $servers = [
                new ServerAddress(ServerAddress::TYPE_UDP, '127.0.0.1', 53),
                new ServerAddress(ServerAddress::TYPE_UDP, '[::1]', 53),
                new ServerAddress(ServerAddress::TYPE_TCP, '127.0.0.1', 53),
                new ServerAddress(ServerAddress::TYPE_TCP, '[::1]', 53),
            ];

            if (!$parsedConfiguration['cache']['enabled']) {
                return new self($logger, $resolver, ...$servers);
            }

            if (!$parsedConfiguration['cache']['type'] === 'memory') {
                return new self($logger, new Cache($resolver, new ArrayCache()), ...$servers);
            }

            $redisCache = new Redis(
                new RedisClient(new RemoteExecutor(
                    RedisConfig::fromUri(
                        sprintf(
                            'tcp://%s:%d',
                            $parsedConfiguration['redis']['host'],
                            (int) $parsedConfiguration['redis']['port'],
                        ),
                    ),
                )),
            );

            return new self($logger, new Cache($resolver, $redisCache), ...$servers);
        });
    }

    public static function buildResolver(Logger $logger, Encoder $encoder, Decoder $decoder, string $resolver): Resolver
    {
        if (preg_match('~^External/(?P<ipAddress>[^:]+):(?P<port>\d+)$~', $resolver, $matches)) {
            return new External($logger, $encoder, $decoder, $matches['ipAddress'], (int) $matches['port']);
        }

        switch ($resolver) {
            case 'Recursive':
                return new Recursive($logger, $encoder, $decoder);
        }
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function getResolver(): Resolver
    {
        return $this->resolver;
    }

    /**
     * @return array<ServerAddress>
     */
    public function getServerAddresses(): array
    {
        return $this->serverAddresses;
    }
}
