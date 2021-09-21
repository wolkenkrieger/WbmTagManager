<?php
/**
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   13.08.2021
 * Zeit:    09:27
 * Datei:   SitemapNameGenerator.php
 * @package ItswCar\SitemapBundle\Service
 */

namespace ItswCar\SitemapBundle\Service;

use League\Flysystem\FilesystemInterface;
use Shopware\Bundle\SitemapBundle\SitemapNameGeneratorInterface;
use ItswCar\Traits\LoggingTrait;

class SitemapNameGenerator implements SitemapNameGeneratorInterface {
	use LoggingTrait;
	
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
		
		$this->debug(__METHOD__, ['path' => $path]);
		
		return $path;
	}
}