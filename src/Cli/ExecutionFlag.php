<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Cli;

final class ExecutionFlag
{
    private string $flag;

    private ?string $data;

    public function __construct(string $flag, ?string $data = null)
    {
        $this->flag = $flag;
        $this->data = $data;
    }

    public function getFlag(): string
    {
        return $this->flag;
    }

    public function getData(): ?string
    {
        return $this->data;
    }
}
