<?php declare(strict_types=1);

/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 02.05.2022
 * Time: 09:15
 * File: ExtendedCategories.php
 */

use ItswCar\Components\Api\Resource\ExtendedCategory;

class Shopware_Controllers_Api_ExtendedCategories extends Shopware_Controllers_Api_Rest {
	
	/** @var \ItswCar\Components\Api\Resource\ExtendedCategory|mixed|object|\Symfony\Component\DependencyInjection\Container|null  */
	protected $resource;
	
	/**
	 * @param \ItswCar\Components\Api\Resource\ExtendedCategory|null $resource
	 * @throws \Exception
	 */
	public function __construct(?ExtendedCategory $resource) {
		$this->resource = $resource ?? $this->get('itswcar.resource.extendedcategory');
		
		parent::__construct();
	}
	
	public function getAction(): void {
		$request = $this->Request();
		$id = (int)$request->get('id', 0);
		$useEbayCategoryIdAsId = (bool)$request->get('useEbayCategoryId', 0);
		
		var_dump($id, $useEbayCategoryIdAsId);die;
	}
}