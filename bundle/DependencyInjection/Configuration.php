<?php

namespace Netgen\Bundle\EzPlatformSearchExtraBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration extends SiteAccessConfiguration
{
    protected $rootNodeName;

    public function __construct($rootNodeName)
    {
        $this->rootNodeName = $rootNodeName;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->rootNodeName);

        $this->addConfiguration($rootNode);

        return $treeBuilder;
    }

    protected function addConfiguration($rootNode)
    {
        $systemNode = $this->generateScopeBaseNode($rootNode);
        $systemNode
            ->booleanNode('activate_highlighting')
                ->info('Enables highlighting feature')
            ->end()
            ->arrayNode('highlighting')
                ->useAttributeAsKey('content_type')
                ->normalizeKeys(false)
                ->arrayPrototype()
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
