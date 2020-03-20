<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Binary;

use Amp\Loop;
use Amp\Redis\Config as RedisConfig;
use Amp\Redis\Redis as RedisClient;
use Amp\Redis\RemoteExecutor;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\EncoderFactory;
use PeeHaa\AsyncDnsServer\Cache\Redis;
use PeeHaa\AsyncDnsServer\Configuration\Configuration;
use PeeHaa\AsyncDnsServer\Configuration\ServerAddress;
use PeeHaa\AsyncDnsServer\Logger\Factory;
use PeeHaa\AsyncDnsServer\Resolver\Cache;
use PeeHaa\AsyncDnsServer\Resolver\External;
use PeeHaa\AsyncDnsServer\Server;

require_once __DIR__ . '/../vendor/autoload.php';

Loop::run(static function () {
    $logger  = Factory::buildStdOutLogger();
    $encoder = (new EncoderFactory())->create();
    $decoder = (new DecoderFactory())->create();
    $cache   = new Redis(new RedisClient(new RemoteExecutor(RedisConfig::fromUri(sprintf('tcp://%s:%d', '127.0.0.1', 6379)))));

    $configuration = new Configuration(
        $logger,
        new Cache(new External($logger, $encoder, $decoder, '8.8.8.8'), $cache),
        new ServerAddress(ServerAddress::TYPE_UDP, '127.0.0.1'),
        new ServerAddress(ServerAddress::TYPE_TCP, '127.0.0.1'),
        new ServerAddress(ServerAddress::TYPE_UDP, '[::1]'),
        new ServerAddress(ServerAddress::TYPE_TCP, '[::1]'),
    );

    yield (new Server($configuration, $encoder, $decoder))->start();
});
