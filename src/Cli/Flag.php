<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Cli;

use League\CLImate\CLImate;

final class Flag
{
    private CLImate $climate;

    private string $short;

    private string $long;

    private string $text;

    private ?string $default;

    public function __construct(CLImate $climate, string $short, string $long, string $text, ?string $default = null)
    {
        $this->climate = $climate;
        $this->short   = ltrim($short, '-');
        $this->long    = ltrim($long, '-');
        $this->text    = $text;
        $this->default = $default;
    }

    public function getShort(): string
    {
        return $this->short;
    }

    public function getLong(): string
    {
        return $this->long;
    }

    public function hasDefault(): bool
    {
        return $this->default !== null;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function render(): void
    {
        $this->climate->info(sprintf('  %s|%s', $this->short, $this->long));
        $this->climate->br();
        $this->climate->white('    ' . $this->text);

        if ($this->default !== null) {
            $this->climate->br();
            $this->climate->out('    Defaults to: ' . $this->default);
        }

        $this->climate->br();
        $this->climate->br();
    }

    public function __debugInfo(): array
    {
        return [
            'short'   => $this->short,
            'long'    => $this->long,
            'text'    => $this->text,
            'default' => $this->default,
        ];
    }
}