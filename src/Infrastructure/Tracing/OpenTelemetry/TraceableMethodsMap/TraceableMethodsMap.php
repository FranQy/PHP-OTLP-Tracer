<?php

declare(strict_types=1);

namespace Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\TraceableMethodsMap;


/**
 * @implements \Iterator<int, TraceableMethod>
 */
class TraceableMethodsMap implements \Iterator
{
    private array $map = [];

    public function addMethod(TraceableMethod $method): void
    {
        $this->map[] = $method;
    }

    /**
     * @return TraceableMethod|false
     */
    public function current(): TraceableMethod|false
    {
        return current($this->map);
    }

    public function next(): void
    {
        next($this->map);
    }

    /**
     * @return int|null
     */
    public function key(): int|null
    {
        return key($this->map);
    }


    public function valid(): bool
    {
        return key($this->map) !== null;
    }

    public function rewind(): void
    {
        reset($this->map);
    }
}
