<?php declare(strict_types=1);
/**
 * Autor:    Rico Wunglück <development@itsw.dev>
 * Datum:    14.09.2021
 * Zeit:    10:45
 * Datei:    ConfigHelper.php
 */

namespace ItswCar\Helpers;

use Google\Exception;
use Shopware\Components\ConfigWriter;
use Shopware\Models\Shop\DetachedShop;
use Shopware\Models\Shop\Shop;
use Shopware_Components_Config;
use Monolog\Logger;

use ItswCar\Traits\LoggingTrait;

class ConfigHelper {
	use LoggingTrait;
	
	
	public const PAY_UNTIL_DAYS = 10;
	
	/**
	 * @var \Shopware_Components_Config
	 */
	public Shopware_Components_Config $configComponent;
	/**
	 * @var bool
	 */
	public bool $hasSubshops;
	/**
	 * @var \Shopware\Models\Shop\DetachedShop|null
	 */
	public ?DetachedShop $shop;
	
	/**
	 * @param \Shopware_Components_Config $configComponent
	 */
	public function __construct(Shopware_Components_Config $configComponent) {
		$this->configComponent = $configComponent;
	}
	
	/**
	 * @return bool
	 */
	public function isFront(): bool {
		try {
			if (Shopware()->Container()->initialized('shop')) {
				$shop = Shopware()->Shop();
				$isFront = ($shop !== NULL);
			} else {
				$isFront = FALSE;
			}
		} catch (\Exception $exception) {
			$isFront = FALSE;
		}
		return $isFront;
	}
	
	/**
	 * @return int
	 */
	public function getShopId(): int {
		if ($this->isFront()) {
			return Shopware()->Shop()->getId();
		} else {
			return $this->getMainShop()->getId();
		}
	}
	
	/**
	 * @return array
	 */
	public function getSubshops(): array {
		/** @var \Shopware\Models\Shop\Repository $shopRepository */
		$shopRepository = Shopware()->Container()->get('models')->getRepository(Shop::class);
		return $shopRepository->findAll();
	}
	
	/**
	 * @return bool
	 */
	public function hasSubshops(): bool {
		if ($this->hasSubshops === null) {
			$this->hasSubshops = count($this->getSubshops()) > 1;
		}
		return $this->hasSubshops;
	}
	
	/**
	 * @param null $shop
	 */
	public function setShop($shop = NULL): void {
		if ($shop === NULL) {
			$shop = $this->getMainShop();
		} elseif (!is_object($shop)) {
			/** @var \Shopware\Models\Shop\Repository $shopRepository */
			$shopRepository = Shopware()->Container()->get('models')->getRepository(Shop::class);
			$shop           = $shopRepository->find($shop);
			
		}
		$this->shop = $shop;
		$this->configComponent->setShop($shop);
	}
	
	/**
	 * @return \Shopware\Models\Shop\DetachedShop
	 */
	public function getShop(): DetachedShop {
		try {
			if (!$this->isFront()) {
				throw new Exception();
			}
			$shop = Shopware()->Shop();
			if (empty($shop)) {
				throw new Exception();
			}
			return $shop;
		} catch (Exception $exception) {
			$modelManager = Shopware()->Container()->get('models');
			/** @var \Shopware\Models\Shop\Repository $shopRepo */
			$repository = $modelManager->getRepository(Shop::class);
			$shop = $repository->getDefault();
			return DetachedShop::createFromShop($shop);
		}
	}
	
	/**
	 * @return object|\Shopware\Models\Shop\Shop|null
	 */
	public function getMainShop() {
		/** @var \Shopware\Models\Shop\Repository $shopRepository */
		$shopRepository = Shopware()->Container()->get('models')->getRepository(Shop::class);
		
		return $shopRepository->findOneBy(['default' => 1]);
	}
	
	/**
	 * @return bool
	 */
	public function isNoGuestMode(): bool {
		return 0 === $this->configComponent->get('NoAccountDisable');
	}
	
	/**
	 * @return bool
	 */
	public function isMaintenanceMode(): bool {
		return
			$this->configComponent->get('setoffline') &&
			(strpos($this->configComponent->get('offlineip'), Shopware()->Front()->Request()->getClientIp()) === FALSE);
	}
	
	/**
	 * @return bool
	 */
	public function isDevelopmentMode(): bool {
		$environment = Shopware()->Container()->getParameter('kernel.environment');
		return (strtolower($environment) === 'dev');
	}
	
