<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Cli\Command;

use League\CLImate\CLImate;

final class Setup
{
    private CLImate $climate;

    private Logger $logger;

    private Resolver $resolver;

    private Caching $caching;

    public function __construct(CLImate $climate)
    {
        $this->climate = $climate;

        $this->logger   = new Logger($climate);
        $this->resolver = new Resolver($climate);
        $this->caching  = new Caching($climate);
    }

    public function run(): void
    {
        $this->climate->br();

        $this->climate->info('AsyncDnsServer - Configuration wizard');

        $this->logger->run();
        $this->resolver->run();
        $this->caching->run();

        $this->verifySettings();
    }

    private function verifySettings(): void
    {
        $this->climate->br();

        $this->climate->info('Settings to be saved:');

        $this->climate->br();

        $i = 0;

        $cacheSettings = $this->caching->getCurrentSettings();

        $cachingValue = 'Disabled';

        if ($cacheSettings) {
            $cachingValue = ucfirst($cacheSettings['type']);

            if (isset($cacheSettings['address'])) {
                $cachingValue = sprintf('%s (%s:%d)', $cachingValue, $cacheSettings['address'][0], $cacheSettings['address'][1]);
            }
        }

        $this->climate->table([
            [
                '#'        => ++$i,
                'settings' => 'Logger',
                'value'    => $this->logger->getCurrentSettings(),
            ],
            [
                '#'        => ++$i,
                'settings' => 'Caching',
                'value'    => $cachingValue,
            ],
        ]);

        $this->climate->br();

        $table     = [];
        $resolvers = [];

        $this->climate->info('Resolvers');

        foreach ($this->resolver->getCurrentSettings() as $index =>  $resolver) {
            $table[] =             [
                '#'             => ++$i,
                'resolver'      => $resolver['resolver'],
                'configuration' => $resolver['configuration'],
            ];

            $resolvers[$i] = $index;
        }

        $this->climate->table($table);

        $this->climate->br();

        $response = $this->climate
            ->input('Type y to accept the current settings or the number of the setting you want to change: [y]')
            ->accept(array_merge(['y'], range(1, $i)))
            ->defaultTo('y')
            ->prompt()
        ;

        if (array_key_exists($response, $resolvers)) {
            $this->resolver->change($resolvers[$response]);

            $this->verifySettings();

            return;
        }

        switch ($response) {
            case '1':
                $this->logger->run();
                $this->verifySettings();
                break;

            case '2':
                $this->caching->run();
                $this->verifySettings();
                break;

            case 'y':
                $this->createConfigFile();
                $this->finish();
        }
    }

    private function createConfigFile(): void
    {
        $configFile = <<<CONFIG
; Configuration file for Async DNS Server

[log]

; Possible loggers are: NullLogger and StdOutLogger

type="{logger}"

[resolvers]

; Resolvers are executed in sequence

{resolvers}

[cache]

enabled={cacheEnabled}
; memory or redis
type="{cacheType}"

[redis]

host="{redisHost}"
port={redisPort}

CONFIG;

        $configFile = str_replace('{logger}', $this->logger->getCurrentSettings(), $configFile);
        $configFile = str_replace('{cacheEnabled}', $this->caching->getCurrentSettings() ? 'true' : 'false', $configFile);
        $configFile = str_replace('{cacheType}', $this->caching->getCurrentSettings()['type'], $configFile);

        if ($this->caching->getCurrentSettings()['type'] === 'redis') {
            $configFile = str_replace('{redisHost}', $this->caching->getCurrentSettings()['address'][0], $configFile);
            $configFile = str_replace('{redisPort}', $this->caching->getCurrentSettings()['address'][1], $configFile);
        } else {
            $configFile = str_replace('{redisHost}', '127.0.0.1', $configFile);
            $configFile = str_replace('{redisPort}', '6379', $configFile);
        }

        $resolvers = [];

        foreach ($this->resolver->getCurrentSettings() as $resolver) {
            $configuration = '';

            if ($resolver['configuration']) {
                $configuration = sprintf('/%s', $resolver['configuration']);
            }

            $resolvers[] = sprintf('resolver[]="%s%s"', $resolver['resolver'], $configuration);
        }

        $configFile = str_replace('{resolvers}', implode(PHP_EOL, $resolvers), $configFile);

        $filename = '/etc/async-dns-server.conf';

        if (PHP_OS_FAMILY === 'Windows') {
            $filename = realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR . 'async-dns-server.conf';
        }

        $this->climate->br();

        $this->climate->info(sprintf('Writing settings to file %s', $filename));

        file_put_contents($filename, $configFile);
    }

    private function finish(): void
    {
        $this->climate->br();

        $this->climate->info('Initialization is finished');
    }
}
