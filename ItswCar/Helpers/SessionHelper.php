<?php declare(strict_types=1);
/**
 * Autor:    Rico Wunglück <development@itsw.dev>
 * Datum:    15.09.2021
 * Zeit:    11:18
 * Datei:    SessionHelper.php
 * @package ItswCar\Helpers
 */

namespace ItswCar\Helpers;

use ItswCar\Models\Car;
use Symfony\Component\HttpFoundation\Cookie;
use ItswCar\Traits\LoggingTrait;

class SessionHelper {
	use LoggingTrait;
	
	/**
	 * @param array $data
	 * @return array
	 */
	public function setSessionData(array $data = []): array {
		$container = Shopware()->Container();
		
		$defaultData = [
			'manufacturer'  => NULL,
			'model'         => NULL,
			'type'          => NULL,
			'car'           => NULL,
			'description'   => NULL,
			'title'         => NULL,
			'url'           => NULL,
		];
		
		$session = $container->get('session');
		$configHelper = $container->get('itsw.helper.config');
		$seoHelper = $container->get('itsw.helper.seo');
		
		$data = array_merge($defaultData, $data);
		
		$viewData = $container->get('models')->getRepository(Car::class)->getCarDisplayForView((int)$data['car']);
		
		$data['description'] = $viewData['description']??NULL;
		$data['title'] = $viewData['title']??NULL;
		$data['url'] = $seoHelper->getCarSeoUrl($data['manufacturer'], $data['model'], $data['car'])?:NULL;
		
		$session->offsetSet('itsw-car-session-data', $data);
		
		try {
			if ($dataEncoded = json_encode($data, JSON_THROW_ON_ERROR)) {
				Shopware()->Front()->Response()->headers->setCookie(
					new Cookie(
						'itsw-car-session-data',
						$dataEncoded,
						0,
						$configHelper->getBasePath(),
						NULL,
						FALSE,
						FALSE,
						TRUE
					)
				);
				
				$this->debug(__METHOD__, $data);
			}
			
			Shopware()->Front()->Response()->headers->setCookie(
				new Cookie(
					'itsw-car-cache-data',
					(string)$data['car'],
					0,
					$configHelper->getBasePath(),
					NULL,
					FALSE,
					FALSE,
					TRUE
				)
			);
			
			$this->debug(__METHOD__, ['car' => $data['car']]);
			
		} catch (\Exception $exception) {
			$this->error($exception);
		}
		
		
		return $data;
	}
	
	/**
	 * @return null[]
	 */
	public function getSessionData(): array {
		$container = Shopware()->Container();
		
		$sessionData = [
			'manufacturer'  => NULL,
			'model'         => NULL,
			'type'          => NULL,
			'car'           => NULL,
			'description'   => NULL,
			'title'         => NULL,
			'url'           => NULL,
		];
		
		$session = $container->get('session');
		
		if ($session->offsetExists('itsw-car-session-data')) {
			$sessionData = array_merge($sessionData, $session->offsetGet('itsw-car-session-data'));
		} else if ($cookieData = Shopware()->Front()->Request()->getCookie('itsw-car-session-data')) {
			try {
				$cookieSessionData = json_decode($cookieData, TRUE, 512, JSON_THROW_ON_ERROR);
				$sessionData = array_merge($sessionData, $cookieSessionData);
				$session->offsetSet('itsw-car-session-data', $sessionData);
			} catch (\JsonException $exception) {
				$this->error($exception);
				$session->offsetSet('itsw-car-session-data', $sessionData);
			}
		}
		
		$this->debug(__METHOD__, $sessionData);
		
		return $sessionData;
	}
	
	/**
	 * @return array
	 */
	public function resetSession(): array {
		return $this->setSessionData();
	}
}