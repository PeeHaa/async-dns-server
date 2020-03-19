<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Binary;

use Amp\Loop;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\EncoderFactory;
use PeeHaa\AsyncDnsServer\Logger\Factory;
use PeeHaa\AsyncDnsServer\Resolver\External;
use PeeHaa\AsyncDnsServer\Server;

require_once __DIR__ . '/../vendor/autoload.php';

Loop::run(static function () {
    $logger  = Factory::buildStdOutLogger();
    $encoder = (new EncoderFactory())->create();
    $decoder = (new DecoderFactory())->create();

    yield (new Server(
        $logger,
        new External($logger, $encoder, $decoder, '8.8.8.8'),
        $encoder,
        $decoder,
        '127.0.0.1', //'[::1]',
    ))->start();
});
