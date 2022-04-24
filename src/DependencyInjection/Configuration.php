<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Sets the configurable options, set which is required, its documentation and default values
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = $this->createTreeBuilderClass('kmi_system_information_bundle');

        $treeBuilder->getRootNode()
            ->children()
            // ToDo: Provide configuration opportunities
            ->end();

        return $treeBuilder;
    }

    /**
     * @param string $path
     *
     * @return TreeBuilder
     */
    public function createTreeBuilderClass(string $path): TreeBuilder
    {
        return new TreeBuilder($path);
    }
}
