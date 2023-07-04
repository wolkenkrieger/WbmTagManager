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

use ItswCar\Traits\LoggingTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Output\OutputInterface;

class Services {
	use LoggingTrait;
	
	public ContainerInterface $container;
	public ModelManager $modelManager;
	public $front;
	public $cache;
	public int $shopId = 1;
	public int $rootCategoryId = 5;
	
	/**
	 * Services constructor.
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
	 * @param \Shopware\Components\Model\ModelManager            $modelManager
	 */
	public function __construct(ContainerInterface $container, ModelManager $modelManager) {
		$this->container = $container;
		$this->modelManager = $modelManager;
		$this->cache = $this->container->get('shopware.cache_manager');
		
		if ($this->container->initialized('shop')) {
			$this->shopId = $this->container->get('shop')->getId();
		}
	}
	
	
	/**
	 * @return \Symfony\Component\DependencyInjection\ContainerInterface
	 */
	public function getContainer(): ContainerInterface {
		return $this->container;
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
	 * @return int
	 */
	public function getRootCategoryId(): int {
		return $this->rootCategoryId;
	}
}