<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   12.12.2020
 * Zeit:    16:06
 * Datei:   Services.php
 * @package ItswCar\Components\Services
 */

namespace ItswCar\Components\Services;

use ItswCar\Models\ArticleCarLinks;
use ItswCar\Models\Car;
use ItswCar\Models\KbaCodes;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\HttpFoundation\Cookie;

class Services {
	protected $container;
	protected $modelManager;
	protected $pluginLogger;
	protected $front;
	protected $basePath;
	protected $cache;
	protected $session;
	protected $shopId = 1;
	
	protected $rootCategoryId = 5;
	
	/**
	 * Services constructor.
	 * @param \Shopware\Components\DependencyInjection\Container $container
	 * @param \Shopware\Components\Model\ModelManager            $modelManager
	 */
	public function __construct(Container $container, ModelManager $modelManager) {
		$this->container = $container;
		$this->modelManager = $modelManager;
		$this->pluginLogger = $this->container->get('pluginlogger');
		$this->front = $this->container->get('front');
		$this->cache = $this->container->get('shopware.cache_manager');
		
		if ($this->container->initialized('shop')) {
			$this->basePath = $this->container->get('shop')->getBasePath();
			$this->shopId = $this->container->get('shop')->getId();
		}
		
		if ($this->basePath === null || $this->basePath === '') {
			$this->basePath = '/';
		}
	}
	
	/**
	 * @return string
	 */
	public function getDocPath(): string {
		return Shopware()->DocPath() . 'files' .DIRECTORY_SEPARATOR. 'documents' . DIRECTORY_SEPARATOR;
	}
	
	/**
	 * @return \Shopware\Components\Model\ModelManager
	 */
	public function getModelManager(): ModelManager {
		return $this->modelManager;
	}
	
	/**
	 * @return \Shopware\Components\DependencyInjection\Container
	 */
	public function getContainer(): Container {
		return $this->container;
	}
	
	/**
	 * @return array|false|string
	 */
	public function getEnvironment() {
		return getenv('SHOPWARE_ENV')?:'production';
	}
	
	/**
	 * @param array  $array
	 * @param string $key
	 * @param string $format
	 * @return mixed
	 */
	public function validate(array $array, string $key, string $format = 'string') {
		if (!isset($array[$key])) {
			throw new \RuntimeException(sprintf("column not found: %s", $key));
		}
		
		return $array[$key];
	}
	
	/**
	 * @param                                                   $current
	 * @param                                                   $end
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	public function showProgress($current, $end, OutputInterface $output): void {
		if ($current % 10 === 0) {
			$output->write('.');
		}
		if (($current % 500 === 0) || ($current === $end)) {
			$output->writeln(sprintf(' %d/%d', $current, $end));
		}
	}
	
	/**
	 * @param $property
	 * @return mixed
	 */
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
	
	/**
	 * @param $property
	 * @param $value
	 * @return mixed
	 */
	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $value;
			
