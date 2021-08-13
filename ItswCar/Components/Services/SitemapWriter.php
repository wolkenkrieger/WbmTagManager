<?php
/**
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   13.08.2021
 * Zeit:    08:15
 * Datei:   SitemapWriter.php
 * @package ItswCar\Components\Services
 */

namespace ItswCar\Components\Services;

use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Bundle\SitemapBundle\Exception\UnknownFileException;
use Shopware\Bundle\SitemapBundle\Service\SitemapNameGenerator;
use Shopware\Bundle\SitemapBundle\Struct\Sitemap;
use Shopware\Models\Shop\Shop;

class SitemapWriter implements \Shopware\Bundle\SitemapBundle\SitemapWriterInterface {
	
	private const SITEMAP_LIMIT = 2000;
	
	/**
	 * @var \Shopware\Bundle\SitemapBundle\Service\SitemapWriter
	 */
	private \Shopware\Bundle\SitemapBundle\Service\SitemapWriter $originalService;
	
	/**
	 * @var FilesystemInterface
	 */
	private $filesystem;
	
	/**
	 * @var SitemapNameGenerator
	 */
	private $sitemapNameGenerator;
	
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
	 * @param \Shopware\Bundle\SitemapBundle\Service\SitemapWriter $originalService
	 */
	public function __construct(SitemapNameGenerator $sitemapNameGenerator,
	                            FilesystemInterface $filesystem,
	                            LoggerInterface $logger = null,
	                            \Shopware\Bundle\SitemapBundle\Service\SitemapWriter $originalService) {
		$this->sitemapNameGenerator = $sitemapNameGenerator;
		$this->filesystem = $filesystem;
		$this->logger = $logger ?: new NullLogger();
		$this->originalService = $originalService;
	}
	
	/**
	 * @inheritDoc
	 */
	public function writeFile(Shop $shop, array $urls = []) {
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
	 */
	public function closeFiles() {
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
	private function closeFile($shopId)
	{
		if (!array_key_exists($shopId, $this->files)) {
			throw new UnknownFileException(sprintf('No open file "%s"', $shopId));
		}
		
		$fileHandle = $this->files[$shopId]['fileHandle'];
		$this->write($fileHandle, '</urlset>');
		
		gzclose($fileHandle);
		
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
	private function openFile($shopId)
	{
		if (array_key_exists($shopId, $this->files)) {
			return true;
		}
		
		$filePath = sprintf(
			'%s/sitemap-shop-%d-%s.xml.gz',
			rtrim(sys_get_temp_dir(), '/'),
			$shopId,
			microtime(true) * 10000
		);
		
		$fileHandler = gzopen($filePath, 'wb');
		
		if (!$fileHandler) {
			$this->logger->error(sprintf('Could not generate sitemap file, unable to write to "%s"', $filePath));
			
			return false;
		}
		
		$this->files[$shopId] = [
			'fileHandle' => $fileHandler,
			'fileName' => $filePath,
			'urlCount' => 0,
		];
		
		$this->write($fileHandler, '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
		
		return true;
	}
	
	/**
	 * @param resource $fileHandler
	 * @param string   $content
	 */
	private function write($fileHandler, $content)
	{
		gzwrite($fileHandler, $content);
	}
	
	/**
	 * Makes sure all files get closed and replaces the old sitemaps with the freshly generated ones
	 */
	private function moveFiles()
	{
		/** @var Sitemap[] $sitemaps */
		foreach ($this->sitemaps as $shopId => $sitemaps) {
			// Delete old sitemaps for this siteId
			foreach ($this->filesystem->listContents(sprintf('shop-%d', $shopId)) as $file) {
				$this->filesystem->delete($file['path']);
			}
			
			// Move new sitemaps into place
			foreach ($sitemaps as $sitemap) {
				$sitemapFileName = $this->sitemapNameGenerator->getSitemapFilename($shopId);
				try {
					$this->filesystem->write($sitemapFileName, file_get_contents($sitemap->getFilename()));
				} catch (\League\Flysystem\Exception $exception) {
					$this->logger->error(sprintf('Could not move sitemap to "%s" in the location for sitemaps', $sitemapFileName));
				} finally {
					// If we could not move the file to it's target, we remove it here to not clutter tmp dir
					unlink($sitemap->getFilename());
				}
			}
		}
		
		$this->sitemaps = [];
	}
}