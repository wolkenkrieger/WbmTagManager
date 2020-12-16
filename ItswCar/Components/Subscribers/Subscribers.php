<?php
declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    17:45
 * Datei:   Subscribers.php
 * @package ItswCar\Components\Subscribers
 */

namespace ItswCar\Components\Subscribers;

use Enlight\Event\SubscriberInterface;
use ItswCar\Components\Eventhandlers\Eventhandlers as Eventhandler;
use ItswCar\Components\Services\Services;
use Shopware\Components\DependencyInjection\Container;


class Subscribers implements SubscriberInterface {
	/**
	 * @var \ItswCar\Components\Eventhandlers
	 */
	protected $eventHandler;
	/**
	 * @var \Shopware\Components\DependencyInjection\Container
	 */
	protected $container;
	/**
	 * @var \ItswCar\Components\Services\Services
	 */
	protected $service;
	
	/**
	 * Subscribers constructor.
	 * @param \Shopware\Components\DependencyInjection\Container $container
	 * @param \ItswCar\Components\Services\Services              $service
	 */
	public function __construct(Container $container, Services $service) {
		$this->container = $container;
		$this->service = $service;
		
		$this->eventHandler = new Eventhandler($this->service);
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getSubscribedEvents(): array {
		return [
			'Enlight_Controller_Action_PostDispatchSecure_Frontend'         => 'onPostDispatchSecureFrontend',
		];
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPostDispatchSecureFrontend(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$this->eventHandler->onPostDispatchSecureFrontend($actionEventArgs);
	}
}