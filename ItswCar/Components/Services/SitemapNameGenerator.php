<?php
/**
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   13.08.2021
 * Zeit:    09:27
 * Datei:   SitemapNameGenerator.php
 * @package ItswCar\Components\Services
 */

namespace ItswCar\Components\Services;

use League\Flysystem\FilesystemInterface;
use Shopware\Bundle\SitemapBundle\SitemapNameGeneratorInterface;

class SitemapNameGenerator implements SitemapNameGeneratorInterface {
	/**
	 * @var FilesystemInterface
	 */
	private FilesystemInterface $filesystem;
	
	/**
	 * @var string
	 */
	private string $pattern;
	
	/**
	 * @param \League\Flysystem\FilesystemInterface $filesystem
	 * @param string                                $pattern
	 */
	public function __construct(FilesystemInterface $filesystem, string $pattern = 'sitemap-{number}.xml')	{
		$this->pattern = $pattern;
		$this->filesystem = $filesystem;
	}
	
	/**
	 * @param int $shopId
	 * @return string
	 */
	public function getSitemapFilename($shopId): string {
		$number = 1;
		do {
			$path = 'shop-' . $shopId . '/' . str_ireplace(
					['{number}'],
					[$number],
					$this->pattern
				);
			++$number;
		} while ($this->filesystem->has($path));
		
		return $path;
	}
}