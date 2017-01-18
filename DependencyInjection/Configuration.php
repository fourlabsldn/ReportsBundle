<?php

namespace FL\ReportsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fl_reports');

        $rootNode
            ->children()
                ->scalarNode('report_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('csv_encoder_service')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
