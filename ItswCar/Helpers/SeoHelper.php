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
	 * @return string[]
	 */
	public function getCarSeoUrl(int $manufacturer = NULL, int $model = NULL, int $car = NULL): array {
		$sessionHelper = Shopware()->Container()->get('itsw.helper.session');
		
		$manufacturer = $manufacturer ?: $sessionHelper->getSessionData()['manufacturer'];
		$model = $model ?: $sessionHelper->getSessionData()['model'];
		$car = $car ?: $sessionHelper->getSessionData()['car'];
		
		$seoUrl = '';
		
		if ($manufacturer) {
			$orgPath = sprintf('sViewPort=cat&m=%d', $manufacturer);
			$seoUrl = $this->getSeoUrlPart($orgPath);
		}
		
		if ($model) {
			$orgPath = sprintf('sViewPort=cat&mo=%d', $model);
			$urlPart = $this->getSeoUrlPart($orgPath);
			$seoUrl = ($seoUrl && $urlPart)? $seoUrl.$urlPart : $seoUrl;
		}
		
		if ($car) {
			$orgPath = sprintf('sViewPort=cat&car=%d', $car);
			$urlPart = $this->getSeoUrlPart($orgPath);
			$seoUrl = ($seoUrl && $urlPart)? $seoUrl.$urlPart : $seoUrl;
		}
		
		return [
			'realUrl' => '',
			'seoUrl' => $seoUrl
		];
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
		
		return preg_replace("/\/\//", '/', $this->cleanSeoPath($this->getCarSeoUrl()['seoUrl']) . $this->cleanSeoPath($categoryUrl['seoUrl']));
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
		
		return preg_replace("/\/\//", '/', $this->cleanSeoPath($this->getCarSeoUrl()['seoUrl']) . $this->cleanSeoPath($articleUrl['seoUrl']));
	}
}