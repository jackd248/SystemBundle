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
                ->scalarNode('filtering_entity')
                    ->info('This value defines the name of the entity to be searched in, e.g. "Museum".')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('namespace')
                    ->info('This value defines the namespace of the `filtering_entity` and all entities ' .
                        'to be joined, e.g. "App\Entity\Museum".')
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('attribute_depth')
                    ->info('This value defines how deep child relations will be followed and searched.')
                    ->defaultValue(3)
                ->end()
                ->arrayNode('attribute_blacklist')
                    ->info('This value defines a list of attributes for which entities can not be searched for ' .
                        ', e.g. [\'*.id\', \'adresse.plz\'].')
                    ->scalarPrototype()
                    ->end()
                ->end()
                ->arrayNode('level_attribute_blacklist')
                    ->info('This value defines a list of related entities on which attribute search shall not be ' .
                        'available, e.g. [\'museum.category\', \'museum.additionals\'].')
                    ->scalarPrototype()
                    ->end()
                ->end()
                ->arrayNode('attribute_special_types')
                    ->info('This value defines a list of special object-types for attributes which should be used ' .
                        'instead of the given ones in the entity, e.g. for Times and Taxons.')
                    ->variablePrototype()
                    ->end()
                ->end()
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
