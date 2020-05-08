<?php

namespace CrosierSource\CrosierLibRadxBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class CrosierLibRadxExtension
 *
 * @package CrosierSource\CrosierLibRadxBundle\DependencyInjection
 * @author Carlos Eduardo Pauluk
 */
class CrosierLibRadxExtension extends Extension
{

    /**
     * Loads a specific configuration.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services_crm.xml');
        $loader->load('services_estoque.xml');
        $loader->load('services_financeiro.xml');
        $loader->load('services_fiscal.xml');
        $loader->load('services_rh.xml');
        $loader->load('services_vendas.xml');
    }


}