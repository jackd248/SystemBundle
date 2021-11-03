<?php

namespace Kmi\SystemInformationBundle\DependencyInjection;

use Kmi\SystemInformationBundle\SystemInformationBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SystemInformationBundleExtension extends Extension
{

    /**
     */
    public function load(array $configs, ContainerBuilder $container): array
    {
        $configuration = $this->getConfiguration($configs, $container);

        return [];
    }

    public function getAlias(): string
    {
        return SystemInformationBundle::BUNDLE_CONFIG_NAME;
    }


}