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

class Shopware_Controllers_Widgets_Garage extends Enlight_Controller_Action {
	use LoggingTrait;
	
	public SessionHelper $sessionHelper;
	public Doctrine\ORM\EntityManager $entityManager;
	
	public function initController(Enlight_Controller_Request_RequestHttp $request, Enlight_Controller_Response_ResponseHttp $response): void {
		parent::initController($request, $response);
		
		$this->setContainer(Shopware()->Container());
		$this->sessionHelper = $this->get('itsw.helper.session');
		$this->entityManager = $this->get('models');
	}
	
	/**
	 *
	 */
	public function preDispatch(): void {
		parent::preDispatch();
		$this->Response()->setHeader('x-robots-tag', 'noindex');
	}
	
	/**
	 * @throws \Exception
	 */
	public function infoAction(): void {
		if (!$this->sessionHelper->isUserLoggedIn() || is_null($userId = $this->sessionHelper->getUserId())) {
			$this->View()->setTemplate();
			$this->Front()->Plugins()->ViewRenderer()->setNoRender();
			
			return;
		}
		
		$sCarQuantity = $this->entityManager->getRepository(Garage::class)->count([
			'userId' => $userId,
			'active' => TRUE
		]);
		
		$this->View()->assign('sCarQuantity', $sCarQuantity);
	}
}