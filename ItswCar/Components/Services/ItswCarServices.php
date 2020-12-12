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


use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Output\OutputInterface;

class ItswCarServices {
	protected $container;
	protected $modelManager;
	
	/**
	 * ItswCarServices constructor.
	 * @param \Shopware\Components\DependencyInjection\Container $container
	 * @param \Shopware\Components\Model\ModelManager            $modelManager
	 */
	public function __construct(Container $container, ModelManager $modelManager) {
		$this->container = $container;
		$this->modelManager = $modelManager;
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
}