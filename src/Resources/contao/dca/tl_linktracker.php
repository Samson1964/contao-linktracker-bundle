<?php

declare(strict_types=1);

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tabelle tl_linktracker
 */
$GLOBALS['TL_DCA']['tl_linktracker'] = array
(

	// Config
	'config' => array
	(
		// Der Kurzname 'Table' ist unter Contao 5 entfallen, der vollständige
		// Klassenname wird von beiden Fassungen verstanden.
		'dataContainer'               => DC_Table::class,
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
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('title', 'url'),
			'flag'                    => DataContainer::SORT_ASC,
			'panelLayout'             => 'filter,sort;search,limit'
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
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			// Der Umschalter kommt jetzt vom Contao-Kern statt von der
			// Haste-Erweiterung. Contao 4.13 wie Contao 5 erkennen an
			// "act=toggle&field=…" von selbst, dass hier ein Umschalter mit
			// Ajax und wechselndem Symbol gerendert werden soll; Voraussetzung
			// ist allein 'toggle' => true am Feld published.
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['toggle'],
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			),
			'statistik' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_linktracker']['statistik'],
				'href'                => 'key=statistik',
				'icon'                => 'bundles/contaolinktracker/counter.png',
			),
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},title,url;{einbindung_legend},einbindung;{publish_legend},published'
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
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker']['url'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		// Reines Anzeigefeld ohne Datenbankspalte: Es zeigt, wie sich dieser
		// Datensatz einbinden lässt. Bewusst ohne 'exclude', weil Contao 4.13
		// ausgeschlossene Felder mit input_field_callback gar nicht erst
		// darstellt.
		'einbindung' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker']['einbindung'],
			'input_field_callback'    => array('tl_linktracker', 'showEinbindung'),
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_linktracker']['published'],
			'exclude'                 => true,
			'filter'                  => true,
			'default'                 => true,
			'toggle'                  => true,
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
 * Hilfsmethoden für den Data Container tl_linktracker.
 *
 * Die Klasse leitet von Contao\Backend ab, damit Contao sie über
 * System::importStatic() erzeugen kann. Der vollständige Namensraum ist
 * Pflicht: Contao 5 registriert keine globalen Klassenaliasse mehr.
 */
class tl_linktracker extends Backend
{
	/**
	 * Ergänzt die Spalte "Aufrufe" in der Listenansicht um die tatsächlichen
	 * Klickzahlen.
	 *
	 * Ausgegeben wird die Gesamtzahl der Klicks und dahinter in Klammern die
	 * Zahl für heute sowie die vier vorangegangenen Tage, durch Schrägstriche
	 * getrennt. Das Feld "hits" hat keine Datenbankspalte, es dient nur als
	 * Platzhalter für diese Ausgabe.
	 *
	 * @param array<string,mixed> $row   Der Datensatz aus tl_linktracker
	 * @param string              $label Das bereits erzeugte Label; hier ungenutzt,
	 *                                   weil bei showColumns die Spalten aus $args
	 *                                   gebildet werden
	 * @param DataContainer       $dc    Der aufrufende Data Container; hier ungenutzt
	 * @param array<int,string>   $args  Die Spaltenwerte; Index 3 ist die Spalte
	 *                                   "Aufrufe"
	 *
	 * @return array<int,string> Die Spaltenwerte mit gefüllter Aufrufspalte
	 */
	public function viewLabels($row, $label, DataContainer $dc, $args)
	{
		$objConnection = System::getContainer()->get('database_connection');

		// Tagesgrenzen bestimmen: heute 0 Uhr und die vier Tage davor.
		$intHeute = mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y'));
		$arrGrenzen = array($intHeute);

		for ($i = 1; $i <= 4; $i++)
		{
			$arrGrenzen[$i] = (int) strtotime('-' . $i . ' day', $intHeute);
		}

		$intGesamt = (int) $objConnection->fetchOne(
			'SELECT COUNT(*) FROM tl_linktracker_items WHERE pid = ?',
			array($row['id'])
		);

		if (0 === $intGesamt)
		{
			$args[3] = '0';

			return $args;
		}

		// COUNT(*) statt der früheren Zählung über numRows: Jene las bei jedem
		// Seitenaufbau sämtliche Klickdatensätze in den Speicher, und das für
		// jede Zeile der Liste fünfmal.
		$arrTage = array();

		foreach ($arrGrenzen as $i => $intVon)
		{
			$intBis = (0 === $i) ? time() : $arrGrenzen[$i - 1];

			$arrTage[] = (int) $objConnection->fetchOne(
				'SELECT COUNT(*) FROM tl_linktracker_items WHERE pid = ? AND clickTime >= ? AND clickTime <= ?',
				array($row['id'], $intVon, $intBis)
			);
		}

		$args[3] = sprintf(
			'<span title="%s"><b>%d</b> (%s)</span>',
			StringUtil::specialchars($GLOBALS['TL_LANG']['tl_linktracker']['hitsHelp'] ?? ''),
			$intGesamt,
			implode('/', $arrTage)
		);

		return $args;
	}

