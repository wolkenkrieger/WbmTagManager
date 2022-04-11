<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   08.02.2021
 * Zeit:    15:51
 * Datei:   ExtendedArticle.php
 * @package ItswCar\Components\Api\Resource
 */

namespace ItswCar\Components\Api\Resource;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use ItswCar\Models\GoogleMerchantCenterQueue;
use Shopware\Components\Api\BatchInterface;
use Shopware\Components\Api\Exception as ApiException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\ORMException;
use Shopware\Components\Api\Resource\Category as CategoryResource;
use Shopware\Components\Api\Resource\Resource;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Article\Article as ProductModel;
use Shopware\Models\Article\Configurator;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Download;
use Shopware\Models\Article\Image;
use Shopware\Models\Article\Link;
use Shopware\Models\Article\Repository;
use Shopware\Models\Article\SeoCategory;
use Shopware\Models\Article\Supplier;
use Shopware\Models\Category\Category;
use Shopware\Models\Media\Media as MediaModel;
use Shopware\Models\Price\Group;
use Shopware\Models\Property\Group as PropertyGroup;
use Shopware\Models\Property\Option;
use Shopware\Models\Property\Value;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Tax\Tax;
use ItswCar\Models\ArticleCarLinks as CarLinks;

use ItswCar\Traits\LoggingTrait;


class ExtendedArticle extends Resource implements BatchInterface {
	use LoggingTrait;
	
	/**
	 * @var \Shopware_Components_Translation
	 */
	private $translationComponent;
	private $productHelper;
	
	public function __construct(\Shopware_Components_Translation $translationComponent = null) {
		$this->translationComponent = $translationComponent ?: Shopware()->Container()->get('translation');
		$this->productHelper = Shopware()->Container()->get('itsw.helper.product');
	}
	
	/**
	 * @return \Shopware\Models\Article\Repository
	 */
	public function getRepository(): Repository {
		return $this->getManager()->getRepository(ProductModel::class);
	}
	
	/**
	 * @return \Shopware\Models\Article\Repository
	 */
	public function getDetailRepository(): Repository {
		return $this->getManager()->getRepository(Detail::class);
	}
	
	/**
	 * @return \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\ItswCar\Models\Repository
	 */
	public function getCarLinkRepository() {
		return $this->getManager()->getRepository(CarLinks::class);
	}
	
	/**
	 * @param $number
	 * @return int
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 */
	public function getIdFromNumber($number): int {
		if (empty($number)) {
			throw new ApiException\ParameterMissingException('id');
		}
		
		/** @var Detail|null $productVariant */
		$productVariant = $this->getDetailRepository()->findOneBy(['number' => $number]);
		
		if (!$productVariant) {
			throw new ApiException\NotFoundException(sprintf('Product by number "%s" not found', $number));
		}
		
		return $productVariant
			->getArticle()
			->getId();
	}
	
	/**
	 * @param       $number
	 * @param array $options
	 * @return array
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 * @throws \Shopware\Components\Api\Exception\PrivilegeException
	 */
	public function getOneByNumber($number, array $options = []): array {
		$id = $this->getIdFromNumber($number);
		
		return $this->getOne($id, $options);
	}
	
	/**
	 * @param       $id
	 * @param array $options
	 * @return array
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 * @throws \Shopware\Components\Api\Exception\PrivilegeException
	 */
	public function getOne($id, array $options = []): array {
		$this->checkPrivilege('read');
		
		if (empty($id)) {
			throw new ApiException\ParameterMissingException('id');
		}
		
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select([
			'article',
			'mainDetail',
			'mainDetailPrices',
			'tax',
			'propertyValues',
			'configuratorOptions',
			'supplier',
			'priceCustomGroup',
			'mainDetailAttribute',
			'propertyGroup',
			'customerGroups'
		])
			->from(ProductModel::class, 'article')
			->leftJoin('article.mainDetail', 'mainDetail')
			->leftJoin('mainDetail.prices', 'mainDetailPrices')
			->leftJoin('mainDetailPrices.customerGroup', 'priceCustomGroup')
			->leftJoin('article.tax', 'tax')
			->leftJoin('article.propertyValues', 'propertyValues')
			->leftJoin('article.supplier', 'supplier')
			->leftJoin('mainDetail.attribute', 'mainDetailAttribute')
			->leftJoin('mainDetail.configuratorOptions', 'configuratorOptions')
			->leftJoin('article.propertyGroup', 'propertyGroup')
			->leftJoin('article.customerGroups', 'customerGroups')
			->where('article.id = ?1')
			->setParameter(1, $id);
		
		/** @var array $product */
		$product = $builder->getQuery()->getOneOrNullResult($this->getResultMode());
		
		if (!$product) {
			throw new ApiException\NotFoundException(sprintf('Product by id "%d" not found', $id));
		}
		
		if ($this->getResultMode() === self::HYDRATE_ARRAY) {
			$product['images'] = $this->getArticleImages((int)$id);
			$product['configuratorSet'] = $this->getArticleConfiguratorSet($id);
			$product['links'] = $this->getArticleLinks($id);
			$product['downloads'] = $this->getArticleDownloads($id);
			$product['categories'] = $this->getArticleCategories($id);
			$product['similar'] = $this->getArticleSimilar($id);
			$product['related'] = $this->getArticleRelated($id);
			$product['details'] = $this->getArticleVariants($id);
			$product['seoCategories'] = $this->getArticleSeoCategories($id);
			$product['carLinks'] = $this->getArticleCarLinks($product['mainDetailId']);
			
			if (isset($options['considerTaxInput']) && $options['considerTaxInput']) {
				$product['mainDetail']['prices'] = $this->getTaxPrices(
					$product['mainDetail']['prices'],
					$product['tax']['tax']
				);
				
				foreach ($product['details'] as &$detail) {
					$detail['prices'] = $this->getTaxPrices(
						$detail['prices'],
						$product['tax']['tax']
					);
				}
				unset($detail);
			}
			
			$query = $this->getManager()->createQuery('SELECT shop FROM Shopware\Models\Shop\Shop as shop');
			
			foreach ($query->getArrayResult() as $shop) {
				$translation = $this->translationComponent->read($shop['id'], 'article', $id);
				if (!empty($translation)) {
					$translation['shopId'] = $shop['id'];
					$product['translations'][$shop['id']] = $translation;
				}
			}
			
			if (isset($options['language']) && !empty($options['language'])) {
				/** @var Shop $shop */
				$shop = $this->findEntityByConditions(Shop::class, [
					['id' => $options['language']],
					['shop' => $options['language']],
				]);
				
				$product = $this->translateArticle(
					$product,
					$shop
				);
			}
			
			$this->filterBadWords($product);
		}
		
		return $product;
	}
	
	/**
	 * Helper function which calculates formats the variant prices for each customer group.
	 * If the customer group configured "taxInput" as true, the price will be formatted as gross.
	 * @param array $prices  Array of the variant prices
	 * @param float $taxRate Float value of the product tax (example: 19.00)
	 * @return array
	 */
	public function getTaxPrices(array $prices, float $taxRate): array {
		foreach ($prices as &$price) {
			$price['net'] = $price['price'];
			if ($price['customerGroup'] && $price['customerGroup']['taxInput']) {
				$price['price'] *= (($taxRate + 100) / 100);
			}
		}
		
		return $prices;
	}
	
	/**
	 * @param int   $offset
	 * @param int   $limit
	 * @param array $criteria
	 * @param array $orderBy
	 * @param array $options
	 * @return array
	 * @throws \Shopware\Components\Api\Exception\PrivilegeException
	 * @throws \Exception
	 */
	public function getList(int $offset = 0, int $limit = 25, array $criteria = [], array $orderBy = [], array $options = []): array {
		$this->checkPrivilege('read');
		
		$builder = $this->getRepository()->createQueryBuilder('article')
			->addSelect(['mainDetail', 'attribute', 'mainDetailPrices'])
			->leftJoin('article.mainDetail', 'mainDetail')
			->leftJoin('mainDetail.attribute', 'attribute')
			->leftJoin('mainDetail.prices', 'mainDetailPrices')
			->addFilter($criteria)
			->addOrderBy($orderBy)
			->setFirstResult($offset)
			->setMaxResults($limit);
		
		$query = $builder->getQuery();
		
		$query->setHydrationMode($this->getResultMode());
		
		$paginator = $this->getManager()->createPaginator($query);
		
		$totalResult = $paginator->count();
		
		$products = $paginator->getIterator()->getArrayCopy();
		
		if (isset($options['language'])
			&& !empty($options['language'])
			&& $this->getResultMode() === self::HYDRATE_ARRAY) {
			/** @var Shop $shop */
			$shop = $this->findEntityByConditions(Shop::class, [
				['id' => $options['language']],
			]);
			
			foreach ($products as &$product) {
				$product = $this->translateArticle(
					$product,
					$shop
				);
				
				$this->filterBadWords($product);
			}
		}
		
		return ['data' => $products, 'total' => $totalResult];
	}
	
