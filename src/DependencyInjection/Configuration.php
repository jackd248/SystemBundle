<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\DependencyInjection;

use Kmi\SystemInformationBundle\SystemInformationBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Sets the configurable options, set which is required, its documentation and default values
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = $this->createTreeBuilderClass(SystemInformationBundle::BUNDLE_CONFIG_NAME);

        $treeBuilder->getRootNode()
//            ->children()
//                ->integerNode('default_cache_lifetime')
//                    ->defaultValue(300)
//                    ->info('Default cache lifetime for system checks')
//                ->end()
//                ->integerNode('dependency_cache_lifetime')
//                    ->defaultValue(86400)
//                    ->info('Default cache lifetime for dependency checks')
//                ->end()
//                ->scalarNode('log_analyse_period')
//                    ->defaultValue('-1 day')
//                    ->info('Period for log analysis')
//                ->end()
//                ->scalarNode('log_date_format')
//                    ->defaultValue('d.m.Y H:i:s')
//                    ->info('Date format for application log')
//                ->end()
//                ->integerNode('log_max_file_size')
//                    ->defaultValue(20000000)
//                    ->info('Default cache lifetime for dependency checks')
//                ->end()
//                ->scalarNode('mail_sender')
//                    ->info('Sender mail address')
//                ->end()
//            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @param string $path
     *
     * @return TreeBuilder
     */
    public function createTreeBuilderClass(string $path)
    {
        return new TreeBuilder($path);
    }
}
