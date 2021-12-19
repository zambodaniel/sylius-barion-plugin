<?php

declare(strict_types=1);

namespace ZamboDaniel\SyliusBarionPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('zambodaniel_sylius_barion_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
