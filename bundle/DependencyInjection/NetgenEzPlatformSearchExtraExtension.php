<?php

namespace Netgen\Bundle\EzPlatformSearchExtraBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;

class NetgenEzPlatformSearchExtraExtension extends Extension
{
    public function getAlias()
    {
        return 'netgen_ez_platform_search_extra';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($this->getAlias());
    }

    /**
     * @param array $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $activatedBundlesMap = $container->getParameter('kernel.bundles');

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../lib/Resources/config/')
        );

        if (array_key_exists('EzPublishLegacySearchEngineBundle', $activatedBundlesMap)) {
            $loader->load('search/legacy.yml');
        }

        if (array_key_exists('EzSystemsEzPlatformSolrSearchEngineBundle', $activatedBundlesMap)) {
            $loader->load('search/solr.yml');
        }

        $loader->load('search/common.yml');
        $loader->load('persistence.yml');


        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $processor = new ConfigurationProcessor($container, $this->getAlias());
        $processor->mapConfig(
            $config,
            function ($scopeSettings, $currentScope, ContextualizerInterface $contextualizer) {
                foreach ($scopeSettings as $key => $value) {
                    $contextualizer->setContextualParameter($key, $currentScope, $value);
                }
            }
        );

        $processor->mapConfigArray('highlighting', $config);

        if (!$container->hasParameter('netgen_ez_platform_search_extra.default.activate_highlighting')) {
            $container->setParameter('netgen_ez_platform_search_extra.default.activate_highlighting', false);
        }
    }
}
