<?php declare(strict_types=1);
/**
 * Autor:    Rico Wunglück <development@itsw.dev>
 * Datum:    16.09.2021
 * Zeit:    08:17
 * Datei:    SeoHelper.php
 * @package ItswCar\Helpers
 */

namespace ItswCar\Helpers;

use ItswCar\Traits\LoggingTrait;
use Shopware\Models\Shop\DetachedShop;
use Shopware\Models\Shop\Shop;

class SeoHelper {
	use LoggingTrait;
	
	/**
	 * @param string $string
	 * @return string
	 */
	public function getCleanedStringForUrl(string $string = ''):string {
		$string = mb_strtolower(trim($string));
		$umlauts = ['/ß/', '/Ä/', '/Ö/', '/Ü/', '/ä/', '/ö/', '/ü/'];
		$umlautsReplacements = ['sz', 'Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue'];
		$patterns = [
			'~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);~i',
			'/[^a-z0-9]+/i'
		];
		$patternsReplacements = ['$1', '-'];
		
		$string = preg_replace($umlauts, $umlautsReplacements, $string);
		$string = preg_replace($patterns, $patternsReplacements, htmlentities($string, ENT_QUOTES, 'UTF-8'));
		
		return trim($string,' -');
	}
	
	/**
	 * @return mixed
	 */
	private function getDbalQueryBuilder() {
		return Shopware()->Container()->get('dbal_connection')->createQueryBuilder();
	}
	
	/**
	 * @param string $path
	 * @return false|string|string[]
	 */
	public function cleanSeoPath(string $path = '') {
		return mb_strtolower(Shopware()->Modules()->RewriteTable()->sCleanupPath($path));
	}
	
	/**
	 * @param int|null $subshopId
	 * @return mixed
	 */
	public function getSeoUrlQuery(int $subshopId = NULL) {
		$configHelper = Shopware()->Container()->get('itsw.helper.config');
		
		$builder = $this->getDbalQueryBuilder();
		return $builder->select([
			'path AS seoUrl',
			'org_path AS realUrl'
		])
			->from('s_core_rewrite_urls', 'r')
			->where('r.org_path = :orgPath')
			->andWhere('main = :main')
			->andWhere('subshopId = :subshopId')
			->setParameter('main', 1)
			->setParameter('subshopId', ($subshopId ?: $configHelper->getShopId()));
	}
	
	/**
	 * @param int|null $manufacturer
	 * @param int|null $model
	 * @param int|null $car
	 * @return string
	 */
	public function getCarSeoUrl(int $manufacturer = NULL, int $model = NULL, int $car = NULL): string {
		if ($car) {
			$orgPath = sprintf('sViewPort=cat&m=%d&mo=%d&car=%d', $manufacturer, $model, $car);
			return $this->getSeoUrlPart($orgPath);
		}
		
		if ($model) {
			$orgPath = sprintf('sViewPort=cat&m=%d&mo=%d', $manufacturer, $model);
			return $this->getSeoUrlPart($orgPath);
		}
		
		if ($manufacturer) {
			$orgPath = sprintf('sViewPort=cat&m=%d', $manufacturer);
			return $this->getSeoUrlPart($orgPath);
		}
		
		return '';
	}
	
	/**
	 * @param string $orgPath
	 * @return mixed|string
	 */
	private function getSeoUrlPart(string $orgPath) {
		$result = $this->getSeoUrlQuery()
			->setParameter('orgPath', $orgPath)
			->execute()
			->fetch(\PDO::FETCH_ASSOC);
		
		return $result['seoUrl']??'';
	}
	
	/**
	 * @param int $categoryId
	 * @return string|string[]|null
	 */
	public function getCategorySeoUrl(int $categoryId) {
		$categoryUrl = $this->getSeoUrlQuery()
			->setParameter('orgPath', 'sViewport=cat&sCategory=' . $categoryId)
			->execute()
			->fetch(\PDO::FETCH_ASSOC) ?: [
			'realUrl' => '',
			'seoUrl' => ''
		];
		$sessionData = Shopware()->Container()->get('itsw.helper.session')->getSessionData();
		return preg_replace("/\/\//", '/', $this->cleanSeoPath((string)$sessionData['url']) . $this->cleanSeoPath($categoryUrl['seoUrl']));
	}
	
	/**
	 * @param int $articleId
	 * @return string|string[]|null
	 */
	public function getArticleSeoUrl(int $articleId) {
		$articleUrl = $this->getSeoUrlQuery()
			->setParameter('orgPath', 'sViewport=detail&sArticle=' . $articleId)
			->execute()
			->fetch(\PDO::FETCH_ASSOC) ?: [
			'realUrl' => '',
			'seoUrl' => ''
		];
		$sessionData = Shopware()->Container()->get('itsw.helper.session')->getSessionData();
		return preg_replace("/\/\//", '/', $this->cleanSeoPath((string)$sessionData['url']) . $this->cleanSeoPath($articleUrl['seoUrl']));
	}
	
	/**
	 * @param int|null $subshopId
	 * @return array|mixed
	 */
	public function getAllCarSeoUrls(?int $subshopId) {
		$configHelper = Shopware()->Container()->get('itsw.helper.config');
		
		$builder = $this->getDbalQueryBuilder();
		return $builder->select([
			'seoUrl'
		])
			->from('s_core_rewrite_urls', 'r')
			->where('r.org_path LIKE "sViewport=cat&car=%"')
			->andWhere('main = :main')
			->andWhere('subshopId = :subshopId')
			->setParameter('main', 1)
			->setParameter('subshopId', ($subshopId ?: $configHelper->getShopId()))
			->execute()
			->fetch(\PDO::FETCH_ASSOC) ?: [];
	}
	
	/**
	 * @param string $url
	 * @return string
	 */
	public function completeUrl(string $url = ''): string {
		$shop = Shopware()->Container()->get('itsw.helper.config')->getShop();
		$host = ($shop->getSecure() ? "https://" : "http://") . $shop->getHost();
		$baseUrl = rtrim($shop->getBaseUrl(), '/');
		//$baseUrl = '';
		
		if (strpos($url, '://') === FALSE) {
			if (stripos($url, $baseUrl) !== 0) {
				$url = implode('/', [
					trim($baseUrl, '/'),
					ltrim($url, '/')
				]);
			}
			
			$url = implode('/', array_filter([
				trim($host, '/'),
				trim($url, '/'),
				''
			]));
		}
		
		return $url;
	}
	
	/**
	 * @param string $url
	 * @return string
	 */
	public function extractPathFromUrl(string $url = ''): string {
		if ($path = parse_url($url, PHP_URL_PATH)) {
			return $path;
		}
		
		$shop = Shopware()->Container()->get('itsw.helper.config')->getShop();
		$baseUrl = $this->completeUrl('');
		
		if (($position = stripos($url, $baseUrl)) !== FALSE) {
			return substr($url, $position + strlen($baseUrl));
		}
		
		return $url;
	}
}