<?php declare(strict_types=1);
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
use Doctrine\ORM\QueryBuilder;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Article\Detail;

class Repository extends ModelRepository {
	/**
	 * @param array $attributes
	 * @param null  $sort
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getDetailsByAttributesQueryBuilder(array $attributes, $sort = null): QueryBuilder {
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
	
	/**
	 * @param int $articleDetailsId
	 * @return \Doctrine\ORM\Query
	 */
	public function getDeleteCarLinksQuery(int $articleDetailsId): Query {
		return $this->getDeleteCarLinksQueryBuilder($articleDetailsId)->getQuery();
	}
	
	/**
	 * @param int $articleDetailsId
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getDeleteCarLinksQueryBuilder(int $articleDetailsId): QueryBuilder {
		return $this->getEntityManager()->createQueryBuilder()
			->delete(ArticleCarlinks::class, 'articleCarLinks')
			->where('articleCarLinks.articleDetailsId = :articleDetailsId')
			->setParameter('articleDetailsId', $articleDetailsId);
	}
	
	/**
	 * @param array $conditions
	 * @param array $sortings
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getCarLinksQueryBuilder(array $conditions = [], array $sortings = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder()
			->select([
				'articleCarLinks'
			])
			->from(ArticleCarLinks::class, 'articleCarLinks');
		
		$parameterCount = 1;
		foreach($conditions as $condition => $value) {
			if (is_numeric($condition)) {
				$builder->andWhere($value);
			} else if (strpos($condition, ' =') !== FALSE) {
				$builder->andWhere($condition . ' ?' . $parameterCount)
					->setParameter($parameterCount++, $value);
			} else {
				$builder->andWhere($condition . ' = ?' . $parameterCount)
					->setParameter($parameterCount++, $value);
			}
		}
		
		if (!empty($sortings)) {
			foreach ($sortings as $sort => $order) {
				$builder->addOrderBy($sort, $order);
			}
		}
		
		return $builder;
	}
	
	/**
	 * @param array $conditions
	 * @param array $sortings
	 * @return \Doctrine\ORM\Query
	 */
	public function getCarLinksQuery(array $conditions = [], array $sortings = []): Query {
		return $this->getCarLinksQueryBuilder($conditions, $sortings)->getQuery();
	}
	
	/**
	 * @param array $conditions
	 * @param array $sortings
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getCarsQueryBuilder(array $conditions = [], array $sortings = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->select([
				'cars'
			])
			->from(Car::class, 'cars');
	
		$parameterCount = 1;
		foreach($conditions as $condition => $value) {
			if (is_numeric($condition)) {
				$builder->andWhere($value);
			} else if (strpos($condition, ' =') !== FALSE) {
				$builder->andWhere($condition . ' ?' . $parameterCount)
					->setParameter($parameterCount++, $value);
			} else {
				$builder->andWhere($condition . ' = ?' . $parameterCount)
					->setParameter($parameterCount++, $value);
			}
		}
	
		if (!empty($sortings)) {
			foreach ($sortings as $sort => $order) {
				$builder->addOrderBy($sort, $order);
			}
		}
	
		return $builder;
	}
	
	/**
	 * @param array $conditions
	 * @param array $sortings
	 * @return \Doctrine\ORM\Query
	 */
	public function getCarsQuery(array $conditions = [], array $sortings = []): Query {
		return $this->getCarsQueryBuilder($conditions, $sortings)->getQuery();
	}
	
	/**
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getManufacturersQueryBuilder(array $filters = [], array $sortings = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->select([
			'manufacturers'
		])
			->from(Manufacturer::class, 'manufacturers');
		
		foreach($filters as $filter) {
			$builder->andWhere($filter);
		}
		
		if (!empty($sortings)) {
			foreach ($sortings as $sort => $order) {
				$builder->addOrderBy($sort, $order);
			}
		} else {
			$builder->addOrderBy('manufacturers.display', 'ASC');
		}
		
		return $builder;
	}
	
	/**
	 * @param array $filters
	 * @return \Doctrine\ORM\Query
	 */
	public function getManufacturersQuery(array $filters = []): Query {
		return $this->getManufacturersQueryBuilder($filters)->getQuery();
	}
	
