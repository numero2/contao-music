<?php

/**
 * Music bundle for Contao Open Source CMS
 *
 * @author    Christopher Brandt <christopher.brandt@numero2.de>
 * @license   LGPL
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MusicBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;


class MusicBundle extends Bundle {


    /**
     * {@inheritdoc}
     */
    public function getPath(): string {

        return \dirname(__DIR__);
    }
}