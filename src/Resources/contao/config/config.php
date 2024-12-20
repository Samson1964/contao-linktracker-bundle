<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   bdf
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

/**
 * Backend-Module
 */
array_insert($GLOBALS['BE_MOD']['content'], 1, array
(
	'linktracker' => array
	(
		'tables'         => array('tl_linktracker', 'tl_linktracker_items'),
		'statistik'      => array('Schachbulle\ContaoLinktrackerBundle\Modules\Statistik', 'go'),
	)
));

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('Schachbulle\ContaoLinktrackerBundle\Tags\Linktracker', 'go');