	/**
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getModelsQueryBuilder(array $filters = [], array $sortings = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->select([
			'models'
		])
			->from(Model::class, 'models');
		
		foreach($filters as $filter) {
			$builder->andWhere($filter);
		}
		
		if (!empty($sortings)) {
			foreach ($sortings as $sort => $order) {
				$builder->addOrderBy($sort, $order);
			}
		} else {
			$builder->addOrderBy('models.display', 'ASC');
		}
		
		return $builder;
	}
	
	/**
	 * @param array $filters
	 * @return \Doctrine\ORM\Query
	 */
	public function getModelsQuery(array $filters = []): Query {
		return $this->getModelsQuery($filters)->getQuery();
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getModelsByManufacturerIdQueryBuilder(int $manufacturerId, array $filters = [], array $sortings = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->select([
			'models'
		])
			->from(Model::class, 'models')
			->distinct(TRUE)
			->join('models.cars', 'cars')
			->where('cars.manufacturerId = :manufacturerId')
			->setParameter('manufacturerId', $manufacturerId);
		
		foreach($filters as $filter) {
			$builder->andWhere($filter);
		}
		
		if (!empty($sortings)) {
			foreach ($sortings as $sort => $order) {
				$builder->addOrderBy($sort, $order);
			}
		} else {
			$builder->addOrderBy('models.display', 'ASC');
		}
		
		return $builder;
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\Query
	 */
	public function getModelsByManufacturerIdQuery(int $manufacturerId, array $filters = [], array $sortings = []): Query {
		return $this->getModelsByManufacturerIdQueryBuilder($manufacturerId, $filters, $sortings)->getQuery();
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param int   $modelId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getTypesByManufacturerIdAndModelIdQueryBuilder(int $manufacturerId, int $modelId, array $filters = [], array $sortings = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->select([
			'types'
		])
			->from(Type::class, 'types')
			->distinct(TRUE)
			->join('types.cars', 'cars')
			->where('cars.manufacturerId = :manufacturerId AND cars.modelId = :modelId')
			->setParameter('manufacturerId', $manufacturerId)
			->setParameter('modelId', $modelId);
		
		foreach($filters as $filter) {
			$builder->andWhere($filter);
		}
		
		if (!empty($sortings)) {
			foreach ($sortings as $sort => $order) {
				$builder->addOrderBy($sort, $order);
			}
		} else {
			$builder->addOrderBy('types.display', 'ASC');
		}
		
		return $builder;
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param int   $modelId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\Query
	 */
	public function getTypesByManufacturerIdAndModelIdQuery(int $manufacturerId, int $modelId, array $filters = [], array $sortings = []): Query {
		return $this->getTypesByManufacturerIdAndModelIdQueryBuilder($manufacturerId, $modelId, $filters, $sortings)->getQuery();
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param int   $modelId
	 * @param int   $typeId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getCarsByManufacturerIdAndModelIdAndTypeIdQueryBuilder(int $manufacturerId, int $modelId, int $typeId, array $filters = [], array $sortings = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->select([
			'cars'
		])
			->from(Car::class, 'cars')
			->distinct(TRUE)
			->where('cars.manufacturerId = :manufacturerId AND cars.modelId = :modelId AND cars.typeId = :typeId')
			->setParameter('manufacturerId', $manufacturerId)
			->setParameter('modelId', $modelId)
			->setParameter('typeId', $typeId);
		
		foreach($filters as $filter) {
			$builder->andWhere($filter);
		}
		
		if (!empty($sortings)) {
			foreach ($sortings as $sort => $order) {
				$builder->addOrderBy($sort, $order);
			}
		} else {
			$builder->addOrderBy('cars.tecdocId', 'ASC');
		}
		
		return $builder;
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param int   $modelId
	 * @param int   $typeId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\Query
	 */
	public function getCarsByManufacturerIdAndModelIdAndTypeIdQuery(int $manufacturerId, int $modelId, int $typeId, array $filters = [], array $sortings = []): Query {
		return $this->getCarsByManufacturerIdAndModelIdAndTypeIdQueryBuilder($manufacturerId, $modelId, $typeId, $filters, $sortings)->getQuery();
	}
	
	/**
	 * @param int   $tecdocId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getIdsByTecdocIdQueryBuilder(int $tecdocId, array $filters = [], array $sortings = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->select([
			'cars.tecdocId',
			'cars.manufacturerId',
			'cars.modelId',
			'cars.typeId',
			'cars.platformId'
		])
			->from(Car::class, 'cars')
			->where('cars.tecdocId = :tecdocId')
			->setParameter('tecdocId', $tecdocId);
		
		foreach($filters as $filter) {
			$builder->andWhere($filter);
		}
		
		if (!empty($sortings)) {
			foreach ($sortings as $sort => $order) {
				$builder->addOrderBy($sort, $order);
			}
		} else {
			$builder->addOrderBy('cars.tecdocId', 'ASC');
		}
		
		return $builder;
	}
	
	/**
	 * @param int   $tecdocId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\Query
	 */
	public function getIdsByTecdocIdQuery(int $tecdocId, array $filters = [], array $sortings = []): Query {
		return $this->getIdsByTecdocIdQueryBuilder($tecdocId, $filters, $sortings)->getQuery();
	}
}