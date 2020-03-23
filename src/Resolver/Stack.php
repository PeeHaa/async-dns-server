<?php declare(strict_types=1);

namespace PeeHaa\AsyncDnsServer\Resolver;

use Amp\Promise;
use PeeHaa\AsyncDnsServer\Message;
use function Amp\call;

final class Stack implements Resolver
{
    /**
     * @var array<Resolver>
     */
    private array $resolvers = [];

    public function __construct(Resolver $resolver, Resolver ...$resolvers)
    {
        $this->resolvers = array_merge([$resolver], $resolvers);
    }

    /**
     * @inheritDoc
     */
    public function query(Message $message): Promise
    {
        return call(function () use ($message) {
            foreach ($this->resolvers as $resolver) {
                /** @var Message $answer */
                $answer = yield $resolver->query($message);

                if ($answer->getAnswerRecords()->count()) {
                    return $answer;
                }
            }

            return $answer;
        });
    }
}
