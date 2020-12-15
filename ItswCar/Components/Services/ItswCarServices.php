<?php
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   12.12.2020
 * Zeit:    16:06
 * Datei:   ItswCarServices.php
 * @package ItswCar\Components\Services
 */

namespace ItswCar\Components\Services;

use ItswCar\Models\Car;
use Shopware\Bundle\StoreFrontBundle\Gateway\DBAL\Hydrator\Hydrator;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

class ItswCarServices {
	protected $container;
	protected $modelManager;
	protected $pluginLogger;
	
	/**
	 * ItswCarServices constructor.
	 * @param \Shopware\Components\DependencyInjection\Container $container
	 * @param \Shopware\Components\Model\ModelManager            $modelManager
	 */
	public function __construct(Container $container, ModelManager $modelManager) {
		$this->container = $container;
		$this->modelManager = $modelManager;
		$this->pluginLogger = $container->get('pluginlogger');
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
}