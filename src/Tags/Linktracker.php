<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\Tags;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Stellt das Insert-Tag {{linktracker::ID}} bereit.
 *
 * Die Klasse hängt am Hook replaceInsertTags und wird als Dienst registriert.
 * Sie erbt bewusst nicht mehr von Contao\Frontend: Contao 5 registriert keine
 * globalen Klassenaliasse mehr, und die geerbten Eigenschaften wurden hier
 * ohnehin nie genutzt.
 */
class Linktracker
{
	/**
	 * @param UrlGeneratorInterface $router Erzeugt die Adresse der Tracking-Route.
	 *                                      Früher wurde sie aus Environment::get('url')
	 *                                      und einem festen Pfad zusammengesetzt; über
	 *                                      den Router bleibt sie auch dann richtig,
	 *                                      wenn Contao in einem Unterverzeichnis liegt.
	 */
	public function __construct(private readonly UrlGeneratorInterface $router)
	{
	}

	/**
	 * Löst das Insert-Tag {{linktracker::ID}} beziehungsweise
	 * {{linktracker::ID::image}} auf.
	 *
	 * Ohne zweiten Parameter kommt die nackte Adresse des getrackten Links
	 * zurück, die im href-Attribut eines Verweises stehen kann. Mit dem
	 * Parameter "image" kommt ein fertiges img-Element zurück, das eine
	 * transparente 1x1-Grafik lädt; damit lassen sich Abrufe in E-Mails zählen.
	 *
	 * Die Adresse wird absolut erzeugt, weil das Tag auch in Newslettern und
	 * anderen Inhalten steht, die ausserhalb der Website gelesen werden.
	 *
	 * @param string $strTag Der Inhalt des Insert-Tags ohne die geschweiften
	 *                       Klammern, also etwa "linktracker::32::image"
	 *
	 * @return string|false Der erzeugte HTML-Code beziehungsweise die Adresse,
	 *                      oder false, wenn das Tag nicht zu dieser Klasse gehört
	 *                      oder keine ID enthält — Contao reicht es dann an die
	 *                      übrigen Hook-Teilnehmer weiter
	 */
	public function onReplaceInsertTags(string $strTag): string|false
	{
		$arrSplit = explode('::', $strTag);

		if ('linktracker' !== $arrSplit[0] && 'cache_linktracker' !== $arrSplit[0])
		{
			return false;
		}

		if (!isset($arrSplit[1]) || '' === $arrSplit[1])
		{
			return false;
		}

		$intId = (int) $arrSplit[1];
		$strUrl = $this->router->generate('linktracker_go', array('id' => $intId), UrlGeneratorInterface::ABSOLUTE_URL);

		if (isset($arrSplit[2]) && 'image' === $arrSplit[2])
		{
			return '<img src="' . htmlspecialchars($strUrl . '?option=image', ENT_QUOTES) . '" alt="" width="1" height="1">';
		}

		return $strUrl;
	}
}
