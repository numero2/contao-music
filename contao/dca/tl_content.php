<?php

/**
 * Music bundle for Contao Open Source CMS
 *
 * @author    Christopher Brandt <christopher.brandt@numero2.de>
 * @license   LGPL
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */

/**
 * Modify the palettes
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'musicSplashImage';
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'musicPlayerSize';

$GLOBALS['TL_DCA']['tl_content']['palettes']['spotify'] = '{type_legend}title,type,headline;{source_legend},sourceId;{player_legend},spotifyVideo,spotifyTheme,musicPlayerSize,caption;{splash_legend},musicSplashImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['apple'] = '{type_legend}title,type,headline;{source_legend},sourceId;{player_legend},appleTheme,caption;{splash_legend},musicSplashImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['musicSplashImage'] = 'musicSplashSRC,size';

/**
 * Modify the fields
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['sourceId'] = [
    'inputType'     => 'text'
,   'eval'          => ['tl_class'=>'w50', 'mandatory'=>true]
,   'sql'           => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['spotifyVideo'] = [
    'inputType'             => 'checkbox'
,   'eval'                  => ['tl_class'=>'w50']
,   'sql'                   => ['type'=>'boolean', 'default'=>false]
];

$GLOBALS['TL_DCA']['tl_content']['fields']['spotifyTheme'] = [
    'inputType'           => 'select'
    ,   'options'             => ['0', '1']
    ,   'reference'           => &$GLOBALS['TL_LANG']['tl_content']['spotifyThemes']
    ,   'eval'                => ['tl_class'=>'clr w25']
    ,   'sql'                 => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['appleTheme'] = [
    'inputType'           => 'select'
,   'options'             => ['auto', 'light', 'dark']
,   'reference'           => &$GLOBALS['TL_LANG']['tl_content']['appleThemes']
,   'eval'                => ['tl_class'=>'w25']
,   'sql'                 => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['musicPlayerSize'] = [
    'inputType'           => 'select'
,   'options'             => ['compact', 'big']
,   'reference'           => &$GLOBALS['TL_LANG']['tl_content']['musicPlayerSizes']
,   'eval'                => ['tl_class'=>'w25', 'submitOnChange'=>true]
,   'sql'                 => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['musicSplashSRC'] = [
    'inputType'     => 'fileTree'
,   'eval'          => ['filesOnly'=>true, 'fieldType'=>'radio', 'extensions'=>'%contao.image.valid_extensions%', 'tl_class'=>'clr']
,   'sql'           => "binary(16) NULL"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['musicSplashImage'] = [
    'inputType'             => 'checkbox'
,   'eval'                  => ['tl_class'=>'clr', 'submitOnChange'=>true]
,   'sql'                   => ['type'=>'boolean', 'default'=>false]
];

$GLOBALS['TL_DCA']['tl_content']['fields']['caption']['eval']['tl_class'] = 'clr w50';