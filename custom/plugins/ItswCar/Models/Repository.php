<?php
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   11.12.2020
 * Zeit:    18:03
 * Datei:   Repository.php
 * @package ItswCar\Models
 */

namespace ItswCar\Models;

use Doctrine\ORM\Query;
use Shopware\Components\Model\ModelRepository;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Article\Detail;

class Repository extends ModelRepository {
	/**
	 * @param array $attributes
	 * @param null  $sort
	 * @return \Shopware\Components\Model\QueryBuilder
	 */
	public function getDetailsByAttributesQueryBuilder(array $attributes, $sort = null): QueryBuilder {
		/** @var QueryBuilder $builder */
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->select([
			'details',
			'attribute'
		])
			->from(Detail::class, 'details')
			->leftJoin('details.attribute', 'attribute');
		
		foreach($attributes as $name => $value) {
			$builder->andWhere('attribute.' . $name . ' = ' . $value);
		}
		
		if ($sort !== null && !empty($sort)) {
			$builder->addOrderBy($sort);
		} else {
			$builder->addOrderBy('details.id', 'ASC');
		}
		
		return $builder;
	}
	
	/**
	 * @param array $attributes
	 * @param null  $sort
	 * @return \Doctrine\ORM\Query
	 */
	public function getDetailsByAttributesQuery(array $attributes, $sort = null): Query {
		return $this->getDetailsByAttributesQueryBuilder($attributes, $sort)->getQuery();
	}
}