	/**
	 * @param array $params
	 * @return \Shopware\Models\Article\Article
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\ORM\TransactionRequiredException
	 * @throws \Shopware\Components\Api\Exception\CustomValidationException
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\OrmException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 * @throws \Shopware\Components\Api\Exception\PrivilegeException
	 * @throws \Shopware\Components\Api\Exception\ValidationException
	 */
	public function create(array $params): ProductModel {
		$this->checkPrivilege('create');
		
		$product = new ProductModel();
		
		$translations = [];
		if (!empty($params['translations'])) {
			$translations = $params['translations'];
			unset($params['translations']);
		}
		
		$carLinks = [];
		if (!empty($params['carLinks'])) {
			$carLinks = $params['carLinks'];
			unset($params['carLinks']);
		}
		
		$googleContentApi = FALSE;
		if (isset($params['google_content_api']) && $params['google_content_api'] === TRUE) {
			$googleContentApi = TRUE;
			unset($params['google_content_api']);
		}
		
		$params = $this->prepareAssociatedData($params, $product);
		
		$this->filterBadWords($params);
		
		$product->fromArray($params);
		
		$violations = $this->getManager()->validate($product);
		if ($violations->count() > 0) {
			throw new ApiException\ValidationException($violations);
		}
		
		$this->getManager()->persist($product);
		$this->flush();
		
		if (!empty($translations)) {
			$this->writeTranslations($product->getId(), $translations);
		}
		
		if (!empty($carLinks)) {
			$this->writeCarLinks($carLinks, $product);
		}
		
		//$this->productHelper->setProductFakePrice($product);
		
		if ($googleContentApi) {
			$queueId = $this->addToGoogleMerchantCenterQueue($product);
		}
		
		return $product;
	}
	
	/**
	 * @param string $number
	 * @param array  $params
	 * @return \Shopware\Models\Article\Article|null
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\ORM\TransactionRequiredException
	 * @throws \Shopware\Components\Api\Exception\CustomValidationException
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\OrmException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 * @throws \Shopware\Components\Api\Exception\PrivilegeException
	 * @throws \Shopware\Components\Api\Exception\ValidationException
	 */
	public function updateByNumber(string $number, array $params): ?ProductModel {
		$id = $this->getIdFromNumber($number);
		
		return $this->update($id, $params);
	}
	
	/**
	 * @param       $id
	 * @param array $params
	 * @return \Shopware\Models\Article\Article|null
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\ORM\TransactionRequiredException
	 * @throws \Shopware\Components\Api\Exception\CustomValidationException
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\OrmException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 * @throws \Shopware\Components\Api\Exception\PrivilegeException
	 * @throws \Shopware\Components\Api\Exception\ValidationException
	 */
	public function update($id, array $params): ?ProductModel {
		$this->checkPrivilege('update');
		
		if (empty($id)) {
			throw new ApiException\ParameterMissingException('id');
		}
		
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select([
			'product',
			'mainVariant',
			'mainVariantPrices',
			'mainVariantAttribute',
			'tax',
			'supplier',
		])
			->from(ProductModel::class, 'product')
			->leftJoin('product.mainDetail', 'mainVariant')
			->leftJoin('mainVariant.prices', 'mainVariantPrices')
			->leftJoin('product.tax', 'tax')
			->leftJoin('product.supplier', 'supplier')
			->leftJoin('mainVariant.attribute', 'mainVariantAttribute')
			->where('product.id = ?1')
			->setParameter(1, $id);
		
		/** @var ProductModel|null $product */
		$product = $builder->getQuery()->getOneOrNullResult(self::HYDRATE_OBJECT);
		
		if (!$product) {
			throw new ApiException\NotFoundException(sprintf('Product by id "%d" not found', $id));
		}
		
		$translations = [];
		if (!empty($params['translations'])) {
			$translations = $params['translations'];
			unset($params['translations']);
		}
		
		$carLinks = [];
		if (!empty($params['carLinks'])) {
			$carLinks = $params['carLinks'];
			unset($params['carLinks']);
		}
		
		$googleContentApi = FALSE;
		if (isset($params['google_content_api']) && $params['google_content_api'] === TRUE) {
			$googleContentApi = TRUE;
			unset($params['google_content_api']);
		}
		
		$params = $this->prepareAssociatedData($params, $product);
		
		$this->filterBadWords($params);
		
		$product->fromArray($params);
		$violations = $this->getManager()->validate($product);
		if ($violations->count() > 0) {
			throw new ApiException\ValidationException($violations);
		}
		
		$this->flush();
		
		if (!empty($translations)) {
			$this->writeTranslations($product->getId(), $translations);
		}
		
		if (!empty($carLinks)) {
			try {
				$this->writeCarLinks($carLinks, $product);
			} catch (OptimisticLockException | ORMException $e) {
			}
		}
		
		//$this->productHelper->setProductFakePrice($product);
		
		if ($googleContentApi || $this->isInQueue($product)) {
			$queueId = $this->addToGoogleMerchantCenterQueue($product, 'update');
		}
		
		return $product;
	}
	
	/**
	 * @param $number
	 * @return \Shopware\Models\Article\Article|null
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\OrmException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 * @throws \Shopware\Components\Api\Exception\PrivilegeException
	 */
	public function deleteByNumber($number): ?ProductModel {
		$this->checkPrivilege('delete');
		
		if (empty($number)) {
			throw new ApiException\ParameterMissingException('number');
		}
		
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select([
			'article',
			'mainDetail'
		])
			->from(ProductModel::class, 'article')
			->leftJoin('article.mainDetail', 'mainDetail')
			->where('mainDetail.number = :number')
			->setParameter('number', $number);
		
		$product = $builder->getQuery()->getOneOrNullResult();
		
		if (!$product instanceof ProductModel) {
			throw new ApiException\NotFoundException(sprintf('Product by "number" %s not found', $number));
		}
		
		return $this->delete($product->getId());
	}
	
	/**
	 * @param $id
	 * @return \Shopware\Models\Article\Article|null
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\OrmException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 * @throws \Shopware\Components\Api\Exception\PrivilegeException
	 */
	public function delete($id): ?ProductModel {
		$this->checkPrivilege('delete');
		
		if (empty($id)) {
			throw new ApiException\ParameterMissingException('id');
		}
		
		/** @var ProductModel|null $product */
		$product = $this->getRepository()->find($id);
		
		if (!$product) {
			throw new ApiException\NotFoundException(sprintf('Product by "id" %d not found', $id));
		}
		
		// Delete associated data
		$query = $this->getRepository()->getRemovePricesQuery($product->getId());
		$query->execute();
		$query = $this->getRepository()->getRemoveESDQuery($product->getId());
		$query->execute();
		$query = $this->getRepository()->getRemoveArticleTranslationsQuery($product->getId());
		$query->execute();
		
		$sql = 'DELETE FROM s_articles_translations WHERE articleID = ?';
		$this->getManager()->getConnection()->executeQuery($sql, [$product->getId()]);
		
		$this->removeArticleDetails($product);
		
		$query = $this->getCarLinkRepository()->getDeleteCarLinksQuery($product->getMainDetail()->getId());
		$query->execute();
		
		$this->getManager()->remove($product);
		$this->flush();
		
		$this->addToGoogleMerchantCenterQueue($product, 'delete');
		
		return $product;
	}
	
	/**
	 * Helper function which converts the passed data for the main variant of the product.
	 *
	 * @return array
	 */
	public function prepareMainDetail(array $data, ProductModel $article): array {
		$detail = $article->getMainDetail();
		if (!$detail) {
			$detail = new Detail();
			$detail->setKind(1);
			$detail->setArticle($article);
			$article->setMainDetail($detail);
		}
		
		if (!$data['mainDetail']) {
			$data['mainDetail'] = [];
		}
		
		$data['mainDetail'] = $this->getVariantResource()->prepareMainVariantData($data['mainDetail'], $article, $detail);
		
		return $data;
	}
	
	/**
	 * Short method to completely generate all images from an product, main images and variant images
	 * @param bool $force Force all images to be regenerated
	 * @throws \Exception
	 * @see \Shopware\Components\Api\Resource\Article::generateVariantImages()
	 * @see \Shopware\Components\Api\Resource\Article::generateMainThumbnails()
	 */
	public function generateImages(ProductModel $article, bool $force = false): void {
		$this->generateMainThumbnails($article, $force);
		$this->generateVariantImages($article, $force);
	}
	
	/**
	 * @param \Shopware\Models\Article\Article $article
	 * @param false                            $force
	 * @throws \Exception
	 */
	public function generateMainThumbnails(ProductModel $article, bool $force = false): void {
		/** @var \Shopware\Components\Thumbnail\Manager $generator */
		$generator = $this->getContainer()->get('thumbnail_manager');
		
		/** @var \Shopware\Bundle\MediaBundle\MediaServiceInterface $mediaService */
		$mediaService = Shopware()->Container()->get('shopware_media.media_service');
		
		/** @var Image $image */
		foreach ($article->getImages() as $image) {
			$media = $image->getMedia();
			
			$projectDir = $this->getContainer()->getParameter('shopware.app.rootdir');
			
			if (!\is_string($projectDir)) {
				throw new \RuntimeException('Parameter shopware.app.rootDir has to be an string');
			}
			
			if (!$force && $mediaService->has($projectDir . $media->getPath())) {
				continue;
			}
			
			foreach ($media->getThumbnailFilePaths() as $size => $path) {
				$generator->createMediaThumbnail($media, [$size], true);
			}
		}
	}
	
