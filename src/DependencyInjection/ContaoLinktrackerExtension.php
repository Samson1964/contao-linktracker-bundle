<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Lädt die Dienste des Bundles in den Container.
 */
class ContaoLinktrackerExtension extends Extension
{
	/**
	 * Liest src/Resources/config/services.yml ein.
	 *
	 * Die Basisklasse Symfony\Component\DependencyInjection\Extension\Extension
	 * gibt es seit Symfony 5.4 und damit sowohl unter Contao 4.13 als auch
	 * unter Contao 5. Die Schnittstelle deklariert für load() keinen
	 * Rückgabetyp, void ist hier also erlaubt.
	 *
	 * @param array<mixed>     $mergedConfig Die zusammengeführte Bundle-Konfiguration;
	 *                                       das Bundle hat keine eigene, deshalb
	 *                                       bleibt sie ungenutzt
	 * @param ContainerBuilder $container    Der Container, in den die Dienste gehen
	 *
	 * @return void
	 */
	public function load(array $mergedConfig, ContainerBuilder $container): void
	{
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__ . '/../Resources/config')
		);

		$loader->load('services.yml');
	}
}
