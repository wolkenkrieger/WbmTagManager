<?php declare(strict_types=1);
/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 29.04.2022
 * Time: 10:36
 * File: ExtendedCategory.php
 * @package ItswCar\Components\Api\Resource
 */

namespace ItswCar\Components\Api\Resource;

use Shopware\Components\Api\Resource\Resource;
use Shopware\Components\Api\Resource\Category as OriginalResource;

use ItswCar\Traits\LoggingTrait;

class ExtendedCategory extends Resource {
	use LoggingTrait;
	
	/** @var \Shopware\Components\Api\Resource\Category  */
	private OriginalResource $originalResource;
	
	public function __construct(OriginalResource $originalResource) {
		$this->originalResource = $originalResource;
	}
	
	/**
	 * @param $id
	 * @return void
	 */
	public function getOneByEbayCategoryId($id) {
		die('hier');
	}
}