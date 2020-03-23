<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Binary;

use Amp\Loop;
use League\CLImate\CLImate;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\EncoderFactory;
use PeeHaa\AsyncDnsServer\Cli\Binary;
use PeeHaa\AsyncDnsServer\Cli\Command;
use PeeHaa\AsyncDnsServer\Cli\Flag;
use PeeHaa\AsyncDnsServer\Cli\Usage;
use PeeHaa\AsyncDnsServer\Configuration\Configuration;
use PeeHaa\AsyncDnsServer\Server;

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');
error_reporting(E_ALL);

$climate = new CLImate();

$defaultConfigurationFile = '/etc/async-dns-server.conf';

if (PHP_OS_FAMILY === 'Windows') {
    $defaultConfigurationFile = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'async-dns-server.conf';
}

$binary = (new Binary($climate, 'AsyncDnsServer - Asynchronous DNS Server'))
    ->addUsage(new Usage($climate, 'server setup'))
    ->addUsage(new Usage($climate, 'server start'))
    ->addCommand(new Command($climate, 'setup', 'Runs the configuration wizard'))
    ->addCommand(new Command($climate, 'start', 'Starts the server'))
    ->addFlag(new Flag($climate, '-h', '--help', 'Displays this help text'))
    ->addFlag(new Flag($climate, '-c', '--config', 'Path to the configuration file', $defaultConfigurationFile))
;

$executionContext = $binary->process($argv);

if ($executionContext->getCommand() === 'setup') {
    (new Command\Setup($climate))->run();

    exit(0);
}

Loop::run(static function () use ($executionContext) {
    $encoder = (new EncoderFactory())->create();
    $decoder = (new DecoderFactory())->create();

    $configuration = yield Configuration::fromConfigurationFile(
        $encoder,
        $decoder,
        $executionContext->getFlag('config')->getData(),
    );

    yield (new Server($configuration, $encoder, $decoder))->start();
});
