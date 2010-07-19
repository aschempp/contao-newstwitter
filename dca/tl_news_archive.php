<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2009-2010
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_news_archive']['palettes']['__selector__'][] = 'twitter';
$GLOBALS['TL_DCA']['tl_news_archive']['palettes']['default'] .= ';{twitter_legend},twitter';
$GLOBALS['TL_DCA']['tl_news_archive']['subpalettes']['twitter'] = 'twitterAuth,twitterParams';


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_news_archive']['fields']['twitter'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_news_archive']['twitter'],
	'exclude'		=> true,
	'inputType'		=> 'checkbox',
	'eval'			=> array('submitOnChange'=>true),
);

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['twitterAuth'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_news_archive']['twitterAuth'],
	'exclude'		=> true,
	'inputType'		=> 'text',
	'eval'			=> array('multiple'=>true, 'size'=>2, 'doNotSaveEmpty'=>true, 'tl_class'=>'w50'),
	'load_callback'	=> array(array('NewsTwitter', 'loadAuth')),
	'save_callback'	=> array(array('NewsTwitter', 'saveAuth')),
);

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['twitterParams'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_news_archive']['twitterParams'],
	'exclude'		=> true,
	'inputType'		=> 'text',
	'eval'			=> array('maxlength'=>255, 'rgxp'=>'url', 'tl_class'=>'w50'),
);

