<?php

declare(strict_types=1);

namespace Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\DependencyInjection;

use Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\Attribute\Traceable;
use Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\TraceableMethodsMap\TraceableMethod;
use Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\TraceableMethodsMap\TraceableMethodsMap;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TraceableAttributeCompilerPass implements CompilerPassInterface
{
    private const NAMESPACE_SEPARATOR = '::';

    public function process(ContainerBuilder $container): void
    {
        $amqpMessagesClassMapDefinition = $container->findDefinition(TraceableMethodsMap::class);

        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();
            if (!$class || !class_exists($class)) {
                continue;
            }

            $reflectionClass = new ReflectionClass($class);
            foreach ($reflectionClass->getMethods() as $method) {
                $attributes = $method->getAttributes(Traceable::class);

                if (!empty($attributes)) {
                    $attribute = $attributes[0]->newInstance();
                    $paramsNames = [];
                    if ($attribute->traceParams) {
                        foreach ($method->getParameters() as $param) {
                            $paramsNames[] = $param->name;
                        }
                    }

                    $inlineTraceable = new Definition(
                        TraceableMethod::class,
                        [
                            $class,
                            $method->name,
                            $attribute->spanName ?? ($class . self::NAMESPACE_SEPARATOR . $method->name),
                            $attribute->traceParams,
                            $paramsNames,
                            $attribute->kind,
                        ]
                    );
                     $inlineTraceable->setShared(false);


                    $amqpMessagesClassMapDefinition->addMethodCall(
                        'addMethod',
                        [$inlineTraceable]
                    );
                }
            }
        }
    }
}
