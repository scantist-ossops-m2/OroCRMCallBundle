<?php

namespace Oro\Bundle\CallBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CallBundle\DependencyInjection\OroCallExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCallExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroCallExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
