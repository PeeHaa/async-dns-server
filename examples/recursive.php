<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Examples;

use Amp\Loop;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\EncoderFactory;
use PeeHaa\AsyncDnsServer\Configuration\Configuration;
use PeeHaa\AsyncDnsServer\Configuration\ServerAddress;
use PeeHaa\AsyncDnsServer\Logger\Factory;
use PeeHaa\AsyncDnsServer\Resolver\Recursive;
use PeeHaa\AsyncDnsServer\Server;

require_once __DIR__ . '/../vendor/autoload.php';

Loop::run(static function () {
    $logger  = Factory::buildStdOutLogger();
    $encoder = (new EncoderFactory())->create();
    $decoder = (new DecoderFactory())->create();

    $configuration = new Configuration(
        $logger,
        new Recursive($logger, $encoder, $decoder),
        new ServerAddress(ServerAddress::TYPE_UDP, '127.0.0.1'),
        new ServerAddress(ServerAddress::TYPE_TCP, '127.0.0.1'),
        new ServerAddress(ServerAddress::TYPE_UDP, '[::1]'),
        new ServerAddress(ServerAddress::TYPE_TCP, '[::1]'),
    );

    yield (new Server($configuration, $encoder, $decoder))->start();
});
