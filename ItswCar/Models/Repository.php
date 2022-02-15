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
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Article\Detail;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

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
	 * @param array $options
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getModelsByManufacturerIdQueryBuilder(int $manufacturerId, array $options = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->from(Model::class, 'models')
			->join('models.cars', 'cars')
			->where('cars.manufacturerId = :manufacturerId')
			->setParameter('manufacturerId', $manufacturerId);
		
		$this->setOptions($builder, $options);
		
		return $builder;
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param array $options
	 * @return \Doctrine\ORM\Query
	 */
	public function getModelsByManufacturerIdQuery(int $manufacturerId, array $options = []): Query {
		return $this->getModelsByManufacturerIdQueryBuilder($manufacturerId, $options)->getQuery();
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param int   $modelId
	 * @param array $options
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getTypesByManufacturerIdAndModelIdQueryBuilder(int $manufacturerId, int $modelId, array $options = []): QueryBuilder {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$builder->from(Type::class, 'types')
			->join('types.cars', 'cars')
			->where('cars.manufacturerId = :manufacturerId AND cars.modelId = :modelId')
			->setParameter('manufacturerId', $manufacturerId)
			->setParameter('modelId', $modelId);
		
		$this->setOptions($builder, $options);
		
		return $builder;
	}
	
	/**
	 * @param int   $manufacturerId
	 * @param int   $modelId
	 * @param array $options
	 * @return \Doctrine\ORM\Query
	 */
	public function getTypesByManufacturerIdAndModelIdQuery(int $manufacturerId, int $modelId, array $options = []): Query {
		return $this->getTypesByManufacturerIdAndModelIdQueryBuilder($manufacturerId, $modelId, $options)->getQuery();
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
			->groupBy('cars.manufacturerId, cars.modelId, cars.typeId')
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
		
		if (isset($options['select'])) {
			$builder->select($options['select']);
		}
		
		if (isset($options['from']) && is_array($options['from'])) {
			if (isset($options['from']['class'], $options['from']['alias'])) {
				$builder->from($options['from']['class'], $options['from']['alias']);
			} else {
				$builder->from(reset($options['from']), array_key_first($options['from']));
			}
		}
		
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
		
		if (isset($options['joins']) && is_array($options['joins'])) {
			foreach($options['joins'] as $alias => $join) {
				if (!is_array($join)) {
					$builder->join($join, $alias);
				} else {
					$type = $join['type'] ?? 'inner';
					$indexBy = $join['indexBy'] ?? NULL;
					$condition = $join['condition'] ?? NULL;
					$conditionType = $join['conditionType'] ?? NULL;
					
					switch(strtolower($type)) {
						case 'left': $builder->leftJoin($join['join'], $alias, $conditionType, $condition, $indexBy); break;
						case 'inner':
						default: $builder->innerJoin($join['join'], $alias, $conditionType, $condition, $indexBy);
					}
				}
			}
		}
		
		if (isset($options['groups']) && is_array($options['groups'])) {
			foreach($options['groups'] as $groupBy) {
				$builder->addGroupBy($groupBy);
			}
		}
		
		if (isset($options['distinct'])) {
			$builder->distinct($options['distinct']);
		}
	}
	
	/**
	 * @param int $tecdocId
	 * @return array|null
	 */
	public function getCarDisplayForView(int $tecdocId): ?array {
		if (!$tecdocId) {
			return NULL;
		}
		
		try {
			$car = $this->getCarsQuery([
				'select' => 'cars',
				'conditions' => [
					'cars.tecdocId' => $tecdocId
				]
			])
				->useQueryCache(TRUE)
				->getOneOrNullResult();
			
			if (is_object($car)) {
				return [
					'description' => sprintf('%s %s %s %d PS', $car->getManufacturer()->getDisplay(), $car->getModel()->getDisplay(), $car->getType()->getDisplay(), $car->getPs()),
					'title' => sprintf('%s %s %s - %d PS - %s ', $car->getManufacturer()->getDisplay(), $car->getModel()->getDisplay(), $car->getType()->getDisplay(), $car->getPs(), $car->getBuildFrom()->format('Y'))
				];
			}
		} catch (NonUniqueResultException $nonUniqueResultException) {}
		
		return NULL;
	}
	
	/**
	 * @param int $tecdocId
	 * @return array
	 */
	public function getVariantIdsByTecdocId(int $tecdocId): array {
		$carLinks = $this->getCarLinksQuery([
				'select' => [
					'articleCarLinks.articleDetailsId'
				],
				'conditions' => [
					'articleCarLinks.tecdocId' => $tecdocId
				]
			])
			->getArrayResult();
		
		return array_column($carLinks, 'articleDetailsId');
	}
	
	/**
	 * @return array
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function getVariantIdsWithoutArticleCarLink(): array {
		$builder = Shopware()->Container()->get('dbal_connection')->createQueryBuilder();
		$result = $builder
			->select(['details.id'])
			->from('s_articles_details', 'details')
			->join('details', 's_articles', 'articles', 'details.id = articles.main_detail_id and articles.active = 1 and details.active = 1')
			->leftJoin('details', 'itsw_article_car_links', 'carLinks', 'details.id = carLinks.article_details_id and carLinks.active = 1')
			->where('carLinks.article_details_id IS NULL')
			->execute()
			->fetchAll(\PDO::FETCH_COLUMN);
		
		return array_map(static function($value) {return (int)$value;}, $result);
	}
	
	/**
	 * @return array
	 */
	public function getManufacturersForCarfinder(): array {
		$manufacturers = $this->getManufacturersQuery([
				'select' => [
					'manufacturers'
				],
				'conditions' => [
					'manufacturers.active = 1'
				],
				'orders' => [
					'manufacturers.display' => 'ASC'
				]
			])
			->getResult();
		
		foreach($manufacturers as $manufacturer) {
			$return[] = [
				'name' => $manufacturer->getName(),
				'url' => Shopware()->Container()->get('itsw.helper.seo')->getCleanedStringForUrl($manufacturer->getName()),
				'display' => $manufacturer->getDisplay(),
				'id' => $manufacturer->getId(),
				'topBrand' => $manufacturer->getTopBrand()
			];
		}
		
		return $return??[];
	}
	
	/**
	 * @param int|null $manufacturerId
	 * @return array
	 */
	public function getModelsForCarfinder(int $manufacturerId = NULL): array {
		if (!$manufacturerId) {
			throw new ParameterNotFoundException("manufacturerId");
		}
		
		$models = $this->getModelsForCarfinderQuery($manufacturerId, [
				'models.active = 1'
			])
			->getResult();
		
		foreach($models as $model) {
			$buildFrom = \DateTime::createFromFormat('Y-m-d H:i:s', $model['buildFrom'])->format('m/Y');
			$buildTo = \DateTime::createFromFormat('Y-m-d H:i:s', $model['buildTo'])->format('m/Y');
			
			$return[$model['modelDisplay']][] = [
				'typeDisplay' => $model['typeDisplay'],
				'buildFrom' => $buildFrom,
				'buildTo' => $buildTo,
				'typeId' => $model['typeId'],
				'modelId' => $model['modelId']
			];
		}
		
		return $return??[];
	}
	
	/**
	 * @param int|null $manufacturerId
	 * @param int|null $modelId
	 * @param int|null $typeId
	 * @return array
	 */
	public function getTypesForCarfinder(int $manufacturerId = NULL, int $modelId = NULL, int $typeId = NULL): array {
		if (!$manufacturerId) {
			throw new ParameterNotFoundException("manufacturerId");
		}
		if (!$modelId) {
			throw new ParameterNotFoundException("modelId");
		}
		$types = $this->getTypesForCarfinderQuery($manufacturerId, $modelId, $typeId, [
				'cars.active = 1'
			])
			->getResult();
		
		foreach($types as $type) {
			$return[] = [
				'tecdocId' => $type['tecdocId'],
				'ccm' => $type['ccm'],
				'display' => sprintf('%.1f', $type['ccm'] / 1000),
				'ps' => $type['ps'],
				'kw' => $type['kw'],
				'platform' => $type['platform'],
				'typeId' => $type['typeId']
			];
		}
		
		
		return $return??[];
	}
	
	/**
	 * @param array $options
	 * @return array
	 */
	public function getCarsForCarfinder(array $options = []): array {
		$cars = $this->getCars($options);
		
		foreach($cars as $car) {
			$codes = [];
			foreach($car->getCodes() as $kbaCodes) {
				$codes[] = [
					'hsn' => $kbaCodes->getHsn(),
					'tsn' => $kbaCodes->getTsn()
				];
			}
			$result[] = array_merge($car->toArray(), [
				'manufacturer' => $car->getManufacturer()->toArray(),
				'model' => $car->getModel()->toArray(),
				'type' => $car->getType()->toArray(),
				'platform' => $car->getPlatform()->toArray(),
				'codes' => $codes,
				'buildFrom' => $car->getBuildFrom()->format('m/Y'),
				'buildTo' => $car->getBuildTo()?$car->getBuildTo()->format('m/Y') : '---'
			]);
		}
		
		return $result??[];
	}
	
	/**
	 * @param array $options
	 * @return mixed
	 */
	public function getCars(array $options = []) {
		return $this->getCarsQuery($options)->getResult();
	}
	
	/**
	 * @param array $options
	 * @return mixed
	 */
	public function getCodes(array $options = []) {
		return $this->getCodesQuery($options)->getResult();
	}
	
	/**
	 * @param int $tecdocId
	 * @return array
	 */
	public function getIdsByTecdocId(int $tecdocId): array {
		$ids = $this->getIdsByTecdocIdQuery($tecdocId)
			->getArrayResult();
		
		if (!empty($ids)) {
			return reset($ids);
		}
		
		return [];
	}
	
	/**
	 * @param array $options
	 * @return \Doctrine\ORM\Query
	 */
	public function getDefaultQuery(array $options = []): Query {
		$builder = $this->getEntityManager()->createQueryBuilder();
		$this->setOptions($builder, $options);
		
		return $builder->getQuery();
	}
}