	/**
	 * @param        $key
	 * @param        $value
	 * @param bool   $clearCache
	 * @param string $namespace
	 */
	public function setValue($key, $value, bool $clearCache = TRUE, string $namespace = 'Itsw'): void {
		$container = Shopware()->Container();
		/** @var ConfigWriter $writer */
		$writer = $container->get('config_writer');
		$writer->save($key, $value, $namespace, ($this->shop ? $this->shop->getId() : 1));

		if ($clearCache && $container->has('shopware.cache_manager')) {
			$container->get('shopware.cache_manager')->clearConfigCache();
		}
		
	}
	
	/**
	 * @param        $key
	 * @param string $namespace
	 * @return mixed|null
	 */
	public function getValue($key, string $namespace = 'Itsw') {
		return $this->configComponent->getByNamespace($namespace, $key);
	}
	
	/**
	 * @return string
	 */
	public function getBasePath(): string {
		if ($this->isFront()) {
			//return (Shopware()->Shop()->getBasePath()) ? : '/';
			return (string)Shopware()->Shop()->getBasePath();
		}
		
		return '/';
	}
	
	/**
	 * @return string
	 */
	public function getBaseUrl(): string {
		if ($this->isFront()) {
			return Shopware()->Shop()->getBaseUrl();
		}
		
		return '';
	}
	
	/**
	 * @return string
	 */
	public function getDocPath(): string {
		return Shopware()->DocPath() . 'files' .DIRECTORY_SEPARATOR. 'documents' . DIRECTORY_SEPARATOR;
	}
	
	/**
	 * @return array|false|string
	 */
	public function getEnvironment() {
		return getenv('SHOPWARE_ENV')?:'production';
	}
	
	/**
	 * @return float|int
	 */
	public function getUserGroupDiscount() {
		if ($this->isFront() && Shopware()->Shop()->getCustomerGroup()->getMode()) {
			return Shopware()->Shop()->getCustomerGroup()->getDiscount();
		}
		
		return 0;
	}
	
	/**
	 * @return int
	 */
	public function getLogLevel(): int {
		$logLevel = $this->getValue('log_level', 'ItswCar')?:Logger::WARNING;
		
		return (int)$logLevel;
	}
	
	/**
	 * @return false|string
	 */
	public function getPayUntilDate() {
		$days = $this->getValue('pay_until_days', 'ItswCar')?:self::PAY_UNTIL_DAYS;
		
		try {
			$date = new \DateTime();
			$payUntilDate = $date->add(new \DateInterval(sprintf('P%dD', $days)));
			
			/*
			 * Numeric representation of the day of the week 0 (for Sunday) through 6 (for Saturday)
			 */
			if ((int)$payUntilDate->format('w') === 0) {
				$payUntilDate = $payUntilDate->add(new \DateInterval('P1D'));
			} else if ((int)$payUntilDate->format('w') === 6) {
				$payUntilDate = $payUntilDate->add(new \DateInterval('P2D'));
			}
			
			return $payUntilDate->format('d.m.Y');
		} catch (\Exception $exception) {
			$this->error($exception);
			
			$then = time() + (86400 * $days);
			$day = getdate($then);
			
			if ($day['wday'] === 0) {
				$then += 86400;
			} else if ($day['wday'] === 6) {
				$then += (86400 * 2);
			}
			
			return date("d.m.Y", $then);
		}
	}
	
	/**
	 * @throws \Doctrine\DBAL\Driver\Exception
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function getShippingInfos(int $countryId = NULL): array {
		$builder = Shopware()->Container()->get('dbal_connection')->createQueryBuilder();
		
		$query = $builder->select([
			'd.name AS dispatchName',
			'd.description AS dispatchDescription',
			'd.comment AS dispatchComment',
			'c.id as countryID',
			'c.countryiso as countryISO',
			'c.countryname as countryName',
			'ROUND(IF(c.taxfree = 1, sc.value / 1.19, sc.value), 2) AS ShippingCost'
			])
			->from('s_premium_dispatch', 'd')
			->join('d', 's_premium_dispatch_countries', 'dc', 'd.id = dc.dispatchID')
			->join('d', 's_premium_shippingcosts', 'sc', 'd.id = sc.dispatchID')
			->join('dc','s_core_countries', 'c', 'dc.countryID = c.id')
			->where('d.active = 1')
			->andWhere('c.active = 1')
			->andWhere('c.allow_shipping = 1');
		
		if ($countryId !== NULL) {
			$query
				->andWhere('c.id = :country_id')
				->setParameter(':country_id', $countryId);
		}
		
		return $query->execute()->fetchAllAssociative();
	}
}