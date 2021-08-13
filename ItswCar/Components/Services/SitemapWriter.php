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
use Shopware\Bundle\SitemapBundle\Struct\Sitemap;
use Shopware\Models\Shop\Shop;
use Google\Client;

class SitemapWriter implements \Shopware\Bundle\SitemapBundle\SitemapWriterInterface {
	
	private const SITEMAP_LIMIT = 1000;
	
	/**
	 * @var \Shopware\Bundle\SitemapBundle\Service\SitemapWriter
	 */
	private \Shopware\Bundle\SitemapBundle\Service\SitemapWriter $originalService;
	
	/**
	 * @var FilesystemInterface
	 */
	private $filesystem;
	
	/**
	 * @var \ItswCar\Components\Services\SitemapNameGenerator
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
	
	private Client $googleClient;
	
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
		$this->googleClient = new Client();
	}
	
	/**
	 * @inheritDoc
	 * @throws \Google\Exception
	 * @throws \JsonException|\Shopware\Bundle\SitemapBundle\Exception\UnknownFileException
	 */
	public function writeFile(Shop $shop, array $urls = []) {
		if (empty($urls)) {
			return false;
		}
		
		$this->googleClient->setAuthConfig(
			[
				"type" =>                           'service_account',
				"project_id" =>                     "autoteile-wiesel-shop",
				"private_key_id" =>                 "3a8f66981162087873933e1d88c53e3ba36961d6",
				"private_key" =>                    "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDeKNIfHarEttFt\n0jc8blJlFQ1DjHsl7hiHxD9GMoZOBpUAZaetzPcI3eRdVb8fI10u/3y1XRYjbpWd\nMHEK0p02hn/6foDSsFAVePif9feFnud9m9nqey2zhqx5YPh/7XhcdJ9Qi1v+Lwjy\nSJrdZXcZcGBuv8lWV3eTH+wmsQ553eyvGwzgUonqS8lY1lqh7bgNvkS2a20D5sMz\nitU/RM0PiV6I2iaS0tRuy1MCe8Tk6eRY+kBIqBUDlOK5Ca2KXVwEgHKVhwkIeTrH\npGqs+uYY7TNpizsRBqFroIKYzPFM4ZWM/X8SXhBQIBoXV2b5hebXCrtkCxSkY+O7\naJgFZSGVAgMBAAECggEAHETG+fkhL+9QZmBLsYebOewMGEaRrAGoQuxcnc7Tiq2p\nO5fz1niAjbMIejztOSXbl12grYZHCSKq7InLtJQYt6W1NrdupoCTbCwMvPQEN09B\nRxk0CTN73P57x2UoscSSnncjh5D4F3e+NHA7LoVY+pZzWtxSHwe5ujGjb8fzXtu6\n6NlaW1wxj9RpaWMoAYvIsLEZqIqzSmcHTDML0TdYo0Qo/NqHUvZ7BY7AwSA65NAi\n8vKpphGsODRGYY9D7yf6Yg0Dv+NTvtHAfbyEF4cV3+hYLACNy3MzB63glhGeqt3D\n8nK/9ci5KWdQz4BLGHZ8jvZ7XLAK7XBKhIg2rYvhMQKBgQD1ixf9cEcPazmW9pRc\nY/XxheMqaG3zrkFGk95SMA4buBQ5nWkq9fxhx6DIpH1EoWalbnozEzLFF1/RNiQT\nXSr0+72oUsQVMkthcITs+uou+l2Nd7H4Ta3OO1BPxNx6D0MDD9NB2W3ULNXtxnqI\nlU5cav4kdvuVVQP41YZuUOejcQKBgQDnnsv1WGfaDyVv6reGRGvC7KT+fNrziITX\nPtlOy5x2NqPGUKkWB1nh+J+DnA8ICmHkTMzTKLlWloVnKhUdlx2173YHFaflRgU5\nyWmGZazPneP7PpBxdmzVR6l/yEKPlbXBTgePh+tVIUWjeAnFAP17aYXkJlqetQxP\n9j8Evn0GZQKBgBumcikj/5yI4o9RbRuKViPAg/g+kkMimE8uT5bJuoz8iaqDQ1iH\nIsiQBlcRztlvo3N1oWUnAEyZeTzB8AYOM8wqnQzbZSFN7JcoaI+XIc/weaB4nh3s\nAWp722tgK8PC/DTYD14W8fs2oVCJTTscNRrLIZtRmrsSO8RFp0G88pjBAoGAPz6O\nBJ0yJkmyeD/IAwKVxjDl0JN5GbxyQW/o2Goie+jpiVInCVvSgDBqJf6r4A8tsbAC\n0bmx+eG55XSJNX4435/WQ5L3OFSJQsErbkY/DKXZRZZMzJ6AkzAxKTIecqR3b6QF\nznxXlvQx1rQFPuTJUjR7GdQbNHDNPtxHUt1MuD0CgYAUAgao3q7G4bnHgLw9abhb\n07ikTu6wC1ThjBkCJY2kriNWv7I4cfrDY15Hgxu36kBCnjngX9jbQNAUl1wgC3ad\n7S7Z14Eou1gqOnIVzQirZmLAvrFYS1TvElVKvycYQWiZcMfGxceaLzFXLGDz2Y/+\nDfsdyCGd2sb8Vbt1rIF0ZQ==\n-----END PRIVATE KEY-----\n",
				"client_email" =>                   "atw-shop-search-api-anbindung@autoteile-wiesel-shop.iam.gserviceaccount.com",
				"client_id" =>                      "110124093730592499312",
				"auth_uri" =>                       "https://accounts.google.com/o/oauth2/auth",
				"token_uri" =>                      "https://oauth2.googleapis.com/token",
				"auth_provider_x509_cert_url" =>    "https://www.googleapis.com/oauth2/v1/certs",
				"client_x509_cert_url" =>           "https://www.googleapis.com/robot/v1/metadata/x509/atw-shop-search-api-anbindung%40autoteile-wiesel-shop.iam.gserviceaccount.com"
			]
		);
		
		$this->googleClient->addScope('https://www.googleapis.com/auth/indexing');
		$httpClient = $this->googleClient->authorize();
		$endPoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
		
		$this->openFile($shop->getId());
		
		foreach ($urls as $url) {
			if ($this->files[$shop->getId()]['urlCount'] >= self::SITEMAP_LIMIT) {
				$this->closeFile($shop->getId());
				
				$this->openFile($shop->getId());
			}
			
			++$this->files[$shop->getId()]['urlCount'];
			$this->write($this->files[$shop->getId()]['fileHandle'], (string) $url);
			
			/*
			$content = json_encode([
				'url' => $url->getLoc(),
				'type' => 'URL_UPDATED'
			], JSON_THROW_ON_ERROR);
			
			$response = $httpClient->post($endPoint, ['body' => $content]);
			$statusCode = $response->getStatusCode();
			
			echo(PHP_EOL . sprintf('URL: %s :: StatusCode: %d', $url->getLoc(), $statusCode));
			*/
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
	private function openFile($shopId)
	{
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
			$this->logger->error(sprintf('Could not generate sitemap file, unable to write to "%s"', $filePath));
			
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
	private function write($fileHandler, string $content)
	{
		fwrite($fileHandler, $content . PHP_EOL);
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