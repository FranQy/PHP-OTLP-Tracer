<?php

namespace Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\TraceableMethodsMap;

class TraceableMethod
{

    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
        public readonly string $spanName,
        public readonly bool $shouldTraceParams,
        public readonly array $traceableParamsNames,
        public readonly int $kind,
    )
    {
    }
}
