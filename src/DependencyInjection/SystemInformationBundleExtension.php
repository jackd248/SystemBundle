<?php

namespace Kmi\SystemInformationBundle\DependencyInjection;

use Kmi\SystemInformationBundle\SystemInformationBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SystemInformationBundleExtension extends ConfigurableExtension
{

    /**
     * Load the configuration and inject its values
     * https://symfony.com/doc/current/bundles/configuration.html
     *
     * @param array $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function loadInternal(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(dirname(__DIR__) . '/Resources/config')
        );
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

//        $container->setParameter(
//            'acme_hello.my_service_type',
//            $config
//        );

//        $definition = $container->getDefinition('kmi_system_information_bundle.configuration_loader');
//        $definition->replaceArgument(0, $config);

        $container->setParameter(SystemInformationBundle::BUNDLE_CONFIG_NAME, $config);
    }

    /**
     * https://stackoverflow.com/a/64614381
     */
    public function prepend(ContainerBuilder $container)
    {
        $thirdPartyBundlesViewFileLocator = (new FileLocator(__DIR__ . '/../Resources/views/bundles'));

        $container->loadFromExtension('twig', [
            'paths' => [
                $thirdPartyBundlesViewFileLocator->locate('SonataAdminBundle') => 'SonataAdmin',
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return SystemInformationBundle::BUNDLE_CONFIG_NAME;
    }
}