	/**
	 * This method generates all variant image entities for a given product model instance.
	 * The method expects that the variants and the mapping of the product images already exist.
	 * @param bool $force Force variant image regeneration
	 * @throws \Exception
	 */
	public function generateVariantImages(ProductModel $article, bool $force = false): void {
		$builder = $this->getArticleImageMappingsQuery($article->getId());
		
		/** @var Image\Mapping $mapping */
		foreach ($builder->getQuery()->getResult() as $mapping) {
			$builder = $this->getArticleVariantQuery($article->getId());
			
			/** @var Image\Rule $rule */
			foreach ($mapping->getRules() as $rule) {
				$option = $rule->getOption();
				$alias = 'option' . $option->getId();
				$builder->innerJoin('variants.configuratorOptions', $alias, 'WITH', $alias . '.id = :' . $alias)
					->setParameter($alias, $option->getId());
			}
			
			/** @var Detail $variant */
			foreach ($builder->getQuery()->getResult() as $variant) {
				if (!$force && $this->getCollectionElementByProperty(
						$variant->getImages(),
						'parent',
						$mapping->getImage()
					)) {
					continue;
				}
				
				$image = $this->getVariantResource()->createVariantImage(
					$mapping->getImage(),
					$variant
				);
				
				$variant->getImages()->add($image);
			}
		}
		$this->getManager()->flush();
	}
	
	/**
	 * @param \Shopware\Models\Article\Article $article
	 * @param \Shopware\Models\Media\Media     $media
	 * @return \Shopware\Models\Article\Image
	 * @throws \Doctrine\ORM\ORMException
	 */
	public function createNewArticleImage(ProductModel $article, MediaModel $media): Image {
		$image = new Image();
		$image = $this->updateArticleImageWithMedia(
			$article,
			$image,
			$media
		);
		$this->getManager()->persist($image);
		$article->getImages()->add($image);
		
		return $image;
	}
	
	/**
	 * @param \Shopware\Models\Article\Article $article
	 * @param \Shopware\Models\Article\Image   $image
	 * @param \Shopware\Models\Media\Media     $media
	 * @return \Shopware\Models\Article\Image
	 */
	public function updateArticleImageWithMedia(ProductModel $article, Image $image, MediaModel $media): Image {
		$image->setMain(2);
		$image->setMedia($media);
		$image->setArticle($article);
		$image->setPath($media->getName());
		$image->setExtension($media->getExtension());
		$image->setDescription($media->getDescription());
		
		return $image;
	}
	
	/**
	 * @param $articleId
	 * @param $translations
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\ORM\TransactionRequiredException
	 * @throws \Shopware\Components\Api\Exception\CustomValidationException
	 */
	public function writeTranslations($articleId, $translations): void {
		$whitelist = $this->getAttributeProperties();
		array_push(
			$whitelist,
			'metaTitle',
			'name',
			'description',
			'descriptionLong',
			'shippingTime',
			'keywords',
			'packUnit'
		);
		
		foreach ($translations as $translation) {
			$shop = $this->getManager()->find(Shop::class, $translation['shopId']);
			if (!$shop) {
				throw new ApiException\CustomValidationException(sprintf('Shop by id "%s" not found', $translation['shopId']));
			}
			
			// Backward compatibility for attribute translations
			foreach ($translation as $key => $value) {
				$attrKey = '__attribute_' . $key;
				if (in_array($attrKey, $whitelist) && !isset($translation[$attrKey])) {
					$translation[$attrKey] = $value;
				}
			}
			
			$data = array_intersect_key($translation, array_flip($whitelist));
			$this->translationComponent->write($shop->getId(), 'article', $articleId, $data);
		}
	}
	
	/**
	 * Returns the primary ID of any data set.
	 *
	 * {@inheritdoc}
	 */
	public function getIdByData($data)	{
		$id = NULL;
		
		if (isset($data['id'])) {
			$id = $data['id'];
		} elseif (isset($data['mainDetail']['number'])) {
			try {
				$id = $this->getIdFromNumber($data['mainDetail']['number']);
			} catch (\Exception $e) {
				return FALSE;
			}
		}
		
		if (!$id) {
			return FALSE;
		}
		
		try {
			$model = $this->getManager()->find(ProductModel::class, $id);
		} catch (OptimisticLockException | TransactionRequiredException | ORMException $e) {
			return FALSE;
		}
		
		if ($model) {
			return $id;
		}
		
		return FALSE;
	}
	
	/**
	 * @return mixed|object|\Shopware\Components\Api\Resource\Variant|null
	 */
	protected function getVariantResource()	{
		return $this->getContainer()->get('shopware.api.variant');
	}
	
	/**
	 * @return mixed|object|\Shopware\Components\Api\Resource\Translation|null
	 */
	protected function getTranslationResource()	{
		return $this->getContainer()->get('shopware.api.translation');
	}
	
	/**
	 * @return mixed|object|null
	 */
	protected function getMediaResource() {
		return $this->getContainer()->get('shopware.api.media');
	}
	
	/**
	 * @param $articleId
	 * @return mixed
	 * @throws \Exception
	 */
	protected function getArticleConfiguratorSet($articleId) {
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['configuratorSet', 'groups'])
			->from(Configurator\Set::class, 'configuratorSet')
			->innerJoin('configuratorSet.articles', 'article')
			->leftJoin('configuratorSet.groups', 'groups')
			->addOrderBy('groups.position', 'ASC')
			->where('article.id = :articleId')
			->setParameter(':articleId', $articleId);
		
