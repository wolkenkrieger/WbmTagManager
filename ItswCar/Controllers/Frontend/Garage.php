<?php declare(strict_types=1);

/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 06.12.2021
 * Time: 08:32
 * File: Garage.php
 */

use ItswCar\Helpers\ConfigHelper;
use ItswCar\Helpers\SeoHelper;
use ItswCar\Helpers\SessionHelper;
use ItswCar\Models\Garage;
use ItswCar\Traits\LoggingTrait;

class Shopware_Controllers_Frontend_Garage extends Enlight_Controller_Action {
	use LoggingTrait;
	
	public SessionHelper $sessionHelper;
	public ConfigHelper $configHelper;
	public SeoHelper $seoHelper;
	public Shopware\Components\Model\ModelManager $entityManager;
	
	public function initController(Enlight_Controller_Request_RequestHttp $request, Enlight_Controller_Response_ResponseHttp $response): void {
		parent::initController($request, $response);
		
		$this->setContainer(Shopware()->Container());
		$this->sessionHelper = $this->get('itsw.helper.session');
		$this->configHelper = $this->get('itsw.helper.config');
		$this->seoHelper = $this->get('itsw.helper.seo');
		$this->entityManager = $this->get('models');
	}
	
	/**
	 * @return void
	 */
	public function preDispatch(): void {
		parent::preDispatch();
		$this->Response()->setHeader('x-robots-tag', 'noindex');
	}
	
	/**
	 * @throws \Exception
	 */
	public function indexAction(): void {
		$userLoggedIn = $this->sessionHelper->isUserLoggedIn();
		$userId = $this->sessionHelper->getUserId();
		$sessionCar = $this->sessionHelper->getSessionData()['car'];
		$cars = [];
		$tecdocIds = [];
		
		if ($userLoggedIn && $userId) {
			
			$garageCars = $this->entityManager->getRepository(Garage::class)
				->findBy([
					'userId' => $userId,
					'active' => TRUE
				]);
			
			foreach($garageCars as $garageCar) {
				$tecdocIds[] = $garageCar->getTecdocId();
			}
			
			if (count($tecdocIds)) {
				$cars = $this->entityManager->getRepository(Garage::class)
					->getCarsForCarfinder([
						'select' => [
							'cars'
						],
						'conditions' => [
							'cars.tecdocId IN (' . implode(',', $tecdocIds) . ')'
						]
					]);
			}
		}
		
		$this->View()->assign('sUserLoggedIn', $userLoggedIn, TRUE);
		$this->View()->assign('sOneTimeAccount', Shopware()->Session()->offsetGet('sOneTimeAccount'));
		$this->View()->assign('canAddCar', $sessionCar && !in_array($sessionCar, $tecdocIds, TRUE));
		$this->View()->assign('cars', $cars);
	}
	
	/**
	 * @throws \Exception
	 */
	public function deleteAction(): void {
		$this->View()->setTemplate();
		$this->Front()->Plugins()->ViewRenderer()->setNoRender();
		
		$userId = $this->sessionHelper->getUserId();
		$tecdocId = (int)$this->Request()->getParam('car', 0);
		
		if ($userId && $tecdocId) {
			$garageCar = $this->entityManager->getRepository(Garage::class)
				->findOneBy([
					'tecdocId' => $tecdocId,
					'userId' => $userId
				]);
			if (is_object($garageCar)) {
				try {
					$this->entityManager->persist($garageCar);
					$garageCar->setActive(FALSE);
					$this->entityManager->flush();
				} catch (\Exception $exception) {
					$this->error($exception);
				}
			}
		}
		
		$this->forward('index', 'garage', 'frontend');
	}
	
	/**
	 * @throws \Exception
	 */
	public function addAction(): void {
		$this->View()->setTemplate();
		$this->Front()->Plugins()->ViewRenderer()->setNoRender();
		
		$userId = $this->sessionHelper->getUserId();
		$tecdocId = (int)$this->Request()->getParam('car', 0);
		$redirect = (int)$this->Request()->getParam('redirect', 0);
		
		if ($userId && $tecdocId) {
			$garageCar = $this->entityManager->getRepository(Garage::class)
				->findOneBy([
					'userId' => $userId,
					'tecdocId' => $tecdocId
				]);
			
			try {
				if (is_object($garageCar)) {
					$this->entityManager->persist($garageCar);
					$garageCar->setActive(TRUE);
				} else {
					$garageCar = new Garage();
					$garageCar->setActive(TRUE);
					$garageCar->setTecdocId($tecdocId);
					$garageCar->setUserId($userId);
					$this->entityManager->persist($garageCar);
				}
				$this->entityManager->flush();
			} catch (\Exception $exception) {
				$this->error($exception);
			}
		}
		
		if($redirect) {
			$this->forward('set-car', 'carfinder', 'widgets', $this->Request()->getParams());
		} else {
			$this->forward('index');
		}
	}
}