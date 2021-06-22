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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;
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
	 * @param array $options
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getCarLinksQueryBuilder(array $options = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder()
			->andWhere('articleCarLinks.active = 1')
			->from(ArticleCarLinks::class, 'articleCarLinks');
		
		$this->setOptions($builder, $options);
		
		return $builder;
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\Query
	 */
	public function getCarLinksQuery(array $options = []): Query {
		return $this->getCarLinksQueryBuilder($options)->getQuery();
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getCarsQueryBuilder(array $options = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->from(Car::class, 'cars');
		$this->setOptions($builder, $options);
		
		return $builder;
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\Query
	 */
	public function getCarsQuery(array $options = []): Query {
		return $this->getCarsQueryBuilder($options)->getQuery();
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getCodesQueryBuilder(array $options = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->from(KbaCodes::class, 'kba_codes');
		$this->setOptions($builder, $options);
		
		return $builder;
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\Query
	 */
	public function getCodesQuery(array $options = []): Query {
		return $this->getCodesQueryBuilder($options)->getQuery();
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getManufacturersQueryBuilder(array $options = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->from(Manufacturer::class, 'manufacturers');
		$this->setOptions($builder, $options);
		
		return $builder;
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\Query
	 */
	public function getManufacturersQuery(array $options = []): Query {
		return $this->getManufacturersQueryBuilder($options)->getQuery();
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getModelsQueryBuilder(array $options = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->from(Model::class, 'models');
		$this->setOptions($builder, $options);
		
		return $builder;
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\Query
	 */
	public function getModelsQuery(array $options = []): Query {
		return $this->getModelsQueryBuilder($options)->getQuery();
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
	 * @param int   $typeId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getTypesByManufacturerIdAndModelIdQueryBuilder(int $manufacturerId, int $modelId, int $typeId, array $filters = [], array $sortings = []): QueryBuilder {
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
	 * @param int   $typeId
	 * @param array $filters
	 * @param array $sortings
	 * @return \Doctrine\ORM\Query
	 */
	public function getTypesByManufacturerIdAndModelIdQuery(int $manufacturerId, int $modelId, int $typeId, array $filters = [], array $sortings = []): Query {
		return $this->getTypesByManufacturerIdAndModelIdQueryBuilder($manufacturerId, $modelId, $typeId, $filters, $sortings)->getQuery();
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
	
	/**
	 * @param int   $manufacturerId
	 * @param array $filters
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getModelsForCarfinderQueryBuilder(int $manufacturerId, array $filters = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->select([
			'models.display AS modelDisplay',
			'types.display AS typeDisplay',
			'MIN(cars.buildFrom) AS buildFrom',
			'MAX(cars.buildTo) AS buildTo',
			'types.id AS typeId',
			'models.id AS modelId'
		])
			->from(Car::class, 'cars')
			->join('cars.model', 'models')
			->join('cars.type', 'types')
			->where('cars.manufacturerId = :manufacturerId')
			->groupBy('cars.typeId')
			->orderBy('models.display')
			//->orderBy('types.display')
			//->addOrderBy('buildFrom')
			//->addOrderBy('buildTo')
			->setParameter('manufacturerId', $manufacturerId);
		
		foreach($filters as $filter) {
			$builder->andWhere($filter);
		}
		
		return $builder;
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param array $filters
	 * @return \Doctrine\ORM\Query
	 */
	public function getModelsForCarfinderQuery(int $manufacturerId, array $filters = []): Query {
		return $this->getModelsForCarfinderQueryBuilder($manufacturerId, $filters)->getQuery();
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param int   $modelId
	 * @param int   $typeId
	 * @param array $filters
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getTypesForCarfinderQueryBuilder(int $manufacturerId, int $modelId, int $typeId, array $filters = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		
		$builder->select([
			'cars.tecdocId',
			'cars.ccm',
			'cars.kw',
			'cars.ps',
			'cars.typeId',
			'platforms.display AS platform'
		])
			->from(Car::class, 'cars')
			->join('cars.platform', 'platforms')
			->where('cars.manufacturerId = :manufacturerId')
			->andWhere('cars.modelId = :modelId')
			->andWhere('cars.typeId = :typeId')
			->setParameter('manufacturerId', $manufacturerId)
			->setParameter('modelId', $modelId)
			->setParameter('typeId', $typeId);
		
		foreach($filters as $filter) {
			$builder->andWhere($filter);
		}
		
		return $builder;
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param int   $modelId
	 * @param int   $typeId
	 * @param array $filters
	 * @return \Doctrine\ORM\Query
	 */
	public function getTypesForCarfinderQuery(int $manufacturerId, int $modelId, int $typeId, array $filters = []): Query {
		return $this->getTypesForCarfinderQueryBuilder($manufacturerId, $modelId, $typeId, $filters)->getQuery();
	}
	
	/**
	 * @param       $builder
	 * @param array $options
	 */
	private function setOptions(&$builder, array $options = []): void {
		if (empty($options)) {
			return;
		}
		
		$builder->select($options['select'] ?? []);
		
		$parameterCount = 1;
		if (isset($options['conditions']) && is_array($options['conditions'])) {
			foreach($options['conditions'] as $condition => $value) {
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
		}
		
		if (isset($options['orders']) && is_array($options['orders'])) {
			foreach ($options['orders'] as $column => $order) {
				$builder->addOrderBy($column, $order);
			}
		}
	}
}