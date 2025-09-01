<?php

namespace Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\TraceableMethodsMap;

class TraceableMethod
{
    /**
     * @param string $className
     * @param string $methodName
     * @param string $spanName
     * @param bool $shouldTraceParams
     * @param array<int,string> $traceableParamsNames
     * @param 0|1|2|3|4 $kind
     * @psalm-param SpanKind::KIND_* $kind
     */
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
        public readonly string $spanName,
        public readonly bool $shouldTraceParams,
        public readonly array $traceableParamsNames,
        public readonly int $kind,
    ) {
    }
}
