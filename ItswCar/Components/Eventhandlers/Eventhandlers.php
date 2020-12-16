<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    17:42
 * Datei:   Eventhandlers.php
 * @package ItswCar\Components\Eventhandlers
 */

namespace ItswCar\Components\Eventhandlers;


use ItswCar\Components\Services\Services;

class Eventhandlers {
	/**
	 * @var \ItswCar\Components\Services\Services
	 */
	protected $service;
	
	/**
	 * Eventhandlers constructor.
	 * @param \ItswCar\Components\Services\Services $service
	 */
	public function __construct(Services $service) {
		$this->service = $service;
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPostDispatchSecureFrontend(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$subject = $actionEventArgs->getSubject();
		
		$subject->View()->assign('ITSW-SESSION', $this->service->getSessionData());
	}
}