		return $this->getSingleResult($builder);
	}
	
	/**
	 * @param int $articleId
	 * @return array
	 * @throws \Exception
	 */
	protected function getArticleImages(int $articleId): array	{
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['images'])
			->from(Image::class, 'images')
			->innerJoin('images.article', 'article')
			->where('article.id = :articleId')
			->orderBy('images.position', 'ASC')
			->andWhere('images.parentId IS NULL')
			->setParameter('articleId', $articleId);
		
		return $this->getFullResult($builder);
	}
	
	/**
	 * @param $articleId
	 * @return array
	 * @throws \Exception
	 */
	protected function getArticleDownloads($articleId): array {
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['downloads'])
			->from(Download::class, 'downloads')
			->innerJoin('downloads.article', 'article')
			->where('article.id = :articleId')
			->setParameter('articleId', $articleId);
		
		return $this->getFullResult($builder);
	}
	
	/**
	 * @param $articleId
	 * @return array
	 * @throws \Exception
	 * @throws \Exception
	 */
	protected function getArticleLinks($articleId): array {
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['links'])
			->from(Link::class, 'links')
			->innerJoin('links.article', 'article')
			->where('article.id = :articleId')
			->setParameter('articleId', $articleId);
		
		return $this->getFullResult($builder);
	}
	
	/**
	 * Helper function which selects all categories of the passed product id.
	 * This function returns only the directly assigned categories.
	 * To prevent a big data, this function selects only the category name and id.
	 * @param int $articleId
	 * @return array
	 * @throws \Exception
	 */
	protected function getArticleCategories($articleId): array {
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['categories.id', 'categories.name', 'attribute.ebayCategoryId'])
			->from(Category::class, 'categories')
			->innerJoin('categories.articles', 'articles')
			->innerJoin('categories.attribute', 'attribute')
			->where('articles.id = :articleId')
			->setParameter('articleId', $articleId);
		
		return $this->getFullResult($builder);
	}
	
	/**
	 * Helper function which selects all similar products of the passed product id.
	 *
	 * @param int $articleId
	 */
	protected function getArticleSimilar($articleId)
	{
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['article', 'PARTIAL similar.{id, name}'])
			->from(ProductModel::class, 'article')
			->innerJoin('article.similar', 'similar')
			->where('article.id = :articleId')
			->setParameter('articleId', $articleId);
		
		$product = $this->getSingleResult($builder);
		
		return $product['similar'];
	}
	
	/**
	 * Helper function which selects all accessory products of the passed product id.
	 *
	 * @param int $articleId
	 */
	protected function getArticleRelated($articleId)
	{
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['article', 'PARTIAL related.{id, name}'])
			->from(ProductModel::class, 'article')
			->innerJoin('article.related', 'related')
			->where('article.id = :articleId')
			->setParameter('articleId', $articleId);
		
		$product = $this->getSingleResult($builder);
		
		return $product['related'];
	}
	
	/**
	 * Returns the configured product seo categories.
	 * This categories are used for the seo url generation.
	 *
	 * @param int $articleId
	 *
	 * @return array
	 */
	protected function getArticleSeoCategories($articleId)
	{
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['seoCategories', 'category'])
			->from(SeoCategory::class, 'seoCategories')
			->innerJoin('seoCategories.category', 'category')
			->where('seoCategories.articleId = :articleId')
			->setParameter('articleId', $articleId);
		
		return $builder->getQuery()->getArrayResult();
	}
	
	/**
	 * Helper function which loads all non main variants of
	 * the passed product id.
	 * Additionally the function selects the variant prices
	 * and configurator options for each variant.
	 * @param int $articleId
	 * @return array
	 */
	protected function getArticleVariants($articleId): array {
		$builder = $this->getRepository()->getVariantDetailQuery();
		$builder->andWhere('article.id = :articleId')
			->andWhere('variants.kind = 2')
			->setParameter('articleId', $articleId);
		
		return $this->getFullResult($builder);
	}
	
	/**
	 * Helper function wich loads all linked cars of the passed
	 * main variant id.
	 * @param int $articleDetailsId
	 * @return array
	 * @throws \Exception
	 */
	protected function getArticleCarLinks(int $articleDetailsId): array {
		$builder = $this->getCarLinkRepository()->getCarLinksQueryBuilder([
			'select' => [
				'articleCarLinks'
			],
			'conditions' => [
				'articleCarLinks.articleDetailsId' => $articleDetailsId
			]
		]);
		
		return $this->getFullResult($builder);
	}
	
	/**
	 * Helper function to remove product details for a given product
	 *
	 * @param ProductModel $article
	 */
	protected function removeArticleDetails($article)
	{
		$sql = 'SELECT id FROM s_articles_details WHERE articleID = ? AND kind != 1';
		
		foreach (Shopware()->Db()->fetchAll($sql, [$article->getId()]) as $detail) {
			$query = $this->getRepository()->getRemoveAttributesQuery($detail['id']);
			$query->execute();
			
			$query = $this->getRepository()->getRemoveImageQuery($detail['id']);
			$query->execute();
			
			$sql = 'DELETE FROM s_article_configurator_option_relations WHERE article_id = ?';
			$this->getManager()->getConnection()->executeQuery($sql, [$detail['id']]);
			
			$sql = 'DELETE FROM s_articles_prices WHERE articledetailsID = ?';
			$this->getManager()->getConnection()->executeQuery($sql, [$detail['id']]);
			
			$query = $this->getRepository()->getRemoveVariantTranslationsQuery($detail['id']);
			$query->execute();
			
			$query = $this->getRepository()->getRemoveDetailQuery($detail['id']);
			$query->execute();
		}
	}
	
	/**
	 * @param                                  $data
	 * @param \Shopware\Models\Article\Article $article
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\ORM\TransactionRequiredException
	 * @throws \Shopware\Components\Api\Exception\CustomValidationException
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 * @throws \Shopware\Components\Api\Exception\ValidationException
	 */
	protected function prepareAssociatedData($data, ProductModel $article): array {
		$data = $this->prepareArticleAssociatedData($data);
		$data = $this->prepareCategoryAssociatedData($data, $article);
		$data = $this->prepareSeoCategoryAssociatedData($data, $article);
		
		$data = $this->prepareRelatedAssociatedData($data, $article);
		$data = $this->prepareSimilarAssociatedData($data, $article);
		$data = $this->prepareAvoidCustomerGroups($data, $article);
		$data = $this->preparePropertyValuesData($data, $article);
		$data = $this->prepareDownloadsAssociatedData($data, $article);
		$data = $this->prepareConfiguratorSet($data, $article);
		
		// Need to set the tax data directly for following price calculations which use the tax object of the article
		if (isset($data['tax'])) {
			$article->setTax($data['tax']);
		}
		
		if (isset($data['configuratorSet'])) {
			$article->setConfiguratorSet($data['configuratorSet']);
		}
		
		$data = $this->prepareImageAssociatedData($data, $article);
		$data = $this->prepareAttributeAssociatedData($data);
		
		// The mainDetail gets its initial value for lastStock from $article, so this has to be set beforehand
		if (isset($data['lastStock'])) {
			$article->setLastStock((bool) $data['lastStock']);
		}
		
		$data = $this->prepareMainDetail($data, $article);
		$data = $this->prepareVariants($data, $article);
		
		unset($data['images']);
		
		return $data;
	}
	
	/**
	 * @param                                  $data
	 * @param \Shopware\Models\Article\Article $article
	 * @return mixed
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Shopware\Components\Api\Exception\NotFoundException
	 * @throws \Shopware\Components\Api\Exception\ParameterMissingException
	 * @throws \Shopware\Components\Api\Exception\ValidationException
	 */
	protected function prepareVariants($data, ProductModel $article)
	{
		unset($data['details']);
		
		if (!isset($data['variants'])) {
			return $data;
		}
		
		$setFirstVariantMain = false;
		// Delete old main, if it has no configurator options
		// and if non of the following variants has the mainDetail's number
		$oldMainDetail = $article->getMainDetail();
		
		if ($oldMainDetail) {
			$mainDetailGetsConfigurator = false;
			foreach ($data['variants'] as $variantData) {
				if (isset($variantData['configuratorOptions']) && is_array($variantData['configuratorOptions'])) {
					$mainDetailGetsConfigurator = true;
				}
			}
			
			if (!$mainDetailGetsConfigurator && count($oldMainDetail->getConfiguratorOptions()) === 0) {
				$this->getManager()->remove($oldMainDetail);
				$setFirstVariantMain = true;
			}
		}
		
		// If the mainDetail was deleted, set the first variant as mainDetail.
		// If another variant has set isMain to true, this variant will become a usual variant again
		if ($setFirstVariantMain) {
			$data['variants'][0]['isMain'] = true;
		}
		
		$variants = [];
		
		/*
		if (isset($data['__options_variants']) && $data['__options_variants']['replace']) {
			$this->removeArticleDetails($article);
		}
		*/
		
		$this->removeArticleDetails($article);
		
		foreach ($data['variants'] as $variantData) {
			if (isset($variantData['id'])) {
				$variant = $this->getVariantResource()->internalUpdate(
					$variantData['id'],
					$variantData,
					$article
				);
			} else {
				$variant = null;
				
				// The number property can be set for two reasons.
				// 1. Use the number as identifier to update an existing variant
				// 2. Use this number for the new variant
				if (isset($variantData['number'])) {
					$variant = $this->getManager()->getRepository(Detail::class)->findOneBy([
						'number' => $variantData['number'],
						'articleId' => $article->getId(),
					]);
				}
				
				// If the variant was found over the number, update the existing
				if ($variant) {
					$variant = $this->getVariantResource()->internalUpdate(
						$variant->getId(),
						$variantData,
						$article
					);
				} else {
					// Otherwise the number passed to use as order number for the new variant
					$variant = $this->getVariantResource()->internalCreate($variantData, $article);
				}
			}
			
			if ($variantData['isMain'] || $variantData['standard']) {
				$newMain = $variant;
				$newMain->setKind(1);
				$oldMainId = $article->getMainDetail()->getId();
				
				// Check for old main articles:
				// If old main article has configurator options, use it as a usual variant
				// if the old main article does not have any configurator options, delete it
				if (isset($data['mainDetail'])) {
					$oldMain = $data['mainDetail'];
					
					if ($oldMain instanceof Detail) {
						$oldMain->setKind(2);
						if ($oldMain->getNumber() && $oldMain->getConfiguratorOptions()) {
							$variant = $oldMain;
						} else {
							$this->getManager()->remove($oldMain);
						}
					} else {
						$oldMain['kind'] = 2;
						if (!empty($oldMain['number']) && !empty($oldMain['configuratorOptions'])) {
							$variant = $oldMain;
						} elseif (!empty($oldMain['number'])) {
							$oldMain = $this->getDetailRepository()->findOneBy(['number' => $oldMain['number']]);
							$oldMainConfiguratorOptions = $oldMain ? $oldMain->getConfiguratorOptions()->toArray() : null;
							if ($oldMain && empty($oldMainConfiguratorOptions)) {
								$this->getManager()->remove($oldMain);
							}
						}
					}
				}
				
				$data['mainDetail'] = $newMain;
			}
			
			$variants[] = $variant;
		}
		
		// If the main variant was changed,
		if (isset($oldMainId, $newMain) && (int) $oldMainId !== (int) $newMain->getId()) {
			$oldMainVariantProcessed = false;
			
			foreach ($variants as &$processedVariant) {
				if ((int) $processedVariant->getId() === (int) $oldMainId) {
					$processedVariant->setKind(2);
					$oldMainVariantProcessed = true;
					break;
				}
			}
			
			unset($processedVariant);
			
			if (!$oldMainVariantProcessed) {
				$oldMain = $this->getDetailRepository()->find($oldMainId);
				if ($oldMain) {
					$oldMain->setKind(2);
					$variants[] = $oldMain;
				}
			}
		}
		
		$data['details'] = $variants;
		unset($data['variants']);
		
		return $data;
	}
	
	/**
	 * @param array $data
	 *
	 * @throws ApiException\CustomValidationException
	 * @throws \Exception
	 *
	 * @return array
	 */
	protected function prepareConfiguratorSet($data, ProductModel $article)
	{
		if (!isset($data['configuratorSet'])) {
			return $data;
		}
		
		$configuratorSet = $article->getConfiguratorSet();
		if (!$configuratorSet) {
			$configuratorSet = new Configurator\Set();
			if (isset($data['mainDetail']['number'])) {
				$number = $data['mainDetail']['number'];
			} else {
				$number = $article->getMainDetail()->getNumber();
			}
			
			$configuratorSet->setName('Set-' . $number);
			$configuratorSet->setPublic(false);
		}
		
		if (isset($data['configuratorSet']['type'])) {
			$configuratorSet->setType($data['configuratorSet']['type']);
		}
		
		if (isset($data['configuratorSet']['name'])) {
			$configuratorSet->setName($data['configuratorSet']['name']);
		}
		
		$allOptions = [];
		$allGroups = [];
		
		$groupPosition = 0;
		
		foreach ($data['configuratorSet']['groups'] as $groupData) {
			$group = null;
			if (isset($groupData['id'])) {
				/** @var Configurator\Group|null $group */
				$group = $this->getManager()->getRepository(Configurator\Group::class)->find($groupData['id']);
				if (!$group) {
					throw new ApiException\CustomValidationException(sprintf('ConfiguratorGroup by id "%s" not found', $groupData['id']));
				}
			} elseif (isset($groupData['name'])) {
				/** @var Configurator\Group|null $group */
				$group = $this->getManager()->getRepository(Configurator\Group::class)->findOneBy(['name' => $groupData['name']]);
				
				if (!$group) {
					$group = new Configurator\Group();
					$group->setPosition($groupPosition);
				}
			} else {
				throw new ApiException\CustomValidationException('At least the groupname is required');
			}
			
			$groupOptions = $group->getOptions();
			$optionPosition = 0;
			foreach ($groupData['options'] as $optionData) {
				$option = null;
				if ($group->getId() > 0) {
					if (isset($optionData['id'])) {
						/** @var Configurator\Option|null $option */
						$option = $this->getManager()->find(Configurator\Option::class, $optionData['id']);
						if (!$option) {
							throw new ApiException\CustomValidationException(sprintf('ConfiguratorOption by id "%s" not found', $optionData['id']));
						}
					} else {
						/** @var Configurator\Option|null $option */
						$option = $this->getManager()->getRepository(Configurator\Option::class)->findOneBy([
							'name' => $optionData['name'],
							'groupId' => $group->getId(),
						]);
					}
				}
				
				if (!$option) {
					$option = new Configurator\Option();
				}
				
				$option->fromArray($optionData);
				$option->setGroup($group);
				
				// Only set new position if option doesn't exist yet
				// Otherwise the position might have been set manually already and we do not want to change that
				if (!isset($optionData['position']) && !$option->getId()) {
					$option->setPosition($optionPosition++);
				}
				$allOptions[] = $option;
				$groupOptions[] = $option;
			}
			
			$groupData['options'] = $groupOptions;
			
			$group->fromArray($groupData);
			$allGroups[] = $group;
		}
		
		// Clear needed in order to allow updates on configuratorSet. When removed constraints in
		// s_article_configurator_set_group_relations and s_article_configurator_set_option_relations
		// might fail.
		$configuratorSet->getOptions()->clear();
		$configuratorSet->setOptions($allOptions);
		$configuratorSet->getGroups()->clear();
		$configuratorSet->setGroups($allGroups);
		
		$this->getManager()->persist($configuratorSet);
		
		$data['configuratorSet'] = $configuratorSet;
		
		return $data;
	}
	
	/**
	 * @param $data
	 * @return mixed
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\ORM\TransactionRequiredException
	 * @throws \Shopware\Components\Api\Exception\CustomValidationException
	 */
	protected function prepareArticleAssociatedData($data) {
		// Check if a tax id is passed and load the tax model or set the tax parameter to null.
		if (!empty($data['taxId'])) {
			$data['tax'] = $this->getManager()->find(Tax::class, $data['taxId']);
			
			if (empty($data['tax'])) {
				throw new ApiException\CustomValidationException(sprintf('Tax by id "%s" not found', $data['taxId']));
			}
		} elseif (isset($data['tax']) && ($data['tax'] >= 0)) {
			$tax = $this->getManager()->getRepository(Tax::class)->findOneBy(['tax' => $data['tax']]);
			if (!$tax) {
				throw new ApiException\CustomValidationException(sprintf('Tax by taxrate "%s" not found', $data['tax']));
			}
			$data['tax'] = $tax;
		} else {
			unset($data['tax']);
		}
		
		// Check if a supplier id is passed and load the supplier model or set the supplier parameter to null.
		if (!empty($data['supplierId'])) {
			$data['supplier'] = $this->getManager()->find(Supplier::class, $data['supplierId']);
			if (empty($data['supplier'])) {
				throw new ApiException\CustomValidationException(sprintf('Supplier by id "%s" not found', $data['supplierId']));
			}
		} elseif (!empty($data['supplier'])) {
			$supplier = $this->getManager()->getRepository(Supplier::class)->findOneBy(['name' => $data['supplier']]);
			if (!$supplier) {
				$supplier = new Supplier();
				$supplier->setName($data['supplier']);
			}
			$data['supplier'] = $supplier;
		} else {
			unset($data['supplier']);
		}
		
		// Check if a priceGroup id is passed and load the priceGroup model or set the priceGroup parameter to null.
		if (isset($data['priceGroupId'])) {
			if (empty($data['priceGroupId'])) {
				$data['priceGroupId'] = null;
			} else {
				$data['priceGroup'] = $this->getManager()->find(Group::class, $data['priceGroupId']);
				if (empty($data['priceGroup'])) {
					throw new ApiException\CustomValidationException(sprintf('Pricegroup by id "%s" not found', $data['priceGroupId']));
				}
			}
		} else {
			unset($data['priceGroup']);
		}
		
		// Check if a propertyGroup is passed and load the propertyGroup model or set the propertyGroup parameter to null.
		if (isset($data['filterGroupId'])) {
			if (empty($data['filterGroupId'])) {
				$data['propertyGroup'] = null;
			} else {
				$data['propertyGroup'] = $this->getManager()->find(PropertyGroup::class, $data['filterGroupId']);
				
				if (empty($data['propertyGroup'])) {
					throw new ApiException\CustomValidationException(sprintf('PropertyGroup by id "%s" not found', $data['filterGroupId']));
				}
			}
		} else {
			unset($data['propertyGroup']);
		}
		
		return $data;
	}
	
	/**
	 * @param $data
	 * @return mixed
	 */
	protected function prepareAttributeAssociatedData($data)
	{
		if (isset($data['attribute']) && !isset($data['mainDetail']['attribute'])) {
			$data['mainDetail']['attribute'] = $data['attribute'];
		}
		unset($data['attribute']);
		if (isset($data['mainDetail']['attribute']['articleDetailId'])) {
			unset($data['mainDetail']['attribute']['articleDetailId']);
		}
		
		return $data;
	}
	
	/**
	 * @param array                            $data
	 * @param \Shopware\Models\Article\Article $article
	 * @return array
	 * @throws \Shopware\Components\Api\Exception\CustomValidationException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	protected function prepareCategoryAssociatedData(array $data, ProductModel $article): array {
		if (!isset($data['categories'])) {
			return $data;
		}
		
		$this->resetProductCategoryAssignment($data, $article);
		
		$properties = [];
		foreach ($data['categories'] as $categoryData) {
			foreach($categoryData as $property => $value) {
				$properties[] = $property;
			}
		}
		
		if (count($properties = array_unique($properties)) > 1) {
			throw new ApiException\CustomValidationException('Validation error - categories: The properties must be unique.', 400);
		}
		
		if (reset($properties) === 'ebayCategoryId') {
			$categories = $this->getCategoriesByEbayCategoryIds($data);
		} else {
			$categories = $article->getCategories();
		}
		
		$categoryIds = $categories->map(function ($category) {
			/* @var Category $category */
			return $category->getId();
		});
		
		$categoryIds = array_flip($categoryIds->toArray());
		
		foreach ($data['categories'] as $categoryData) {
			$category = $this->getManyToManySubElement(
				$categories,
				$categoryData,
				Category::class
			);
			
			if (!$category) {
				if (!empty($categoryData['path'])) {
					/** @var CategoryResource $categoryResource */
					$categoryResource = $this->getContainer()->get('shopware.api.category');
					$category = $categoryResource->findCategoryByPath($categoryData['path'], true);
					
					if (!$category) {
						throw new ApiException\CustomValidationException(
							sprintf('Could not find or create category by path: %s.', $categoryData['path'])
						);
					}
					
					if (isset($categoryIds[$category->getId()])) {
						continue;
					}
					
					$categories->add($category);
				}
			} else {
				$categoryIds[$category->getId()] = 1;
			}
		}
		
		$data['categories'] = $categories;
		
		return $data;
	}
	
	/**
	 * @param array $data
	 *
	 * @throws ApiException\CustomValidationException
	 *
	 * @return array
	 */
	protected function prepareSeoCategoryAssociatedData($data, ProductModel $article)
	{
		if (!isset($data['seoCategories'])) {
			return $data;
		}
		
		$categories = $this->checkDataReplacement(
			$article->getSeoCategories(),
			$data,
			'seoCategories',
			true
		);
		
		foreach ($data['seoCategories'] as $categoryData) {
			/** @var SeoCategory $seoCategory */
			$seoCategory = $this->getOneToManySubElement(
				$categories,
				$categoryData,
				SeoCategory::class
			);
			
			if (isset($categoryData['shopId'])) {
				/** @var Shop|null $shop */
				$shop = $this->manager->find(
					Shop::class,
					$categoryData['shopId']
				);
				
				if (!$shop) {
					throw new ApiException\CustomValidationException(
						sprintf('Could not find shop by id: %s.', $categoryData['shopId'])
					);
				}
				
				$seoCategory->setShop($shop);
			}
			
			if (!$seoCategory->getShop()) {
				throw new ApiException\CustomValidationException(
					sprintf('An product seo category requires a configured shop')
				);
			}
			
			if (isset($categoryData['categoryId'])) {
				/** @var Category|null $category */
				$category = $this->manager->find(
					Category::class,
					$categoryData['categoryId']
				);
				
				if (!$category) {
					throw new ApiException\CustomValidationException(
						sprintf('Could not find category by id: %s.', $categoryData['categoryId'])
					);
				}
				
				$seoCategory->setCategory($category);
			} elseif (isset($categoryData['categoryPath'])) {
				/** @var CategoryResource $categoryResource */
				$categoryResource = $this->getContainer()->get('shopware.api.category');
				$category = $categoryResource->findCategoryByPath(
					$categoryData['categoryPath'],
					true
				);
				if (!$category) {
					throw new ApiException\CustomValidationException(
						sprintf('Could not find category by path: %s.', $categoryData['categoryPath'])
					);
				}
				$seoCategory->setCategory($category);
			}
			
			$existing = $this->getCollectionElementByProperty(
				$data['categories'],
				'id',
				$seoCategory->getCategory()->getId()
			);
			
			if (!$existing) {
				throw new ApiException\CustomValidationException(
					sprintf('Seo category isn\'t assigned as normal product category. Only assigned categories can be used as seo category')
				);
			}
			
			$seoCategory->setArticle($article);
		}
		
		$data['seoCategories'] = $categories;
		
		return $data;
	}
	
	/**
	 * @param array $data
	 *
	 * @throws ApiException\CustomValidationException
	 *
	 * @return array
	 */
	protected function prepareAvoidCustomerGroups($data, ProductModel $article)
	{
		if (!isset($data['customerGroups'])) {
			return $data;
		}
		
		$customerGroups = $this->checkDataReplacement($article->getCustomerGroups(), $data, 'customerGroups', true);
		
		foreach ($data['customerGroups'] as $customerGroupData) {
			$this->getManyToManySubElement(
				$customerGroups,
				$customerGroupData,
				\Shopware\Models\Customer\Group::class
			);
		}
		
		$data['customerGroups'] = $customerGroups;
		
		return $data;
	}
	
	/**
	 * @param array $data
	 *
	 * @throws ApiException\CustomValidationException
	 *
	 * @return array
	 */
	protected function prepareRelatedAssociatedData($data, ProductModel $article)
	{
		if (!isset($data['related'])) {
			return $data;
		}
		
		$related = $this->checkDataReplacement($article->getRelated(), $data, 'related', true);
		
		foreach ($data['related'] as $relatedData) {
			if (empty($relatedData['number']) && empty($relatedData['id'])) {
				continue;
			}
			
			$relatedProduct = null;
			if ($relatedData['number']) {
				$productId = $this->getManager()->getConnection()->fetchColumn(
					'SELECT articleID FROM s_articles_details WHERE ordernumber = :number',
					[':number' => $relatedData['number']]
				);
				
				if ($productId) {
					$relatedProduct = $this->getManyToManySubElement(
						$related,
						['id' => $productId],
						ProductModel::class
					);
				}
			}
			
			if (!$relatedProduct) {
				$relatedProduct = $this->getManyToManySubElement(
					$related,
					$relatedData,
					ProductModel::class
				);
			}
			
			//no valid entity found, throw exception!
			if (!$relatedProduct) {
				$property = $relatedData['number'] ?: $relatedData['id'];
				throw new ApiException\CustomValidationException(
					sprintf('Related product by number/id "%s" not found', $property)
				);
			}
			
			/* @var ProductModel $relatedProduct */
			if ($relatedData['cross']) {
				$relatedProduct->getRelated()->add($article);
			}
		}
		
		$data['related'] = $related;
		
		return $data;
	}
	
	/**
	 * @param array $data
	 *
	 * @throws ApiException\CustomValidationException
	 *
	 * @return array
	 */
	protected function prepareSimilarAssociatedData($data, ProductModel $article)
	{
		if (!isset($data['similar'])) {
			return $data;
		}
		
		$similar = $this->checkDataReplacement($article->getSimilar(), $data, 'similar', true);
		
		foreach ($data['similar'] as $similarData) {
			if (empty($similarData['number']) && empty($similarData['id'])) {
				continue;
			}
			
			$similarProduct = null;
			if ($similarData['number']) {
				$productId = $this->getManager()->getConnection()->fetchColumn(
					'SELECT articleID FROM s_articles_details WHERE ordernumber = :number',
					[':number' => $similarData['number']]
				);
				
				if ($productId) {
					$similarProduct = $this->getManyToManySubElement(
						$similar,
						['id' => $productId],
						ProductModel::class
					);
				}
			}
			
			if (!$similarProduct) {
				$similarProduct = $this->getManyToManySubElement(
					$similar,
					$similarData,
					ProductModel::class
				);
			}
			
			// No valid entity found, throw exception!
			if (!$similarProduct) {
				$property = $similarData['number'] ?: $similarData['id'];
				throw new ApiException\CustomValidationException(
					sprintf('Similar product by number/id "%s" not found', $property)
				);
			}
			
			/* @var ProductModel $similarProduct */
			if ($similarData['cross']) {
				$similarProduct->getSimilar()->add($article);
			}
		}
		
		$data['similar'] = $similar;
		
		return $data;
	}
	
	/**
	 * @param array $data
	 *
	 * @throws ApiException\CustomValidationException
	 *
	 * @return array
	 */
	protected function preparePropertyValuesData($data, ProductModel $article)
	{
		if (!isset($data['propertyValues'])) {
			return $data;
		}
		
		// Remove assigned values
		if (empty($data['propertyValues'])) {
			return $data;
		}
		
		$propertyRepository = $this->getManager()->getRepository(PropertyGroup::class);
		
		/*
		 *  Get group - this is required.
		 */
		if (isset($data['propertyGroup'])) {
			$propertyGroup = $data['propertyGroup'];
		} else {
			$propertyGroup = $article->getPropertyGroup();
		}
		
		if (!$propertyGroup instanceof PropertyGroup) {
			throw new ApiException\CustomValidationException('There is no propertyGroup specified');
		}
		
		$models = [];
		
		foreach ($data['propertyValues'] as $valueData) {
			$value = null;
			/** @var Option $option */
			$option = null;
			
			// Get value by id
			if (isset($valueData['id'])) {
				$value = $this->getManager()->getRepository(Value::class)->find($valueData['id']);
				if (!$value) {
					throw new ApiException\CustomValidationException(sprintf('Property value by id "%s" not found', $valueData['id']));
				}
				// Get / create value by name
			} elseif (isset($valueData['value'])) {
				// Get option
				if (isset($valueData['option'])) {
					// Get option by id
					if (isset($valueData['option']['id'])) {
						/** @var Option|null $option */
						$option = $this->getManager()->getRepository(Option::class)->find($valueData['option']['id']);
						if (!$option) {
							throw new ApiException\CustomValidationException(sprintf('Property option by id "%s" not found', $valueData['option']['id']));
						}
						$filters = [
							['property' => 'options.id', 'expression' => '=', 'value' => $option->getId()],
							['property' => 'groups.id', 'expression' => '=', 'value' => $propertyGroup->getId()],
						];
						$query = $propertyRepository->getPropertyRelationQuery($filters, null, 1, 0);
						/** @var \Shopware\Models\Property\Relation|null $relation */
						$relation = $query->getOneOrNullResult(self::HYDRATE_OBJECT);
						if (!$relation) {
							$propertyGroup->addOption($option);
						}
						// Get/create option depending on associated filter groups
					} elseif (isset($valueData['option']['name'])) {
						// If a name is passed and there is a matching option/group relation, get this option
						// If only a name is passed, create a new option
						$filters = [
							['property' => 'options.name', 'expression' => '=', 'value' => $valueData['option']['name']],
							['property' => 'groups.name', 'expression' => '=', 'value' => $propertyGroup->getName()],
						];
						$query = $propertyRepository->getPropertyRelationQuery($filters, null, 1, 0);
						/** @var \Shopware\Models\Property\Relation|null $relation */
						$relation = $query->getOneOrNullResult(self::HYDRATE_OBJECT);
						if (!$relation) {
							//checks if a new option was created
							//because the new option is not written to the database at this point
							$groupOption = $this->getCollectionElementByProperty(
								$propertyGroup->getOptions(),
								'name',
								$valueData['option']['name']
							);
							// Creates a new option
							if ($groupOption === null) {
								$option = new Option();
								$propertyGroup->addOption($option);
							} else {
								$option = $groupOption;
							}
						} else {
							$option = $relation->getOption();
						}
					} else {
						throw new ApiException\CustomValidationException('A property option needs to be given for each property value');
					}
					$option->fromArray($valueData['option']);
					if ($option->isFilterable() === null) {
						$option->setFilterable(false);
					}
				} else {
					throw new ApiException\CustomValidationException('A property option needs to be given for each property value');
				}
				// Create the value
				// If there is a filter value with matching name and option, load this value, else create a new one
				$value = $this->getManager()->getRepository(Value::class)->findOneBy([
					'value' => $valueData['value'],
					'optionId' => $option->getId(),
				]);
				if (!$value) {
					$value = new Value($option, $valueData['value']);
				}
				if (isset($valueData['position'])) {
					$value->setPosition($valueData['position']);
				}
				$this->getManager()->persist($value);
			} else {
				throw new ApiException\CustomValidationException('Name or id for property value required');
			}
			$models[] = $value;
		}
		
		$data['propertyValues'] = $models;
		
		return $data;
	}
	
	/**
	 * Returns a query builder to select all product images with mappings and rules.
	 * Used to generate the variant images.
	 *
	 * @param int $articleId
	 *
	 * @return \Doctrine\ORM\QueryBuilder|QueryBuilder
	 */
	protected function getArticleImageMappingsQuery($articleId)
	{
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['mappings', 'image', 'rules'])
			->from(Image\Mapping::class, 'mappings')
			->innerJoin('mappings.image', 'image')
			->innerJoin('mappings.rules', 'rules')
			->where('image.articleId = :articleId')
			->setParameter('articleId', $articleId);
		
		return $builder;
	}
	
	/**
	 * @deprecated in 5.6, will be removed in 5.7 without a replacement
	 *
	 * Checks if the passed product image is already created
	 * as variant image.
	 *
	 * @return bool
	 */
	protected function isVariantImageExist(Detail $variant, Image $image)
	{
		trigger_error(sprintf('%s:%s is deprecated since Shopware 5.6 and will be removed with 5.7. Will be removed without replacement.', __CLASS__, __METHOD__), E_USER_DEPRECATED);
		
		/** @var Image $variantImage */
		foreach ($variant->getImages() as $variantImage) {
			if ((int) $variantImage->getParent()->getId() === (int) $image->getId()) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Small helper function which creates a query builder to select
	 * all product variants.
	 *
	 * @param int $id
	 *
	 * @return \Doctrine\ORM\QueryBuilder|QueryBuilder
	 */
	protected function getArticleVariantQuery($id)
	{
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select('variants');
		$builder->from(Detail::class, 'variants')
			->where('variants.articleId = :articleId')
			->setParameter('articleId', $id);
		
		return $builder;
	}
	
	/**
	 * Creates the product image mappings for the passed product and image entity.
	 * The mappings parameter contains a multi dimensional array with configurator options.
	 * The first level of the mappings defines the OR conditions of the image mappings.
	 * The second level of the mappings defines a single rule.
	 * Example:
	 * $mappings = array(
	 *    array(
	 *       array('name' => 'red')
	 *       AND
	 *       array('name' => 'small')
	 *    )
	 *    //OR
	 *    array('name' => 'blue')
	 * )
	 *
	 * @throws ApiException\CustomValidationException
	 */
	protected function createImageMappings(Image $image, ProductModel $article, array $mappings)
	{
		if (!$article->getConfiguratorSet()) {
			throw new ApiException\CustomValidationException(
				'Product is no configurator product. Image mapping can only be created on configurator products'
			);
		}
		
		$configuratorOptions = $article->getConfiguratorSet()->getOptions();
		
		foreach ($mappings as $mappingData) {
			$options = new ArrayCollection();
			
			foreach ($mappingData as $option) {
				$conditions = [];
				
				if (isset($option['id']) && $option['id']) {
					$conditions['id'] = $option['id'];
				}
				
				$conditions['name'] = $option['name'];
				
				$available = $this->getCollectionElementByProperties($configuratorOptions, $conditions);
				
				if (!$available) {
					$property = $option['id'] ?: $option['name'];
					throw new ApiException\CustomValidationException(
						sprintf('Passed option "%s" does not exist in the configurator set of the product', $property)
					);
				}
				
				$options->add($available);
			}
			
			if ($options->isEmpty()) {
				throw new ApiException\CustomValidationException('No available option exists');
			}
			
			$this->getVariantResource()->createImageMappingForOptions(
				$options,
				$image
			);
		}
	}
	
	/**
	 * Translate the whole product array.
	 *
	 * @return array
	 */
	protected function translateArticle(array $data, Shop $shop)
	{
		$this->getTranslationResource()->setResultMode(
			self::HYDRATE_ARRAY
		);
		$translation = $this->getSingleTranslation(
			'article',
			$shop->getId(),
			$data['id']
		);
		
		if (!empty($translation)) {
			$data = $this->mergeTranslation($data, $translation['data']);
			
			if ($data['mainDetail']) {
				$data['mainDetail'] = $this->mergeTranslation($data['mainDetail'], $translation['data']);
				
				if ($data['mainDetail']['attribute']) {
					$data['mainDetail']['attribute'] = $this->mergeTranslation(
						$data['mainDetail']['attribute'],
						$translation['data']
					);
				}
				
				if ($data['mainDetail']['configuratorOptions']) {
					$data['mainDetail']['configuratorOptions'] = $this->translateAssociation(
						$data['mainDetail']['configuratorOptions'],
						$shop,
						'configuratoroption'
					);
				}
			}
		}
		
		$data['details'] = $this->translateVariants(
			$data['details'],
			$shop
		);
		
		if (isset($data['links'])) {
			$data['links'] = $this->translateAssociation(
				$data['links'],
				$shop,
				'link'
			);
		}
		
		if (isset($data['downloads'])) {
			$data['downloads'] = $this->translateAssociation(
				$data['downloads'],
				$shop,
				'download'
			);
		}
		
		$data['supplier'] = $this->translateSupplier($data['supplier'], $shop);
		
		$data['propertyValues'] = $this->translatePropertyValues($data['propertyValues'], $shop);
		
		$data['propertyGroup'] = $this->translatePropertyGroup($data['propertyGroup'], $shop);
		
		if (!empty($data['configuratorSet']) && !empty($data['configuratorSet']['groups'])) {
			$data['configuratorSet']['groups'] = $this->translateAssociation(
				$data['configuratorSet']['groups'],
				$shop,
				'configuratorgroup'
			);
		}
		
		if (isset($data['related'])) {
			$data['related'] = $this->translateAssociation(
				$data['related'],
				$shop,
				'article'
			);
		}
		
		if (isset($data['similar'])) {
			$data['similar'] = $this->translateAssociation(
				$data['similar'],
				$shop,
				'article'
			);
		}
		
		if (isset($data['images'])) {
			$data['images'] = $this->translateAssociation(
				$data['images'],
				$shop,
				'articleimage'
			);
		}
		
		return $data;
	}
	
	/**
	 * Translates the passed values array with the passed shop entity.
	 *
	 * @param array $values
	 */
	protected function translatePropertyValues($values, Shop $shop)
	{
		if (empty($values)) {
			return $values;
		}
		
		foreach ($values as &$value) {
			$translation = $this->getSingleTranslation(
				'propertyvalue',
				$shop->getId(),
				$value['id']
			);
			if (empty($translation)) {
				continue;
			}
			
			$translation['data']['value'] = $translation['data']['optionValue'];
			
			$value = $this->mergeTranslation(
				$value,
				$translation['data']
			);
		}
		
		return $values;
	}
	
	/**
	 * Translates the passed supplier data.
	 *
	 * @param array $supplier
	 *
	 * @return array
	 */
	protected function translateSupplier($supplier, Shop $shop)
	{
		if (empty($supplier)) {
			return $supplier;
		}
		$translation = $this->getSingleTranslation(
			'supplier',
			$shop->getId(),
			$supplier['id']
		);
		
		if (empty($translation)) {
			return $supplier;
		}
		
		return $this->mergeTranslation(
			$supplier,
			$translation['data']
		);
	}
	
	/**
	 * Translates the passed property group data.
	 *
	 * @param array $groupData
	 *
	 * @return array
	 */
	protected function translatePropertyGroup($groupData, Shop $shop)
	{
		if (empty($groupData)) {
			return $groupData;
		}
		
		$translation = $this->getSingleTranslation(
			'propertygroup',
			$shop->getId(),
			$groupData['id']
		);
		
		if (empty($translation)) {
			return $groupData;
		}
		
		$translation['data']['name'] = $translation['data']['groupName'];
		
		return $this->mergeTranslation(
			$groupData,
			$translation['data']
		);
	}
	
	/**
	 * Translates the passed variants array and all associated data.
	 *
	 * @param array $details
	 */
	protected function translateVariants($details, Shop $shop)
	{
		if (empty($details)) {
			return $details;
		}
		
		foreach ($details as &$variant) {
			$translation = $this->getSingleTranslation(
				'variant',
				$shop->getId(),
				$variant['id']
			);
			if (empty($translation)) {
				continue;
			}
			$variant = $this->mergeTranslation(
				$variant,
				$translation['data']
			);
			$variant['attribute'] = $this->mergeTranslation(
				$variant['attribute'],
				$translation['data']
			);
			
			if ($variant['configuratorOptions']) {
				$variant['configuratorOptions'] = $this->translateAssociation(
					$variant['configuratorOptions'],
					$shop,
					'configuratoroption'
				);
			}
			
			if ($variant['images']) {
				foreach ($variant['images'] as &$image) {
					$translation = $this->getSingleTranslation(
						'articleimage',
						$shop->getId(),
						$image['parentId']
					);
					if (empty($translation)) {
						continue;
					}
					$image = $this->mergeTranslation($image, $translation['data']);
				}
			}
		}
		
		return $details;
	}
	
	/**
	 * Helper function which merges the translated data into the already
	 * existing data object. This function merges only values, which already
	 * exist in the original data array.
	 *
	 * @param array $data
	 * @param array $translation
	 *
	 * @return array
	 */
	protected function mergeTranslation($data, $translation)
	{
		$data = array_merge(
			$data,
			array_intersect_key($translation, $data)
		);
		
		return $data;
	}
	
	/**
	 * Helper function which translates associated array data.
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	protected function translateAssociation(array $association, Shop $shop, $type)
	{
		foreach ($association as &$item) {
			$translation = $this->getSingleTranslation(
				$type,
				$shop->getId(),
				$item['id']
			);
			if (empty($translation)) {
				continue;
			}
			$item = $this->mergeTranslation($item, $translation['data']);
		}
		
		return $association;
	}
	
	/**
	 * Helper function to get a single translation.
	 *
	 * @param string $type
	 * @param int    $shopId
	 * @param string $key
	 *
	 * @return array
	 */
	protected function getSingleTranslation($type, $shopId, $key)
	{
		$translation = $this->getTranslationResource()->getList(0, 1, [
			['property' => 'translation.type', 'value' => $type],
			['property' => 'translation.key', 'value' => $key],
			['property' => 'translation.shopId', 'value' => $shopId],
		]);
		
		return $translation['data'][0];
	}
	
	/**
	 * @param \Shopware\Components\Model\QueryBuilder $builder
	 * @return mixed
	 * @throws \Exception
	 */
	private function getSingleResult(QueryBuilder $builder)	{
		$query = $builder->getQuery();
		$query->setHydrationMode($this->getResultMode());
		$paginator = $this->getManager()->createPaginator($query);
		
		return $paginator->getIterator()->current();
	}
	
	/**
	 * @param \Shopware\Components\Model\QueryBuilder $builder
	 * @return array
	 * @throws \Exception
	 */
	private function getFullResult(QueryBuilder $builder): array {
		$query = $builder->getQuery();
		
		$query->setHydrationMode($this->getResultMode());
		$paginator = $this->getManager()->createPaginator($query);
		
		return $paginator->getIterator()->getArrayCopy();
	}
	
	/**
	 * Helper function for the category assignment.
	 * This function is used for the category configuration.
	 * If the data key __options_categories => replace is set to true,
	 * the function removes the assigned product categories from the
	 * s_articles_categories and s_articles_categories_ro table.
	 * @param array                            $data
	 * @param \Shopware\Models\Article\Article $product
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function resetProductCategoryAssignment(array $data, ProductModel $product): void {
		if (!$product->getId()) {
			return;
		}
		
		$key = '__options_categories';
		
		// Replacement deactivated?
		if (isset($data[$key]) && $data[$key]['replace'] === false) {
			return;
		}
		
		$connection = $this->manager->getConnection();
		$connection->executeUpdate(
			'DELETE FROM s_articles_categories WHERE articleID = :articleId',
			[':articleId' => $product->getId()]
		);
		
		$connection->executeUpdate(
			'DELETE FROM s_articles_categories_ro WHERE articleID = :articleId',
			[':articleId' => $product->getId()]
		);
	}
	
	/**
	 * @param array $data
	 *
	 * @throws ApiException\CustomValidationException
	 *
	 * @return array
	 */
	private function prepareDownloadsAssociatedData($data, ProductModel $product)
	{
		if (!isset($data['downloads'])) {
			return $data;
		}
		
		$downloads = $this->checkDataReplacement($product->getDownloads(), $data, 'downloads', true);
		
		foreach ($data['downloads'] as &$downloadData) {
			/** @var Download $download */
			$download = $this->getOneToManySubElement(
				$downloads,
				$downloadData,
				Download::class
			);
			
			if (isset($downloadData['link'])) {
				$media = $this->getMediaResource()->internalCreateMediaByFileLink(
					$downloadData['link'],
					-6
				);
				if (isset($downloadData['name']) && !empty($downloadData['name'])) {
					$media->setDescription($downloadData['name']);
				}
				
				try { //persist the model into the model manager
					$this->getManager()->persist($media);
				} catch (ORMException $e) {
					throw new ApiException\CustomValidationException(
						sprintf('Some error occured while loading your image from link "%s"', $downloadData['link'])
					);
				}
				
				$download->setFile($media->getPath());
				$download->setName($media->getName());
				$download->setSize($media->getFileSize());
			}
			
			$download->fromArray($downloadData);
			$download->setArticle($product);
		}
		unset($downloadData);
		
		$data['downloads'] = $downloads;
		
		return $data;
	}
	
	/**
	 * @param                                  $data
	 * @param \Shopware\Models\Article\Article $product
	 * @return mixed
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\ORM\TransactionRequiredException
	 * @throws \Shopware\Components\Api\Exception\CustomValidationException
	 */
	private function prepareImageAssociatedData($data, ProductModel $product)
	{
		if (!isset($data['images'])) {
			return $data;
		}
		
		// remove assigned images
		if (empty($data['images'])) {
			$images = $product->getImages();
			$images->clear();
			unset($data['images']);
			
			return $data;
		}
		
		$position = 1;
		$images = $this->checkDataReplacement($product->getImages(), $data, 'images', TRUE);
		
		foreach ($data['images'] as &$imageData) {
			/** @var Image $image */
			$image = $this->getOneToManySubElement(
				$images,
				$imageData,
				Image::class
			);
			
			if (isset($imageData['link'])) {
				$createImageData = [$imageData['link']];
				
				if (isset($imageData['albumId'])) {
					$createImageData[] = $imageData['albumId'];
				}
				
				/** @var MediaModel $media */
				$media = $this->getMediaResource()->internalCreateMediaByFileLink(
					...$createImageData
				);
				
				$image = $this->updateArticleImageWithMedia(
					$product,
					$image,
					$media
				);
				
				$image->setPosition($position);
				++$position;
			} elseif (!empty($imageData['mediaId'])) {
				$media = $this->getManager()->find(
					MediaModel::class,
					(int) $imageData['mediaId']
				);
				
				if (!($media instanceof MediaModel)) {
					throw new ApiException\CustomValidationException(sprintf('Media by mediaId "%s" not found', $imageData['mediaId']));
				}
				
				$image = $this->updateArticleImageWithMedia(
					$product,
					$image,
					$media
				);
				
				$image->setPosition($position);
				++$position;
			}
			
			$image->fromArray($imageData);
			
			// if image is set as main set other images to secondary
			if ((int) $image->getMain() === 1) {
				/** @var Image $otherImage */
				foreach ($images as $otherImage) {
					//only update existing images which are not the current processed image.
					//otherwise the main flag won't be changed.
					if ($otherImage->getId() !== $image->getId()) {
						$otherImage->setMain(2);
					}
				}
			}
			
			if (isset($imageData['options'])) {
				$this->createImageMappings($image, $product, $imageData['options']);
			}
		}
		
		$hasMain = $this->getCollectionElementByProperty(
			$images,
			'main',
			1
		);
		
		if (!$hasMain) {
			$image = $images->get(0);
			$image->setMain(1);
		}
		unset($data['images']);
		
		return $data;
	}
	
	/**
	 * @return array
	 */
	private function getAttributeProperties(): array {
		/** @var \Shopware\Bundle\AttributeBundle\Service\CrudServiceInterface $crud */
		$crud = $this->getContainer()->get('shopware_attribute.crud_service');
		$attributeNames = $crud->getList('s_articles_attributes');
		$fields = [];
		foreach ($attributeNames as $property) {
			$fields[] = '__attribute_' . $property->getColumnName();
		}
		
		return $fields;
	}
	
	/**
	 * @param array                            $carLinks
	 * @param \Shopware\Models\Article\Article $product
	 * @throws \Doctrine\ORM\ORMException
	 */
	private function writeCarLinks(array $carLinks = [], ProductModel $product): void {
		if (empty($carLinks)) {
			return;
		}
		
		$articleDetailsId = $product->getMainDetail()->getId();
		
		$query = $this->getCarLinkRepository()->getDeleteCarLinksQuery($articleDetailsId);
		$query->execute();
		
		$carLink = NULL;
		$tecdocIds = [];
		$em = $this->getManager();
		
		foreach($carLinks as $data) {
			if (in_array((int)$data['tecdocId'], $tecdocIds, TRUE)) {
				continue;
			}
			
			$data['tecdocId'] = (int)$data['tecdocId'];
			$data['restriction'] = $data['restriction']??'';
			
			$tecdocIds[] = $data['tecdocId'];
			$carLink = new CarLinks();
			$data['articleDetailsId'] = $articleDetailsId;
			
			$carLink->fromArray($data);
			
			if (!$em->isOpen()) {
				$em = $em->create($em->getConnection(),	$em->getConfiguration());
			}
			
			try {
				$em->persist($carLink);
				$em->flush($carLink);
			} catch (OptimisticLockException | ORMException | \Exception $e) {
			}
		}
	}
	
	/**
	 * @param array $data
	 * @return int|mixed|string
	 */
	private function getCategoriesByEbayCategoryIds(array $data) {
		$ebayCategoryIds = [];
		
		foreach($data['categories'] as $categoryData) {
			foreach($categoryData as $ebayCategoryId) {
				$ebayCategoryIds[] = (int)$ebayCategoryId;
			}
		}
		
		$builder = $this->getManager()->createQueryBuilder();
		$builder->select(['categories'])
			->from(Category::class, 'categories')
			//->innerJoin('categories.articles', 'articles')
			->innerJoin('categories.attribute', 'attribute')
			->where('attribute.ebayCategoryId IN (:ebayCategoryIds)')
			->setParameter('ebayCategoryIds', $ebayCategoryIds);
		
		 return new ArrayCollection($builder->getQuery()->getResult());
	}
	
	/**
	 * @param \Shopware\Models\Article\Article $product
	 * @param string                           $jobType
	 * @return int
	 */
	private function addToGoogleMerchantCenterQueue(ProductModel $product, string $jobType = 'create'): int {
		try {
			$entity = $this->getManager()->getRepository(GoogleMerchantCenterQueue::class)
				->findOneBy([
					'articleId' => $product->getId()
				]);
			
			if ($entity instanceof GoogleMerchantCenterQueue) {
				$entity->setHandled(NULL);
				$entity->setJobType('update');
				
			} else {
				$entity = new GoogleMerchantCenterQueue();
				$entity->setArticleId($product->getId());
				$entity->setJobType($jobType);
			}
			
			$this->getManager()->persist($entity);
			$this->getManager()->flush($entity);
		} catch (\Exception $exception) {
			$this->error($exception);
			
			return 0;
		}
		
		return $entity->getId();
	}
	
	/**
	 * @param \Shopware\Models\Article\Article $product
	 * @return bool
	 */
	private function isInQueue(ProductModel $product): bool {
		return NULL !== $this->getManager()->getRepository(GoogleMerchantCenterQueue::class)
			->findOneBy([
				'articleId' => $product->getId()
			]);
	}
	
	/**
	 * @param array $product
	 */
	private function filterBadWords(array &$product): void {
		$filterName = isset($product['name']);
		$filterDescription = isset($product['description']);
		$filterDescriptionLong = isset($product['descriptionLong']);
		
		if (!$filterName && !$filterDescription && !$filterDescriptionLong) {
			return;
		}
		
		$textHelper = Shopware()->Container()->get('itsw.helper.text');
		
		if ($filterName) {
			$product['name'] = $textHelper->filterBadWords($product['name']);
		}
		if ($filterDescription) {
			$product['description'] = $textHelper->filterBadWords($product['description']);
		}
		if ($filterDescriptionLong) {
			$product['descriptionLong'] = $textHelper->filterBadWords($product['descriptionLong']);
		}
	}
}