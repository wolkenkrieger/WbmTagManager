<?php
/**
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   03.08.2021
 * Zeit:    17:17
 * Datei:   CustomerGroupConditionHandler.php
 * @package ItswCar\Components\Eventhandlers
 */

namespace ItswCar\SearchBundleDBAL\Condition;

use Doctrine\DBAL\Connection;
use Shopware\Bundle\SearchBundle\Condition\CustomerGroupCondition;
use Shopware\Bundle\SearchBundle\ConditionInterface;
use Shopware\Bundle\SearchBundleDBAL\ConditionHandlerInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;

class CustomerGroupConditionHandler implements ConditionHandlerInterface {
	/**
	 * {@inheritdoc}
	 */
	public function supportsCondition(ConditionInterface $condition) {
		return $condition instanceof CustomerGroupCondition;
	}
	
	/**
	 * {@inheritdoc}
	 * @throws \JsonException
	 */
	public function generateCondition(ConditionInterface $condition, QueryBuilder $query, ShopContextInterface $context) {
		$key = 'customerGroupIds' . md5(json_encode($condition, JSON_THROW_ON_ERROR));
		
		$query->leftJoin(
			'product',
			's_articles_avoid_customergroups',
			'avoidCustomerGroup',
			'avoidCustomerGroup.articleID = product.id
             AND avoidCustomerGroup.customerGroupId IN (:' . $key . ')'
		);
		
		/* @var CustomerGroupCondition $condition */
		$query->setParameter(
			$key,
			$condition->getCustomerGroupIds(),
			Connection::PARAM_INT_ARRAY
		);
		
		$query->andWhere('avoidCustomerGroup.articleID IS NULL');
	}
}
