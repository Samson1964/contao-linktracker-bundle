<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\Tests\Tags;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoLinktrackerBundle\Tags\Linktracker;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Prüft das Insert-Tag {{linktracker::…}}.
 */
class LinktrackerTest extends TestCase
{
	/**
	 * Baut die zu prüfende Klasse mit einem echten Adressgenerator auf.
	 *
	 * Statt eines Mocks kommt hier der echte UrlGenerator zum Einsatz, damit
	 * die Prüfung auch die erzeugte Adresse selbst abdeckt und nicht nur den
	 * Aufruf des Generators.
	 *
	 * @return Linktracker Die vorbereitete Insert-Tag-Klasse
	 */
	private function createTag(): Linktracker
	{
		$objRouten = new RouteCollection();
		$objRouten->add('linktracker_go', new Route('/linktracker/{id}'));

		$objGenerator = new UrlGenerator(
			$objRouten,
			new RequestContext('', 'GET', 'example.org', 'https')
		);

		return new Linktracker($objGenerator);
	}

	/**
	 * Ohne zweiten Parameter muss die nackte, absolute Adresse herauskommen.
	 */
	public function testLiefertDieAbsoluteAdresse(): void
	{
		$this->assertSame(
			'https://example.org/linktracker/32',
			$this->createTag()->onReplaceInsertTags('linktracker::32')
		);
	}

	/**
	 * Mit dem Parameter "image" muss ein img-Element mit option=image kommen.
	 */
	public function testLiefertDasZaehlpixel(): void
	{
		$strErgebnis = $this->createTag()->onReplaceInsertTags('linktracker::32::image');

		$this->assertIsString($strErgebnis);
		$this->assertStringStartsWith('<img ', $strErgebnis);
		$this->assertStringContainsString('https://example.org/linktracker/32?option=image', $strErgebnis);
		$this->assertStringContainsString('alt=""', $strErgebnis);
	}

	/**
	 * Die zwischengespeicherte Schreibweise muss dasselbe liefern.
	 */
	public function testErkenntAuchDieCacheSchreibweise(): void
	{
		$this->assertSame(
			'https://example.org/linktracker/32',
			$this->createTag()->onReplaceInsertTags('cache_linktracker::32')
		);
	}

	/**
	 * Die ID wird als Ganzzahl ausgewertet, damit sich über das Insert-Tag
	 * nichts anderes in die Adresse schmuggeln lässt.
	 */
	public function testWertetDieIdAlsGanzzahlAus(): void
	{
		$this->assertSame(
			'https://example.org/linktracker/32',
			$this->createTag()->onReplaceInsertTags('linktracker::32abc')
		);
	}

	/**
	 * Tags anderer Erweiterungen und unvollständige Tags müssen unangetastet
	 * an die übrigen Hook-Teilnehmer weitergereicht werden.
	 *
	 * @dataProvider fremdeTags
	 */
	public function testReichtFremdeTagsDurch(string $strTag): void
	{
		$this->assertFalse($this->createTag()->onReplaceInsertTags($strTag));
	}

	/**
	 * @return array<string,array{string}> Tags, die nicht zu dieser Klasse gehören
	 */
	public static function fremdeTags(): array
	{
		return array(
			'fremdes Tag'      => array('news_url::7'),
			'ohne ID'          => array('linktracker'),
			'mit leerer ID'    => array('linktracker::'),
			'aehnlicher Name'  => array('linktrackers::32'),
		);
	}
}
