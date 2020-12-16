<?php
declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   12.12.2020
 * Zeit:    16:06
 * Datei:   ItswCarServices.php
 * @package ItswCar\Components\Services
 */

namespace ItswCar\Components\Services;

use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\HttpFoundation\Cookie;

class ItswCarServices {
	protected $container;
	protected $modelManager;
	protected $pluginLogger;
	protected $front;
	protected $basePath;
	protected $cache;
	protected $session;
	
	protected $rootCategoryId = 5;
	
	/**
	 * ItswCarServices constructor.
	 * @param \Shopware\Components\DependencyInjection\Container $container
	 * @param \Shopware\Components\Model\ModelManager            $modelManager
	 */
	public function __construct(Container $container, ModelManager $modelManager) {
		$this->container = $container;
		$this->modelManager = $modelManager;
		$this->pluginLogger = $this->container->get('pluginlogger');
		$this->front = $this->container->get('front');
		$this->cache = $this->container->get('shopware.cache_manager');
		$this->session = $this->container->get('session');
		$this->basePath = $this->container->get('shop')->getBasePath();
		
		if ($this->basePath === null || $this->basePath === '') {
			$this->basePath = '/';
		}
		
		$this->setSessionData($this->getSessionData());
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
		$manufacturers = $this->modelManager->getRepository(\ItswCar\Models\Car::class)
			->getManufacturersQuery([
				'manufacturers.active = 1'
			])
			->getResult();
		
		foreach($manufacturers as $manufacturer) {
			$return[] = [
				'name' => $manufacturer->getName(),
				'url' => $this->getCleanedStringForUrl($manufacturer->getName()),
				'display' => $manufacturer->getDisplay(),
				'id' => $manufacturer->getId()
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
		
		$models = $this->modelManager->getRepository(\ItswCar\Models\Car::class)
			->getModelsByManufacturerIdQuery($manufacturerId, [
				'models.active = 1',
				'cars.active = 1'
			])
			->getResult();
		
		foreach($models as $model) {
			$return[] = [
				'name' => $model->getName(),
				'url' => $this->getCleanedStringForUrl($model->getName()),
				'display' => $model->getDisplay(),
				'id' => $model->getId()
			];
		}
		
		return $return??[];
	}
	
	/**
	 * @param int|null $manufacturerId
	 * @param int|null $modelId
	 * @return array
	 */
	public function getTypesForCarfinder(int $manufacturerId = NULL, int $modelId = NULL): array {
		if (!$manufacturerId) {
			throw new ParameterNotFoundException("manufacturerId");
		}
		if (!$modelId) {
			throw new ParameterNotFoundException("modelId");
		}
		$types = $this->modelManager->getRepository(\ItswCar\Models\Car::class)
			->getTypesByManufacturerIdAndModelIdQuery($manufacturerId, $modelId, [
				'types.active = 1',
				'cars.active = 1'
			])
			->getResult();
		
		foreach($types as $type) {
			$return[] = [
				'name' => $type->getName(),
				'url' => $this->getCleanedStringForUrl($type->getName()),
				'display' => $type->getDisplay(),
				'id' => $type->getId()
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
	public function getCarsForCarfinder(int $manufacturerId = NULL, int $modelId = NULL, int $typeId = NULL): array {
		if (!$manufacturerId) {
			throw new ParameterNotFoundException("manufacturerId");
		}
		if (!$modelId) {
			throw new ParameterNotFoundException("modelId");
		}
		if (!$typeId) {
			throw new ParameterNotFoundException("typeId");
		}
		
		$cars = $this->modelManager->getRepository(\ItswCar\Models\Car::class)
			->getCarsByManufacturerIdAndModelIdAndTypeIdQuery($manufacturerId, $modelId, $typeId, [
				'cars.active = 1'
			])
			->getResult();
		
		return $cars??[];
	}
	
	/**
	 * @param array $data
	 * @param bool  $full
	 * @return array
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
		
		if ($dataEncoded = json_encode($data)) {
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
		if ($cookieData = $this->front->Request()->getCookie('itsw_cache')) {
			$sessionData = json_decode($cookieData, TRUE);
			$this->session->offsetSet('itsw-session-data', $sessionData);
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
}