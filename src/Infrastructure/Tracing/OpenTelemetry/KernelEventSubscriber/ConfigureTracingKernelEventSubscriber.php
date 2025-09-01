<?php

namespace Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\KernelEventSubscriber;

use Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\TraceableMethodsMap\TraceableMethod;
use Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\TraceableMethodsMap\TraceableMethodsMap;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

class ConfigureTracingKernelEventSubscriber implements EventSubscriberInterface
{
    private const ATTRIBUTE_FUNCTION_PARAMS = 'code.function.params';

    private const INSTRUMENTATION_NAME = 'com.jaebestudio.php.symfony';

    /**
     * @var \OpenTelemetry\API\Trace\TracerInterface
     */
    private TracerInterface $tracer;

    public function __construct(
        private TraceableMethodsMap $traceableMethodsMap,
        // try to load friendsofopentelemetry/opentelemetry-bundle default tracer if available
        #[Autowire('@open_telemetry.traces.default_tracer')] ?TracerInterface $tracer = null,
    ) {
        // fallback to OpenTelemetry zero-code instrumentation tracer
        $this->tracer = $tracer ?? (new CachedInstrumentation(self::INSTRUMENTATION_NAME))->tracer();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['configureTracing', 128],
            ConsoleEvents::COMMAND => ['configureTracing', 128],
        ];
    }

    public function configureTracing(): void
    {
        $tracer = $this->tracer;
        foreach ($this->traceableMethodsMap as $methodDetails) {
            hook(
                class: $methodDetails->className,
                function: $methodDetails->methodName,
                pre: static function (
                    $object,
                    ?array $params,
                    ?string $class,
                    ?string $function,
                    ?string $filename,
                    ?int $lineno
                ) use (
                    $tracer,
                    $methodDetails
                ) {
                    $span = self::builder($tracer, $methodDetails, $params, $function, $class, $filename, $lineno)
                        ->startSpan();
                    Context::storage()->attach($span->storeInContext(Context::getCurrent()));
                },
                post: static function ($object, ?array $params, mixed $return, ?Throwable $exception) {
                    self::end($exception);
                }
            );
        }
    }

    /**
     * @param \OpenTelemetry\API\Trace\TracerInterface $tracer
     * @param \Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\TraceableMethodsMap\TraceableMethod $methodDetails
     * @param array<int,string>|null $params
     * @param string|null $method
     * @param string|null $class
     * @param string|null $filename
     * @param int|null $lineno
     * @return \OpenTelemetry\API\Trace\SpanBuilderInterface
     */
    private static function builder(
        TracerInterface $tracer,
        TraceableMethod $methodDetails,
        ?array $params,
        ?string $method,
        ?string $class,
        ?string $filename,
        ?int $lineno,
    ): SpanBuilderInterface {
        $params = $methodDetails->shouldTraceParams ? $params : null;
        $tracer = $tracer
            ->spanBuilder($methodDetails->spanName)
            ->setAttribute(TraceAttributes::CODE_FUNCTION_NAME, $class . '::' . $method)
            ->setAttribute(TraceAttributes::CODE_FILE_PATH, $filename)
            ->setAttribute(TraceAttributes::CODE_LINE_NUMBER, $lineno)
            ->setSpanKind($methodDetails->kind);

        $encodedParams = [];
        foreach ($params ?? [] as $position => $paramValue) {
            $encodedParams[$methodDetails->traceableParamsNames[$position]] = json_encode($paramValue);
        }

        $tracer->setAttribute(self::ATTRIBUTE_FUNCTION_PARAMS, $encodedParams);

        return $tracer;
    }

    private static function end(?Throwable $exception): void
    {
        $scope = Context::storage()->scope();
        if (!$scope) {
            return;
        }

        $scope->detach();
        $span = Span::fromContext($scope->context());

        if ($exception) {
            $span->recordException($exception, [TraceAttributes::EXCEPTION_ESCAPED => true]);
            $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        }

        $span->end();
    }
}
