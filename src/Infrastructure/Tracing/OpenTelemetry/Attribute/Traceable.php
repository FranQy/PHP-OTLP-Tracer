<?php

namespace Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\Attribute;

use Attribute;
use OpenTelemetry\API\Trace\SpanKind;

#[Attribute(Attribute::TARGET_METHOD)]
class Traceable
{
    public function __construct(
        public readonly ?string $spanName=null,
        public readonly bool $traceParams=false,
        public readonly int $kind = SpanKind::KIND_INTERNAL
    )
    {
    }
}
