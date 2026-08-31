<?php

declare(strict_types=1);

use Contao\Backend;
use Contao\Config;
use Contao\Date;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;

/**
 * Tabelle tl_linktracker_items
 */
$GLOBALS['TL_DCA']['tl_linktracker_items'] = array
(

	// Config
	'config' => array
	(
		// Der Kurzname 'Table' ist unter Contao 5 entfallen, der vollständige
		// Klassenname wird von beiden Fassungen verstanden.
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_linktracker',
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'disableGrouping'         => true,
			'fields'                  => array('clickTime DESC'),
			'headerFields'            => array('id', 'title', 'url'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_linktracker_items', 'listRecords'),
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
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker_items']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker_items']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker_items']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker_items']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			// Umschalter des Contao-Kerns statt des Haste-Umschalters, siehe
			// die gleichlautende Stelle in tl_linktracker.
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker_items']['toggle'],
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker_items']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{tracker_legend},clickTime,ip,browser,viaLegacy;{publish_legend},published'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_linktracker.id',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'clickTime' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker_items']['clickTime'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'ip' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker_items']['ip'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>128, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'browser' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker_items']['browser'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		// Hält fest, ob der Klick über die alte Adresse
		// bundles/contaolinktracker/go.php hereinkam. Ohne diese Angabe lässt
		// sich nicht beurteilen, ob die alte Adresse noch gebraucht wird: Aus
		// Zeitpunkt, IP und Browserkennung geht die Herkunft nicht hervor.
		// Datensätze aus der Zeit vor Version 2.1.0 haben hier den Vorgabewert
		// und zählen damit als "nicht über die alte Adresse" — sie stammen aber
		// zum grossen Teil von dort. Aussagekräftig ist deshalb erst, was nach
		// dem Update hinzukommt.
		'viaLegacy' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker_items']['viaLegacy'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class' => 'w50', 'isBoolean' => true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker_items']['published'],
			'exclude'                 => true,
			'search'                  => false,
			'sorting'                 => false,
			'filter'                  => true,
			'toggle'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class' => 'w50', 'isBoolean' => true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
	)
);

/**
 * Hilfsmethoden für den Data Container tl_linktracker_items.
 *
 * Die Klasse leitet von Contao\Backend ab, damit Contao sie über
 * System::importStatic() erzeugen kann. Der vollständige Namensraum ist
 * Pflicht: Contao 5 registriert keine globalen Klassenaliasse mehr.
 */
class tl_linktracker_items extends Backend
{
	/**
	 * Erzeugt die einzeilige Darstellung eines Klicks in der Listenansicht.
	 *
	 * Gezeigt werden Zeitpunkt, IP-Adresse und Browserkennung. Das Datumsformat
	 * kommt aus den Contao-Einstellungen statt aus einer festen Zeichenkette,
	 * damit die Ausgabe zum Rest des Backends passt.
	 *
	 * @param array<string,mixed> $arrRow Der Klickdatensatz aus tl_linktracker_items
	 *
	 * @return string Der fertige HTML-Block für die Zeile
	 */
	public function listRecords($arrRow)
	{
		$strZeit = Date::parse(Config::get('datimFormat'), (int) $arrRow['clickTime']);

		return '<div class="tl_content_left"><b>' . $strZeit . '</b> '
			. StringUtil::specialchars((string) $arrRow['ip']) . ' &ndash; '
			. StringUtil::specialchars((string) $arrRow['browser'])
			. '</div>';
	}
}