			return $this->$property;
		}
	}
	
	/**
	 * @param $property
	 * @return bool
	 */
	public function __isset($property) {
		return isset($this->$property);
	}
	
	/**
	 * @return mixed
	 */
	public function setNoRender() {
		return $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();
	}
	
	/**
	 * @return mixed
	 */
	public function setNeverRender() {
		return $this->container->get('front')->Plugins()->ViewRenderer()->setNeverRender();
	}
	
	/**
	 * @return mixed
	 */
	public function setJsonRender() {
		return $this->container->get('front')->Plugins()->Json()->setRenderer();
	}
	
	/**
	 * @param string $string
	 * @return string
	 */
	public function getCleanedStringForUrl($string = ''):string {
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
	 * @return array
	 */
	public function getManufacturersForCarfinder(): array {
		$manufacturers = $this->modelManager->getRepository(Car::class)
			->getManufacturersQuery([
				'select' => [
					'manufacturers'
				],
				'conditions' => [
					'manufacturers.active = 1'
				],
				'orders' => [
					'manufacturers.display' => 'ASC'
				]
			])
			->getResult();
		
		foreach($manufacturers as $manufacturer) {
			$return[] = [
				'name' => $manufacturer->getName(),
				'url' => $this->getCleanedStringForUrl($manufacturer->getName()),
				'display' => $manufacturer->getDisplay(),
				'id' => $manufacturer->getId(),
				'topBrand' => $manufacturer->getTopBrand()
			];
		}
		
		return $return??[];
	}
	
	/**
	 * @param int|null $manufacturerId
	 * @return array
	 */
	public function getModelsForCarfinder(int $manufacturerId = NULL): array {
		if (!$manufacturerId) {
			throw new ParameterNotFoundException("manufacturerId");
		}
		
		$models = $this->modelManager->getRepository(Car::class)
			->getModelsForCarfinderQuery($manufacturerId, [
				'models.active = 1'
			])
			->getResult();
		
		foreach($models as $model) {
			$buildFrom = \DateTime::createFromFormat('Y-m-d H:i:s', $model['buildFrom'])->format('m/Y');
			$buildTo = \DateTime::createFromFormat('Y-m-d H:i:s', $model['buildTo'])->format('m/Y');
			
			$return[$model['modelDisplay']][] = [
				'typeDisplay' => $model['typeDisplay'],
				'buildFrom' => $buildFrom,
				'buildTo' => $buildTo,
				'typeId' => $model['typeId'],
				'modelId' => $model['modelId']
			];
		}
		
		return $return??[];
	}
	
	/**
	 * @param int|null $manufacturerId
	 * @param int|null $modelId
	 * @param int|null $typeId
	 * @return array
	 */
	public function getTypesForCarfinder(int $manufacturerId = NULL, int $modelId = NULL, int $typeId = NULL): array {
		if (!$manufacturerId) {
			throw new ParameterNotFoundException("manufacturerId");
		}
		if (!$modelId) {
			throw new ParameterNotFoundException("modelId");
		}
		$types = $this->modelManager->getRepository(Car::class)
			->getTypesForCarfinderQuery($manufacturerId, $modelId, $typeId, [
				'cars.active = 1'
			])
			->getResult();
		
		foreach($types as $type) {
			$return[] = [
				'tecdocId' => $type['tecdocId'],
				'ccm' => $type['ccm'],
				'display' => sprintf('%.1f', $type['ccm'] / 1000),
				'ps' => $type['ps'],
				'kw' => $type['kw'],
				'platform' => $type['platform'],
				'typeId' => $type['typeId']
			];
		}
		
		
		return $return??[];
	}
	
	/**
	 * @param array $options
	 * @return array
	 */
	public function getCarsForCarfinder(array $options = []): array {
		$cars = $this->getCars($options);
		
		foreach($cars as $car) {
			$codes = [];
			foreach($car->getCodes() as $kbaCodes) {
				$codes[] = [
					'hsn' => $kbaCodes->getHsn(),
					'tsn' => $kbaCodes->getTsn()
				];
			}
			$result[] = array_merge($car->toArray(), [
				'manufacturer' => $car->getManufacturer()->toArray(),
				'model' => $car->getModel()->toArray(),
				'type' => $car->getType()->toArray(),
				'platform' => $car->getPlatform()->toArray(),
				'codes' => $codes,
				'buildFrom' => $car->getBuildFrom()->format('m/Y'),
				'buildTo' => $car->getBuildTo()?$car->getBuildTo()->format('m/Y') : '---'
			]);
		}
		
		return $result??[];
	}
	
	/**
	 * @param array $options
	 * @return mixed
	 */
	public function getCars(array $options = []) {
		return $this->modelManager->getRepository(Car::class)
			->getCarsQuery($options)->getResult();
	}
	
	/**
	 * @param array $options
	 * @return mixed
	 */
	public function getCodes(array $options = []) {
		return $this->modelManager->getRepository(KbaCodes::class)
			->getCodesQuery($options)->getResult();
	}
	
	/**
	 * @param array $data
	 * @param bool  $full
	 * @return array
	 * @throws \JsonException
	 */
	public function setSessionData(array $data = [], bool $full = FALSE): array {
		if (!$full) {
			$tmp = [
				'manufacturer' => $data['manufacturer'],
				'model' => $data['model'],
				'type' => $data['type'],
				'car' => $data['car']??NULL
			];
			
			$data = $tmp;
		}
		
		if ($dataEncoded = json_encode($data, JSON_THROW_ON_ERROR)) {
			$expire = new \DateTime();
			$expire->modify('+7 day');
			$this->front->Response()->headers->setCookie(
				new Cookie(
					'itsw_cache',
					$dataEncoded,
					0,
					$this->basePath,
					NULL,
					FALSE,
					FALSE,
					TRUE
				)
			);
		}
		
		return $this->getSessionData(TRUE);
	}
	
	/**
	 * @param bool $full
	 * @return array
	 */
	public function getSessionData(bool $full = FALSE): array {
		if (!is_object($this->session)) {
			$this->session = $this->container->get('session');
		}
		
		if ($cookieData = $this->front->Request()->getCookie('itsw_cache')) {
			try {
				$sessionData = json_decode($cookieData, TRUE, 512, JSON_THROW_ON_ERROR);
				$this->session->offsetSet('itsw-session-data', $sessionData);
			} catch (\JsonException $exception) {
				$this->setLog($exception);
				$this->session->offsetSet('itsw-session-data', [
					'manufacturer' => NULL,
					'model' => NULL,
					'type' => NULL,
					'car' => NULL
				]);
			}
		}
		
		if ($this->session->offsetExists('itsw-session-data')) {
			$sessionData = $this->session->offsetGet('itsw-session-data');
			if ($full) {
				return $sessionData;
			} else {
				return [
					'manufacturer' => $sessionData['manufacturer'],
					'model' => $sessionData['model'],
					'type' => $sessionData['type'],
					'car' => $sessionData['car']??NULL
				];
			}
		}
		
		return [];
	}
	
	/**
	 * @param array $url
	 * @return mixed
	 */
	public function getUrl(array $url) {
		return $this->container->get('router')->assemble($url);
	}
	
	/**
	 * @return int
	 */
	public function getRootCategoryId(): int {
		return $this->rootCategoryId;
	}
	
	/**
	 * @param int $tecdocId
	 * @return array
	 */
	public function getIdsByTecdocId(int $tecdocId): array {
		$ids = $this->modelManager->getRepository(Car::class)
			->getIdsByTecdocIdQuery($tecdocId)
			->getArrayResult();
		
		if (!empty($ids)) {
			return reset($ids);
		}
		
		return [];
	}
	
	/**
	 * @param \Exception $e
	 */
	public function setLog(\Exception $e): void {
		$this->pluginLogger->addCritical($e->getMessage(), [
			'code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTraceAsString()
		]);
	}
	
	/**
	 * @param string $path
	 * @return false|string|string[]
	 */
	public function cleanSeoPath(string $path = '') {
		return mb_strtolower($this->container->get('modules')->RewriteTable()->sCleanupPath($path));
	}
	
	/**
	 * @return mixed
	 */
	public function getDbalQueryBuilder() {
		return $this->container->get('dbal_connection')->createQueryBuilder();
	}
	
	/**
	 * @param int|null $subshopId
	 * @return mixed
	 */
	public function getSeoUrlQuery(int $subshopId = NULL) {
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
			->setParameter('subshopId', ($subshopId ?: $this->shopId));
	}
	
	/**
	 * @param int|null $manufacturer
	 * @param int|null $model
	 * @return string[]
	 */
	public function getCarSeoUrl(int $manufacturer = NULL, int $model = NULL): array {
		$manufacturer = $manufacturer ?: $this->getSessionData()['manufacturer'];
		$model = $model ?: $this->getSessionData()['model'];
		
		return $this->getSeoUrlQuery()
			->setParameter('orgPath', ($model? 'sViewPort=cat&m=' . $manufacturer . '&mo=' . $model : 'sViewPort=cat&m=' . $manufacturer))
			->execute()
			->fetch(\PDO::FETCH_ASSOC) ?: [
				'realUrl' => '',
				'seoUrl' => ''
			];
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
	
	/**
	 * @param int $tecdocId
	 * @return array
	 */
	public function getVariantIdsByTecdocId(int $tecdocId): array {
		$carLinks = $this->getModelManager()->getRepository(ArticleCarLinks::class)
			->getCarLinksQuery([
				'select' => [
					'articleCarLinks.articleDetailsId'
				],
				'conditions' => [
					'articleCarLinks.tecdocId' => $tecdocId
				]
			])
			->getArrayResult();
		
		return array_column($carLinks, 'articleDetailsId');
	}
	
	/**
	 * @return int[]
	 */
	public function getVariantIdsWithoutArticleCarLink(): array {
		$builder = $this->getDbalQueryBuilder();
		$result = $builder
			->select(['details.id'])
			->from('s_articles_details', 'details')
			->join('details', 's_articles', 'articles', 'details.id = articles.main_detail_id and articles.active = 1 and details.active = 1')
			->leftJoin('details', 'itsw_article_car_links', 'carLinks', 'details.id = carLinks.article_details_id and carLinks.active = 1')
			->where('carLinks.article_details_id IS NULL')
			->execute()
			->fetchAll(\PDO::FETCH_COLUMN);
		
		return array_map(static function($value) {return (int)$value;}, $result);
	}
	
	/**
	 * @return array
	 */
	public function getArticleIdsWithoutArticleCarLink(): array {
		$builder = $this->getDbalQueryBuilder();
		$result = $builder
			->select(['details.articleID'])
			->from('s_articles_details', 'details')
			->join('details', 's_articles', 'articles', 'details.id = articles.main_detail_id and articles.active = 1 and details.active = 1')
			->leftJoin('details', 'itsw_article_car_links', 'carLinks', 'details.id = carLinks.article_details_id and carLinks.active = 1')
			->where('carLinks.article_details_id IS NULL')
			->execute()
			->fetchAll(\PDO::FETCH_COLUMN);
		
		return array_map(static function($value) {return (int)$value;}, $result);
	}
	
	/**
	 * @return bool
	 */
	public function getServiceMode(): bool {
		$config = $this->container->get('config');
		if (!empty($config->setOffline) && strpos($config->offlineIp, $this->front->Request()->getClientIp()) === false) {
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * @return bool
	 */
	public function getDevelopmentMode(): bool {
		$environment = $this->container->getParameter('kernel.environment');
		
		if ($environment === 'dev') {
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * @return float|int
	 */
	public function getUserGroupDiscount() {
		if ($this->container->get('shop')->getCustomerGroup()->getMode()) {
			return $this->container->get('shop')->getCustomerGroup()->getDiscount();
		}
		
		return 0;
	}
}