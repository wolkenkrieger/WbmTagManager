<?php declare(strict_types=1);

/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   08.02.2021
 * Zeit:    15:40
 * Datei:   ExtendedArticles.php
 * @package ItswCar\Controllers\Api
 */

use ItswCar\Components\Api\Resource\ExtendedArticle;
use Shopware\Components\Api\Manager;

class Shopware_Controllers_Api_ExtendedArticles extends Shopware_Controllers_Api_Rest {
	protected $resource;
	
	/**
	 * Shopware_Controllers_Api_ExtendedArticles constructor.
	 * @param \ItswCar\Components\Api\Resource\ExtendedArticle|null $extendedArticle
	 */
	public function __construct(ExtendedArticle $extendedArticle = NULL) {
		if (NULL === $extendedArticle) {
			$this->resource = Manager::getResource('itswcar.resource.extendedarticle');
		} else {
			$this->resource = $extendedArticle;
		}
		
		parent::__construct();
	}
	
	/**
	 *
	 */
	public function indexAction(): void {
		$request = $this->Request();
		$limit = (int) $request->getParam('limit', 1000);
		$offset = (int) $request->getParam('start', 0);
		$sort = $request->getParam('sort', []);
		$filter = $request->getParam('filter', []);
		
		$result = $this->resource->getList($offset, $limit, $filter, $sort, [
			'language' => $request->getParam('language'),
		]);
		
		$view = $this->View();
		$view->assign($result);
		$view->assign('success', true);
	}
	
	/**
	 * Get one product
	 *
	 * GET /api/articles/{id}
	 */
	public function getAction(): void {
		$request = $this->Request();
		$id = $request->getParam('id');
		$useNumberAsId = (bool) $request->getParam('useNumberAsId', 0);
		
		if ($useNumberAsId) {
			$product = $this->resource->getOneByNumber($id, [
				'language' => $request->getParam('language'),
				'considerTaxInput' => $request->getParam('considerTaxInput'),
			]);
		} else {
			$product = $this->resource->getOne($id, [
				'language' => $request->getParam('language'),
				'considerTaxInput' => $request->getParam('considerTaxInput'),
			]);
		}
		
		$view = $this->View();
		$view->assign('data', $product);
		$view->assign('success', true);
	}
	
	/**
	 * Create new product
	 *
	 * POST /api/articles
	 */
	public function postAction(): void	{
		$product = $this->resource->create($this->Request()->getPost());
		
		$location = $this->apiBaseUrl . 'articles/' . $product->getId();
		$data = [
			'id' => $product->getId(),
			'location' => $location,
		];
		
		$this->View()->assign(['success' => true, 'data' => $data]);
		$this->Response()->headers->set('location', $location);
	}
}