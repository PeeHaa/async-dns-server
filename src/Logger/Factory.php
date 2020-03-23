<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Logger;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger as MonologLogger;
use Psr\Log\NullLogger;
use function Amp\ByteStream\getStdout;

final class Factory
{
    public static function buildStdOutLogger(): Logger
    {
        $handler = new StreamHandler(getStdout());
        $handler->setFormatter(new ConsoleFormatter());

        $logger = new MonologLogger('dns');
        $logger->pushHandler($handler);

        return new Logger($logger);
    }

    public static function buildNullLogger(): Logger
    {
        return new Logger(new NullLogger());
    }
}
