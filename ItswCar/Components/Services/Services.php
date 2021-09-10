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

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use ItswCar\Models\ArticleCarLinks;
use ItswCar\Models\Car;
use ItswCar\Models\KbaCodes;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Logger as PluginLogger;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\HttpFoundation\Cookie;

class Services {
	public Container $container;
	public ModelManager $modelManager;
	public PluginLogger $pluginLogger;
	public $front;
	public $basePath;
	public $cache;
	public $session;
	public int $shopId = 1;
	public int $rootCategoryId = 5;
	
	/**
	 * Services constructor.
	 * @param \Shopware\Components\DependencyInjection\Container $container
	 * @param \Shopware\Components\Model\ModelManager            $modelManager
	 */
	public function __construct(Container $container, ModelManager $modelManager) {
		$this->container = $container;
		$this->modelManager = $modelManager;
		$this->pluginLogger = $this->container->get('pluginlogger');
		$this->cache = $this->container->get('shopware.cache_manager');
		
		if ($this->container->initialized('shop')) {
			$this->basePath = $this->container->get('shop')->getBasePath();
			$this->shopId = $this->container->get('shop')->getId();
		}
		
		if ($this->container->initialized('front')) {
			$this->front = $this->container->get('front');
		} else {
			$this->front = NULL;
		}
		
		if ($this->container->initialized('session')) {
			$this->session = $this->container->get('session');
		} else {
			$this->session = NULL;
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
	 * @return mixed
	 */
	public function validate(array $array, string $key) {
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
		
		return NULL;
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
		
		return NULL;
	}
	
	/**
	 * @param $property
	 * @return bool
	 */
	public function __isset($property) {
		return isset($this->$property);
	}
	
	/**
	 * @return \Enlight_Controller_Plugins_ViewRenderer_Bootstrap|\Enlight_Plugin_Bootstrap|false
	 */
	public function setNoRender() {
		if ($this->isFront()) {
			return $this->front->Plugins()->ViewRenderer()->setNoRender();
		}
		
		return FALSE;
	}
	
	/**
	 * @return \Enlight_Controller_Plugins_ViewRenderer_Bootstrap|\Enlight_Plugin_Bootstrap|false
	 */
	public function setNeverRender() {
		
		if ($this->isFront()) {
			return $this->front->Plugins()->ViewRenderer()->setNeverRender();
		}
		
		return FALSE;
	}
	
	/**
	 * @return mixed
	 */
	public function setJsonRender() {
		if ($this->isFront()) {
			return $this->front->Plugins()->Json()->setRenderer();
		}
		
		return FALSE;
	}
	
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
	 * @return array
	 * @throws \JsonException
	 */
	public function setSessionData(array $data = []): array {
		if (is_null($this->session)) {
			if ($this->isFront()) {
				$this->session = $this->container->get('session');
			} else {
				return [];
			}
		}
		
		$data = array_merge([
			'manufacturer'  => NULL,
			'model'         => NULL,
			'type'          => NULL,
			'car'           => NULL,
			'description'   => NULL,
			'title'         => NULL
		], $data);
		
		
		
		$data['description'] = $data['car'] ? $this->getCarDisplayForView((int)$data['car'], TRUE) : NULL;
		$data['title'] = $data['car'] ? $this->getCarDisplayForView((int)$data['car']) : NULL;
		
		$this->session->offsetSet('itsw-session-data', $data);
		
		if ($dataEncoded = json_encode($data, JSON_THROW_ON_ERROR)) {
			Shopware()->Front()->Response()->headers->setCookie(
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
		
		return $this->session->offsetGet('itsw-session-data');
	}
	
	/**
	 * @return null[]
	 */
	public function getSessionData(): array {
		if (is_null($this->session)) {
			if ($this->isFront()) {
				$this->session = $this->container->get('session');
			} else {
				return [];
			}
		}
		
		$defaultData = [
			'manufacturer'  => NULL,
			'model'         => NULL,
			'type'          => NULL,
			'car'           => NULL,
			'description'   => NULL,
			'title'         => NULL
		];
		
		if ($this->session->offsetExists('itsw-session-data')) {
			return array_merge($defaultData, $this->session->offsetGet('itsw-session-data'));
		} else if ($cookieData = Shopware()->Front()->Request()->getCookie('itsw_cache')) {
			try {
				$sessionData = json_decode($cookieData, TRUE, 512, JSON_THROW_ON_ERROR);
				$sessionData = array_merge($defaultData, $sessionData);
				$this->session->offsetSet('itsw-session-data', $sessionData);
			} catch (\JsonException $exception) {
				$this->setLog($exception);
				$this->session->offsetSet('itsw-session-data', $defaultData);
			}
		}
		
		return array_merge($defaultData, $this->session->offsetGet('itsw-session-data'));
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
	 * @param int|null $car
	 * @return string[]
	 */
	public function getCarSeoUrl(int $manufacturer = NULL, int $model = NULL, int $car = NULL): array {
		$manufacturer = $manufacturer ?: $this->getSessionData()['manufacturer'];
		$model = $model ?: $this->getSessionData()['model'];
		$car = $car ?: $this->getSessionData()['car'];
		
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
		return $this->isFront() && !empty($config->setOffline) && (strpos($config->offlineIp, $this->front->Request()->getClientIp()) === false);
	}
	
	/**
	 * @return bool
	 */
	public function getDevelopmentMode(): bool {
		$environment = $this->container->getParameter('kernel.environment');
		return ($environment === 'dev');
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
	
	/**
	 * @return bool
	 */
	public function isFront(): bool {
		return !is_null($this->front);
	}
	
	/**
	 * @param int  $tecdocId
	 * @param bool $forDescription
	 * @return string|null
	 */
	public function getCarDisplayForView(int $tecdocId, bool $forDescription = FALSE): ?string {
		try {
			$car = $this->modelManager->getRepository(Car::class)->getCarsQuery([
				'select' => 'cars',
				'conditions' => [
					'cars.tecdocId' => $tecdocId
				]
			])
				->useQueryCache(TRUE)
				->getOneOrNullResult();
			
			if (is_object($car)) {
				if ($forDescription) {
					return sprintf('%s %s %s %d PS', $car->getManufacturer()->getDisplay(), $car->getModel()->getDisplay(), $car->getType()->getDisplay(), $car->getPs());
				} else {
					return sprintf('%s %s %s - %d PS - %s ', $car->getManufacturer()->getDisplay(), $car->getModel()->getDisplay(), $car->getType()->getDisplay(), $car->getPs(), $car->getBuildFrom()->format('Y'));
				}
			}
		} catch (NonUniqueResultException $nonUniqueResultException) {
		
		}
		
		return NULL;
	}
}