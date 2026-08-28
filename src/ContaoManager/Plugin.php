<?php

declare(strict_types=1);

namespace Schachbulle\ContaoLinktrackerBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Schachbulle\ContaoLinktrackerBundle\ContaoLinktrackerBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Meldet das Bundle beim Contao Manager an und bringt seine Routen mit.
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
	/**
	 * Meldet das Bundle an und sorgt dafür, dass es nach dem Contao-Kern
	 * geladen wird.
	 *
	 * @param ParserInterface $parser Wird hier nicht gebraucht, gehört aber zur
	 *                                Schnittstelle
	 *
	 * @return array<BundleConfig> Die Anmeldung dieses einen Bundles
	 */
	public function getBundles(ParserInterface $parser): array
	{
		return array(
			BundleConfig::create(ContaoLinktrackerBundle::class)
				->setLoadAfter(array(ContaoCoreBundle::class)),
		);
	}

	/**
	 * Lädt die Routen des Bundles aus src/Resources/config/routes.yaml.
	 *
	 * Der Rückgabetyp bleibt undeklariert, weil die Schnittstelle in Contao 4.13
	 * wie in Contao 5 keinen deklariert. Übergeben wird ein Resolver, kein
	 * Loader — der passende Loader muss also erst erfragt werden.
	 *
	 * @param LoaderResolverInterface $resolver Ermittelt den Loader für die
	 *                                          YAML-Datei
	 * @param KernelInterface         $kernel   Wird hier nicht gebraucht, gehört
	 *                                          aber zur Schnittstelle
	 *
	 * @return RouteCollection|null Die Routen des Bundles, oder null, wenn sich
	 *                              für die Datei kein Loader finden liess
	 */
	public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
	{
		$strFile = __DIR__ . '/../Resources/config/routes.yaml';
		$objLoader = $resolver->resolve($strFile);

		if (false === $objLoader)
		{
			return null;
		}

		return $objLoader->load($strFile);
	}
}
