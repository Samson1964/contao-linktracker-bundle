<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package News
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Table tl_linktracker
 */
$GLOBALS['TL_DCA']['tl_linktracker'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ctable'                      => array('tl_linktracker_items'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'    => 'primary',
				'title' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('title'),
			'flag'                    => 1,
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('id', 'title', 'url', 'hits'),
			'showColumns'             => true,
			'label_callback'          => array('tl_linktracker', 'viewLabels'),
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['edit'],
				'href'                => 'table=tl_linktracker_items',
				'icon'                => 'edit.gif'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif',
				'button_callback'     => array('tl_linktracker', 'editHeader')
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif',
				'button_callback'     => array('tl_linktracker', 'copyArchive')
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_linktracker', 'deleteArchive')
			),
			'toggle' => array
			(
				'label'                => &$GLOBALS['TL_LANG']['tl_linktracker']['toggle'],
				'attributes'           => 'onclick="Backend.getScrollOffset()"',
				'haste_ajax_operation' => array
				(
					'field'            => 'published',
					'options'          => array
					(
						array('value' => '', 'icon' => 'invisible.svg'),
						array('value' => '1', 'icon' => 'visible.svg'),
					),
				),
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},title,url;{publish_legend},published'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker']['id'],
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker']['url'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker']['published'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'default'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array
			(
				'doNotCopy'           => true
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),  
		'hits' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker']['hits'],
		),
	)
);


/**
 * Class tl_linktracker
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    News
 */
class tl_linktracker extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Return the edit header button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function editHeader($row, $href, $label, $title, $icon, $attributes)
	{
		return ($this->User->isAdmin || count(preg_grep('/^tl_linktracker::/', $this->User->alexf)) > 0) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
	}


	/**
	 * Return the copy archive button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function copyArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return ($this->User->isAdmin || $this->User->hasAccess('create', 'newp')) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
	}


	/**
	 * Return the delete archive button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function deleteArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return ($this->User->isAdmin || $this->User->hasAccess('delete', 'newp')) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
	}

	/**
	 * Zeigt zu einem Datensatz die eingetragenen Lizenzen an
	 *
	 * @param array                $row
	 * @param string               $label
	 * @param Contao\DataContainer $dc
	 * @param array                $args        Index 6 ist das Feld lizenzen
	 *
	 * @return array
	 */
	public function viewLabels($row, $label, Contao\DataContainer $dc, $args)
	{

		// Datum heute, gestern, vorgestern usw. festlegen
		$zeit = time();
		$tag = date("d", $zeit); // Tag
		$monat = date("m", $zeit); // Monat
		$jahr = date("Y", $zeit); // Jahr
		$abstand = array();
		$abstand[0] = mktime(0, 0, 0, $monat, $tag, $jahr);
		$abstand[1] = strtotime("-1 day", $abstand[0]);
		$abstand[2] = strtotime("-2 day", $abstand[0]);
		$abstand[3] = strtotime("-3 day", $abstand[0]);
		$abstand[4] = strtotime("-4 day", $abstand[0]);

		// Gesamtaufrufe des Links laden
		$objHits = \Database::getInstance()->prepare("SELECT * FROM tl_linktracker_items WHERE pid = ?")
		                                   ->execute($row['id']);
		if($objHits->numRows)
		{
			$args[3] = '<b>'.$objHits->numRows.'</b> (';
			$tage = array();
			// Vorherige Tage zählen
			for($x = 0; $x < count($abstand); $x++)
			{
				if($x == 0) $bis = $zeit;
				else $bis = $abstand[$x-1];
				$objHits = \Database::getInstance()->prepare("SELECT * FROM tl_linktracker_items WHERE pid = ? AND clickTime >= ? AND clickTime <= ?")
				                                   ->execute($row['id'], $abstand[$x], $bis);
				if($objHits->numRows)
				{
					$tage[] = $objHits->numRows;
				}
				else
				{
					$tage[] = 0;
				}
			}
			$args[3] .= implode('/', $tage);
			$args[3] .= ')';
		}
		else
		{
			$args[3] = 0;
		}

		// Hilfetext hinzufügen
		$args[3] = '<span title="Gesamt (Heute und letzte 4 Tage)">'.$args[3].'</span>';

		// Datensatz komplett zurückgeben
		return $args;
	}

}
