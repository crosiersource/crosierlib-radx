<?php

namespace CrosierSource\CrosierLibRadxBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package CrosierSource\CrosierLibRadxBundle\DependencyInjection
 * @author Carlos Eduardo Pauluk
 */
class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        return new TreeBuilder();
    }

}