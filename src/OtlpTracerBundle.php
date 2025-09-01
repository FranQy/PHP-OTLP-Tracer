<?php

declare(strict_types=1);

namespace Jaebe\OtlpTracer;

use Jaebe\OtlpTracer\Infrastructure\Tracing\OpenTelemetry\DependencyInjection\TraceableAttributeCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class OtlpTracerBundle extends AbstractBundle
{

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new TraceableAttributeCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
    }
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/../config/services.yaml');
    }
}
