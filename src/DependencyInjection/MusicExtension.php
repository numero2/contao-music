<?php

/**
 * Music bundle for Contao Open Source CMS
 *
 * @author    Christopher Brandt <christopher.brandt@numero2.de>
 * @license   LGPL
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MusicBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


class MusicExtension extends Extension {


    public function load(array $mergedConfig, ContainerBuilder $container): void {

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('controller.yaml');
    }
}
