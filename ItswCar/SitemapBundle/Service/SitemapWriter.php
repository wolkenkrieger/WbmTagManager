<?php
/**
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   13.08.2021
 * Zeit:    08:15
 * Datei:   SitemapWriter.php
 * @package ItswCar\SitemapBundle\Service
 */

namespace ItswCar\SitemapBundle\Service;

use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Bundle\SitemapBundle\Exception\UnknownFileException;
use Shopware\Bundle\SitemapBundle\SitemapWriterInterface;
use Shopware\Bundle\SitemapBundle\Struct\Sitemap;
use Shopware\Models\Shop\Shop;
use ItswCar\Traits\LoggingTrait;

class SitemapWriter implements SitemapWriterInterface {
	use LoggingTrait;
	
	private const SITEMAP_LIMIT = 1000;
	
	/**
	 * @var FilesystemInterface
	 */
	private FilesystemInterface $filesystem;
	
	/**
	 * @var \ItswCar\SitemapBundle\Service\SitemapNameGenerator
	 */
	private SitemapNameGenerator $sitemapNameGenerator;
	
	/**
	 * @var array
	 */
	private array $files = [];
	
	/**
	 * @var array<Sitemap[]>
	 */
	private $sitemaps = [];
	
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	
	/**
	 * @param \ItswCar\SitemapBundle\Service\SitemapNameGenerator $sitemapNameGenerator
	 * @param \League\Flysystem\FilesystemInterface               $filesystem
	 * @param \Psr\Log\LoggerInterface|null                       $logger
	 */
	public function __construct(SitemapNameGenerator $sitemapNameGenerator,
	                            FilesystemInterface $filesystem,
	                            LoggerInterface $logger = null)
	{
		$this->sitemapNameGenerator = $sitemapNameGenerator;
		$this->filesystem = $filesystem;
		$this->logger = $logger ?: new NullLogger();
	}
	
	/**
	 * @param \Shopware\Models\Shop\Shop $shop
	 * @param array                      $urls
	 * @return bool
	 * @throws \Shopware\Bundle\SitemapBundle\Exception\UnknownFileException
	 */
	public function writeFile(Shop $shop, array $urls = []): bool {
		if (empty($urls)) {
			return false;
		}
		
		$this->openFile($shop->getId());
		
		foreach ($urls as $url) {
			if ($this->files[$shop->getId()]['urlCount'] >= self::SITEMAP_LIMIT) {
				$this->closeFile($shop->getId());
				
				$this->openFile($shop->getId());
			}
			
			++$this->files[$shop->getId()]['urlCount'];
			$this->write($this->files[$shop->getId()]['fileHandle'], (string) $url);
		}
		
		return true;
	}
	
	/**
	 * @inheritDoc
	 * @throws \Shopware\Bundle\SitemapBundle\Exception\UnknownFileException
	 * @throws \League\Flysystem\FileNotFoundException
	 */
	public function closeFiles(): void {
		foreach ($this->files as $shopId => $params) {
			$this->closeFile($shopId);
		}
		
		$this->moveFiles();
	}
	
	/**
	 * @param int $shopId
	 *
	 * @throws UnknownFileException
	 *
	 * @return bool
	 */
	private function closeFile(int $shopId): bool {
		if (!array_key_exists($shopId, $this->files)) {
			throw new UnknownFileException(sprintf('No open file "%s"', $shopId));
		}
		
		$fileHandle = $this->files[$shopId]['fileHandle'];
		$this->write($fileHandle, '</urlset>');
		
		fclose($fileHandle);
		
		if (!array_key_exists($shopId, $this->sitemaps)) {
			$this->sitemaps[$shopId] = [];
		}
		
		$this->sitemaps[$shopId][] = new Sitemap(
			$this->files[$shopId]['fileName'],
			$this->files[$shopId]['urlCount']
		);
		
		unset($this->files[$shopId]);
		
		return true;
	}
	
	/**
	 * @param int $shopId
	 *
	 * @return bool
	 */
	private function openFile(int $shopId): bool {
		if (array_key_exists($shopId, $this->files)) {
			return true;
		}
		
		$filePath = sprintf(
			'%s/sitemap-shop-%d-%.4f.xml',
			rtrim(sys_get_temp_dir(), '/'),
			$shopId,
			microtime(true)
		);
		
		$fileHandler = fopen($filePath, 'wb');
		
		if (!$fileHandler) {
			$this->error(__METHOD__, [
				sprintf('Could not generate sitemap file, unable to write to "%s"', $filePath)
			]);
			
			return false;
		}
		
		$this->files[$shopId] = [
			'fileHandle' => $fileHandler,
			'fileName' => $filePath,
			'urlCount' => 0,
		];
		
		$this->write($fileHandler,
			'<?xml version="1.0" encoding="UTF-8"?>'
			. PHP_EOL.
			'<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">'
		);
		
		return true;
	}
	
	/**
	 * @param resource $fileHandler
	 * @param string   $content
	 */
	private function write($fileHandler, string $content): void {
		fwrite($fileHandler, $content . PHP_EOL);
	}
	
	/**
	 * Makes sure all files get closed and replaces the old sitemaps with the freshly generated ones
	 * @throws \League\Flysystem\FileNotFoundException
	 */
	private function moveFiles(): void {
		/** @var Sitemap[] $sitemaps */
		foreach ($this->sitemaps as $shopId => $sitemaps) {
			// Delete old sitemaps for this siteId
			foreach ($this->filesystem->listContents(sprintf('shop-%d', $shopId)) as $file) {
				if ($file['basename'] !== '.htaccess') {
					$this->filesystem->delete($file['path']);
				}
			}
			
			// Move new sitemaps into place
			foreach ($sitemaps as $sitemap) {
				$sitemapFileName = $this->sitemapNameGenerator->getSitemapFilename($shopId);
				try {
					$this->filesystem->write($sitemapFileName, file_get_contents($sitemap->getFilename()));
				} catch (\League\Flysystem\Exception $exception) {
					$this->error(__METHOD__, [
						sprintf('Could not move sitemap to "%s" in the location for sitemaps', $sitemapFileName)
					]);
				} finally {
					// If we could not move the file to it's target, we remove it here to not clutter tmp dir
					unlink($sitemap->getFilename());
				}
			}
		}
		
		$this->sitemaps = [];
	}
}