	/**
	 * Erzeugt den Hinweis, wie sich der gerade bearbeitete Link einbinden lässt.
	 *
	 * Gezeigt werden die beiden Insert-Tags und die unmittelbare Adresse,
	 * jeweils mit der echten ID des Datensatzes eingesetzt, damit sie sich
	 * unverändert übernehmen lassen. Die Adresse stammt vom Router und nicht aus
	 * einer festen Zeichenkette, damit sie auch dann stimmt, wenn Contao in
	 * einem Unterverzeichnis läuft.
	 *
	 * @param DataContainer $dc     Liefert über id den Datensatz, um den es geht
	 * @param string        $xlabel Zusätzliche Beschriftungen (Assistenten); wird
	 *                              hier nicht gebraucht, gehört aber zur Signatur
	 *
	 * @return string Der fertige HTML-Block für die Eingabemaske
	 */
	public function showEinbindung(DataContainer $dc, $xlabel = '')
	{
		$intId = (int) $dc->id;

		$strUrl = System::getContainer()->get('router')->generate(
			'linktracker_go',
			array('id' => $intId),
			UrlGeneratorInterface::ABSOLUTE_URL
		);

		$arrBeispiele = array(
			array(
				'label' => $GLOBALS['TL_LANG']['tl_linktracker']['einbindungLink'] ?? '',
				'code'  => '<a href="{{linktracker::' . $intId . '}}">…</a>',
			),
			array(
				'label' => $GLOBALS['TL_LANG']['tl_linktracker']['einbindungBild'] ?? '',
				'code'  => '{{linktracker::' . $intId . '::image}}',
			),
			array(
				'label' => $GLOBALS['TL_LANG']['tl_linktracker']['einbindungUrl'] ?? '',
				'code'  => $strUrl,
			),
		);

		$strReturn = '<div class="widget">';
		$strReturn .= '<p class="tl_help" style="margin-bottom:9px">' . StringUtil::specialchars($GLOBALS['TL_LANG']['tl_linktracker']['einbindungHelp'] ?? '') . '</p>';

		foreach ($arrBeispiele as $arrBeispiel)
		{
			$strReturn .= '<h3><label>' . StringUtil::specialchars($arrBeispiel['label']) . '</label></h3>';

			// Eingabefeld statt <code>: So lässt sich der Text mit einem Klick
			// markieren und übernehmen. readonly verhindert versehentliche
			// Änderungen, gespeichert wird ohnehin nichts — das Feld hat keine
			// Datenbankspalte.
			$strReturn .= '<input type="text" class="tl_text" readonly onclick="this.select()" value="' . StringUtil::specialchars($arrBeispiel['code']) . '" style="width:100%">';
		}

		$strReturn .= '</div>';

		return $strReturn;
	}
}
