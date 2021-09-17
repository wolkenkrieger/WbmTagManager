<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   26.01.2021
 * Zeit:    13:35
 * Datei:   CategoryConditionHandler.php
 * @package ItswCar\Components\Eventhandlers
 */

namespace ItswCar\Components\Eventhandlers;

use Doctrine\DBAL\Connection;
use ItswCar\Models\Car;
use Shopware\Bundle\SearchBundle\ConditionInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;

class CategoryConditionHandler extends \Shopware\Bundle\SearchBundleDBAL\ConditionHandler\CategoryConditionHandler {
	
	/**
	 * @param \Shopware\Bundle\SearchBundle\ConditionInterface              $condition
	 * @param \Shopware\Bundle\SearchBundleDBAL\QueryBuilder                $query
	 * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface $context
	 */
	public function generateCondition(ConditionInterface $condition, QueryBuilder $query, ShopContextInterface $context): void {
		$container = Shopware()->Container();
		
		if (!$container->has('itsw.helper.session') ||
			!$container->has('itsw.helper.config') ||
			!$container->get('itsw.helper.config')->isFront()) {
			return;
		}
		
		$sessionData = $container->get('itsw.helper.session')->getSessionData();
		$repository = $container->get('models')->getRepository(Car::class);
		
		parent::generateCondition($condition, $query, $context);
		
		if ($sessionData['car']) {
			if ($container->get('itsw.helper.config')->getValue('category_condition_show_unlinked', 'ItswCar')) {
				$variantIds = array_merge($repository->getVariantIdsByTecdocId($sessionData['car']), $repository->getVariantIdsWithoutArticleCarLink());
			} else {
				$variantIds = $repository->getVariantIdsByTecdocId($sessionData['car']);
			}
			
			$query->andWhere('product.main_detail_id IN (:variantIds)')
				->setParameter('variantIds', $variantIds, Connection::PARAM_INT_ARRAY);
		}
	}
}