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
use Shopware\Components\Random;
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
			'uuid'          => NULL,
		];
		
		$session = $container->get('session');
		$configHelper = $container->get('itsw.helper.config');
		$seoHelper = $container->get('itsw.helper.seo');
		
		$sessionData = [];
		if ($session->offsetExists('itsw-car-session-data')) {
			$sessionData = $session->offsetGet('itsw-car-session-data');
		}
		
		$data = array_merge($defaultData, $data);
		
		$data['uuid'] = $sessionData['uuid']?? $data['uuid']?: Random::getAlphanumericString(32);
		
		$viewData = $container->get('models')->getRepository(Car::class)->getCarDisplayForView((int)$data['car']);
		
		$data['description'] = $viewData['description']??NULL;
		$data['title'] = $viewData['title']??NULL;
		$data['url'] = $seoHelper->getCarSeoUrl($data['manufacturer'], $data['model'], $data['car'])?:NULL;
		
		$session->offsetSet('itsw-car-session-data', $data);
		
		try {
			$dataEncoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE);
			
			if ($dataEncoded !== FALSE ) {
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
			'uuid'          => NULL,
		];
		
		$session = $container->get('session');
		
		if ($session->offsetExists('itsw-car-session-data')) {
			$sessionData = array_merge($sessionData, $session->offsetGet('itsw-car-session-data'));
		} else if ($cookieData = Shopware()->Front()->Request()->getCookie('itsw-car-session-data')) {
			try {
				$cookieSessionData = json_decode($cookieData, TRUE, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE);
				$sessionData = array_merge($sessionData, $cookieSessionData);
				$this->setSessionData($sessionData);
			} catch (\JsonException $exception) {
				$this->error($exception);
				$this->error('$cookieData', [
					'data' => $cookieData,
					'server' => $_SERVER
				]);
				$this->setSessionData($sessionData);
			}
		} else {
			$this->setSessionData($sessionData);
		}
		
		$this->debug(__METHOD__, $sessionData);
		
		return $sessionData;
	}
	
	/**
	 * @return array
	 */
	public function resetSession(): array {
		try {
			Shopware()->Front()->Response()->headers->clearCookie('itsw-car-session-data');
			Shopware()->Front()->Response()->headers->clearCookie('itsw-car-cache-data');
		} catch (\Exception $exception) {
			$this->error($exception);
		}
		
		return $this->setSessionData();
	}
}