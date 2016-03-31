<?php
/**
 * @vendor      BiberLtd
 * @package		Core\Bundles\NewsManagementBundle
 * @subpackage	Services
 * @name	    NewsManagementModel
 *
 * @author		Can Berkol
 * @author      Said Imamoglu
 *
 * @copyright   Biber Ltd. (www.biberltd.com)
 *
 * @version     1.1.3
 * @date        18.09.2015
 *
 */
namespace BiberLtd\Bundle\NewsManagementBundle\Services;

/** Extends CoreModel */
use BiberLtd\Bundle\CoreBundle\CoreModel;
/** Entities to be used */
use BiberLtd\Bundle\CoreBundle\Responses\ModelResponse;
use BiberLtd\Bundle\NewsManagementBundle\Entity as BundleEntity;
/** Helper Models */
use BiberLtd\Bundle\SiteManagementBundle\Services as SMMService;
use BiberLtd\Bundle\MultiLanguageSupportBundle\Services as MLSService;
/** Core Service */
use BiberLtd\Bundle\CoreBundle\Services as CoreServices;

class NewsManagementModel extends CoreModel {
	public $entity = array(
		'n' => array('name' => 'NewsManagementBundle:News', 'alias' => 'n'),
		'nl' => array('name' => 'NewsManagementBundle:NewsLocalization', 'alias' => 'nl'),
		'nc' => array('name' => 'NewsManagementBundle:NewsCategory', 'alias' => 'nc'),
		'ncl' => array('name' => 'NewsManagementBundle:NewsCategoryLocalization', 'alias' => 'ncl'),
		'con' => array('name' => 'NewsManagementBundle:CategoriesOfNews', 'alias' => 'con'),
		'fon' => array('name' => 'NewsManagementBundle:FilesOfNews', 'alias' => 'fon'),
	);
	/**
	 * @name 			addFilesToNewsItems()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->getNewsItem()
	 * @use             $this->isFileOfNews()
	 * @use             $this->createException()
	 *
	 * @param           mixed           $item
	 * @param           array           $files
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addFilesToNewsItems($item, $files, $language) {
		$timeStamp = time();
		$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $lModel->getLanguage($language);
		if($response->error->exist){
			return $response;
		}
		$language = $response->result->set;
		unset($response);
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		if (!is_array($files)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. $groups parameter must be an array collection', 'E:S:001');
		}
		$toAdd = array();
		$fModel = $this->kernel->getContainer()->get('filemanagement.model');
		foreach ($files as $file) {
			$response = $fModel->getFile($file);
			if($response->error->exist){
				break;
			}
			$file = $response->result->set;
			if (!$this->isFileOfNews($item, $file, true)) {
				$toAdd[] = $file;
			}
		}
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
		$insertedItems = array();
		$i = 1;
		foreach ($toAdd as $file) {
			$entity = new BundleEntity\FilesOfNews();
			$entity->setFile($file)->setNews($item)->setDateAdded($now)->setSortOrder($i)->setLanguage($language);
			$this->em->persist($entity);
			$insertedItems[] = $entity;
		}
		$countInserts = count($toAdd);
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name 			addNewsToCategories()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->getNewsItem()
	 * @use             $this->getNewsCategory()
	 * @use             $this->isNewsOfCategory()
	 * @use             $this->createException()
	 *
	 * @param           mixed           $item
	 * @param           array           $categories
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addNewsToCategories($item, $categories) {
		$timeStamp = time();
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			var_dump(get_class($item));die;
			return $response;
		}
		$item = $response->result->set;
		if (!is_array($categories)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. $groups parameter must be an array collection', 'E:S:001');
		}
		$toAdd = array();
		foreach ($categories as $category) {
			$response = $this->getNewsCategory($category);
			if($response->error->exist){
				break;
			}
			$category = $response->result->set;
			if (!$this->isNewsOfCategory($item, $category, true)) {
				$toAdd[] = $category;
			}
		}
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
		$insertedItems = array();
		foreach ($toAdd as $cat) {
			$entity = new BundleEntity\CategoriesOfNews();
			$entity->setCategory($cat)->setNews($item)->setDateAdded($now)->setSortOrder(1);
			$this->em->persist($entity);
			$insertedItems[] = $entity;
		}
		$countInserts = count($toAdd);
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name 			deleteNewsItem()
	 *
	 * @since			1.0.0
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->deleteFiles()
	 *
	 * @param           mixed           $news
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteNewsItem($news){
		return $this->deleteNewsItems(array($news));
	}
	/**
	 * @name 			deleteNewsItems()
	 *
	 * @since			1.0.0
	 * @version         1.0.2
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteNewsItems($collection) {
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\News){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getNewsItem($entry);
				if(!$response->error->exist){
					$entry = $response->result->set;
					$this->em->remove($entry);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();

		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}
	/**
	 * @name 			doesNewsItemExist()
	 *
	 * @since			1.0.0
	 * @version         1.0.0
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->getNewsItem()
	 *
	 * @param           mixed           $news
	 *
	 * @param           bool            $bypass         If set to true does not return response but only the result.
	 *
	 * @return          mixed           $response
	 */
	public function doesNewsItemExist($news, $bypass = false) {
		$timeStamp = time();
		$exist = false;

		$response = $this->getNewsItem($news);

		if ($response->error->exist) {
			if($bypass){
				return $exist;
			}
			$response->result->set = false;
			return $response;
		}
		$exist = true;
		if ($bypass) {
			return $exist;
		}
		return new ModelResponse($exist, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			getLastAddedFileOfNews()
	 *
	 * @since			1.0.9
	 * @version         1.1.0
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $item
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getLastAddedFileOfNews($item) {
		$timeStamp = time();
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$qStr = 'SELECT '.$this->entity['fon']['alias']
			. ' FROM '.$this->entity['fon']['name'].' '.$this->entity['fon']['alias']
			. ' WHERE '.$this->entity['fon']['alias'].'.news = '.$item->getId()
			. ' ORDER BY '.$this->entity['fon']['alias'].'.date_added DESC';

		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, array('start' => 0, 'count' => 1));

		$result = $q->getResult();

		if(count($result) < 1){
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}

		$fModel = $this->kernel->getContainer()->get('filemanagement.model');

		$response = $fModel->getFile($result[0]->getFile());

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			getNewsCategory()
	 *
	 * @since			1.0.2
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 * @use             $this->listNewsItems()
	 *
	 * @param           mixed           $category
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getNewsCategory($category) {
		$timeStamp = time();
		if($category instanceof BundleEntity\NewsCategory){
			return new ModelResponse($category, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
		}
		$result = null;
		switch($category){
			case is_numeric($category):
				$result = $this->em->getRepository($this->entity['nc']['name'])->findOneBy(array('id' => $category));
				break;
			case is_string($category):
				$response = $this->getNewsCategoryByUrlKey($category);
				if($response->error->exist){
					return $response;
				}
				$result = $response->result->set;
				unset($response);
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getNewsCategoryByUrlKey()
	 *
	 * @since           1.0.6
	 * @version         1.0.6
	 * @author          Can Berkol
	 *
	 * @use             $this->listProducts()
	 * @use             $this->createException()
	 *
	 * @param           mixed 			$urlKey
	 * @param			mixed			$language
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getNewsCategoryByUrlKey($urlKey, $language = null){
		$timeStamp = time();
		if(!is_string($urlKey)){
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['ncl']['alias'].'.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if(!is_null($language)){
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if(!$response->error->exist){
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['ncl']['alias'].'.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listNewsCategories($filter, null, array('start' => 0, 'count' => 1));
		if($response->error->exist){
			return $response;
		}
		return new ModelResponse($response->result->set[0], 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			getNewsItem()
	 *
	 * @since			1.0.0
	 * @version         1.0.2
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 * @use             $this->listNewsItems()
	 *
	 * @param           mixed           $news
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getNewsItem($news) {
		$timeStamp = time();
		if($news instanceof BundleEntity\News){
			return new ModelResponse($news, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
		}
		$result = null;
		switch($news){
			case is_numeric($news):
				$result = $this->em->getRepository($this->entity['n']['name'])->findOneBy(array('id' => $news));
				break;
			case is_string($news):
				$response = $this->getNewsItemByUrlKey($news);
				if($response->error->exist){
					return $response;
				}
				$result = $response->result->set;
				unset($response);
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getNewsItemByUrlKey()
	 *
	 * @since           1.0.6
	 * @version         1.1.1
	 * @author          Can Berkol
	 *
	 * @use             $this->listProducts()
	 * @use             $this->createException()
	 *
	 * @param           mixed 			$urlKey
	 * @param			mixed			$language
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getNewsItemByUrlKey($urlKey, $language = null){
		$timeStamp = time();
		if(!is_string($urlKey)){
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['nl']['alias'].'.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if(!is_null($language)){
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if(!$response->error->exist){
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['nl']['alias'].'.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listNewsItems($filter, null, array('start' => 0, 'count' => 1));

		$response->result->set = $response->result->set[0];
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			insertNewsCategory()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->insertNewsCategories()
	 *
	 * @param           mixed           $category
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertNewsCategory($category) {
		return $this->insertNewsCategories(array($category));
	}
	/**
	 * @name 			insertNewsCategories()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertNewsCategories($collection)	{
		$timeStamp = time();
		/** Parameter must be an array */
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$countLocalizations = 0;
		$insertedItems = array();
		$localizations = array();
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\NewsCategory) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else if (is_object($data)) {
				$entity = new BundleEntity\NewsCategory();
				$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
				if(!property_exists($data, 'date_added')){
					$data->date_added = $now;
				}
				if(!property_exists($data, 'date_updated')){
					$data->date_updated = $now;
				}
				if(!property_exists($data, 'site')){
					$data->site = 1;
				}
				foreach ($data as $column => $value) {
					$localeSet = false;
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'local':
							$localizations[$countInserts]['localizations'] = $value;
							$localeSet = true;
							$countLocalizations++;
							break;
						case 'site':
							$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
							$response = $sModel->getSite($value);
							if(!$response->error->exist){
								$entity->$set($response->result->set);
							}
							unset($response, $sModel);
							break;
						default:
							$entity->$set($value);
							break;
					}
					if ($localeSet) {
						$localizations[$countInserts]['entity'] = $entity;
					}
				}
				$this->em->persist($entity);
				$insertedItems[] = $entity;

				$countInserts++;
			}
		}
		if ($countInserts > 0) {
			$this->em->flush();
		}
		/** Now handle localizations */
		if ($countInserts > 0 && $countLocalizations > 0) {
			$response = $this->insertNewsItemCategoryLocalizations($localizations);
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	/**
	 * @name 			insertNewsItem()
	 *
	 * @since			1.0.0
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->insertNewsItems()
	 *
	 * @param           mixed           $item
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertNewsItem($item) {
		return $this->insertNewsItems(array($item));
	}
	/**
	 * @name 			insertNewsItems()
	 *
	 * @since			1.0.0
	 * @version         1.1.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertNewsItems($collection){
		$timeStamp = time();
		/** Parameter must be an array */
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$countLocalizations = 0;
		$countCats = 0;
		$countFiles = 0;
		$insertedItems = array();
		$localizations = array();
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\News) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else if (is_object($data)) {
				$entity = new BundleEntity\News();
				$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
				if(!property_exists($data, 'date_added')){
					$data->date_added = $now;
				}
				if(!property_exists($data, 'date_published')){
					$data->date_published = $now;
				}
				if(!property_exists($data, 'status')){
					$data->status = 'p';
				}
				if(!property_exists($data, 'site')){
					$data->site = 1;
				}
				$cats = array();
				foreach ($data as $column => $value) {
					$localeSet = false;
					$catSet = false;
					$fileSet = false;
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'local':
							$localizations[$countInserts]['localizations'] = $value;
							$localeSet = true;
							$countLocalizations++;
							break;
						case 'author':
							$mModel = $this->kernel->getContainer()->get('membermanagement.model');
							$response = $mModel->getMember($value);
							if(!$response->error->exist){
								$entity->$set($response->result->set);
							}
							unset($response, $sModel);
							break;
						case 'site':
							$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
							$response = $sModel->getSite($value);
							if(!$response->error->exist){
								$entity->$set($response->result->set);
							}
							unset($response, $sModel);
							break;
						case 'categories':
							$cats[$countInserts]['categories'] = $value;
							$catSet = true;
							$countCats++;
							break;
						case 'files':
							$fModel = $this->kernel->getContainer()->get('filemanagement.model');
							foreach($value as $file){
								$response = $fModel->getFile($file);
								if(!$response->error->exist){
									$fileSet = true;
									$files[$countInserts]['files'][] = $value;
									$countFiles++;
								}
							}
							break;
						default:
							$entity->$set($value);
							break;
					}
					if ($localeSet) {
						$localizations[$countInserts]['entity'] = $entity;
					}
					if($catSet){
						$cats[$countInserts]['entity'] = $entity;
					}
					if($fileSet){
						$files[$countInserts]['entity'] = $entity;
					}
					$this->em->persist($entity);
					$insertedItems[] = $entity;
					$countInserts++;
				}
			}
		}
		if ($countInserts > 0) {
			$this->em->flush();
		}
		/** Now handle localizations */
		if ($countInserts > 0 && $countLocalizations > 0) {
			$response = $this->insertNewsItemLocalizations($localizations);
		}
		if($countInserts > 0 && $countCats > 0){
			foreach($cats as $cat){
				$response =$this->addNewsItemToCategories($cat['entity'], $cat['categories']);
			}
		}
		if($countInserts > 0 && $countFiles > 0){
			foreach($cats as $cat){
				$response =$this->addFilesToNewsItem($files['entity'], $files['files']);
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name 			insertNewsCategoryLocalizations()
	 *
	 * @since			1.0.2
	 * @version         1.0.4
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertNewsCategoryLocalizations($collection) {
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
		foreach($collection as $data){
			if($data instanceof BundleEntity\NewsCategoryLocalization){
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else{
				$category = $data['entity'];
				foreach($data['localizations'] as $locale => $translation){
					$entity = new BundleEntity\NewsCategoryLocalization();
					$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
					$response = $lModel->getLanguage($locale);
					if($response->error->exist){
						return $response;
					}
					$entity->setLanguage($response->result->set);
					unset($response);
					$entity->setCategory($category);
					foreach($translation as $column => $value){
						$set = 'set'.$this->translateColumnName($column);
						switch($column){
							default:
								if(is_object($value) || is_array($value)){
									$value = json_encode($value);
								}
								$entity->$set($value);
								break;
						}
					}
					$this->em->persist($entity);
					$insertedItems[] = $entity;
					$countInserts++;
				}
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name 			insertNewsItemLocalizations()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertNewsItemLocalizations($collection) {
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
		foreach($collection as $data){
			if($data instanceof BundleEntity\NewsLocalization){
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else{
				$news = $data['entity'];
				foreach($data['localizations'] as $locale => $translation){
					$entity = new BundleEntity\NewsLocalization();
					$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
					$response = $lModel->getLanguage($locale);
					if($response->error->exist){
						return $response;
					}
					$entity->setLanguage($response->result->set);
					unset($response);
					$entity->setNews($news);
					foreach($translation as $column => $value){
						$set = 'set'.$this->translateColumnName($column);
						switch($column){
							default:
								if(is_object($value) || is_array($value)){
									$value = json_encode($value);
								}
								$entity->$set($value);
								break;
						}
					}
					$this->em->persist($entity);
					$insertedItems[] = $entity;
					$countInserts++;
				}
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name 			isFileOfNews()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $item
	 * @param           mixed           $file
	 * @param           bool            $bypass                 if set to true returns the result directly.
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function isFileOfNews($item, $file, $bypass = false) {
		$timeStamp = time();
		$fModel = $this->kernel->getContainer()->get('filemanagement.model');
		$response = $fModel->getFile($file);
		if($response->error->exist){
			return $response;
		}
		$file = $response->result->set;
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$qStr = 'SELECT '.$this->entity['fon']['alias']
			. ' FROM '.$this->entity['fon']['name'].' '.$this->entity['fon']['alias']
			. ' WHERE '.$this->entity['fon']['alias'].'.file = '.$file->getId()
			. ' AND '.$this->entity['fon']['alias'].'.news = '.$item->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();

		$exist = false;
		if (count($result) > 0) {
			$exist = true;
		}
		if ($bypass) {
			return $exist;
		}
		return new ModelResponse($exist, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			isNewsOfCategory()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $item
	 * @param           mixed           $category
	 * @param           bool            $bypass                 if set to true returns the result directly.
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function isNewsOfCategory($item, $category, $bypass = false) {
		$timeStamp = time();
		$response = $this->getNewsCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$qStr = 'SELECT '.$this->entity['con']['alias']
			. ' FROM '.$this->entity['con']['name'].' '.$this->entity['con']['alias']
			. ' WHERE '.$this->entity['con']['alias'].'.category = '.$category->getId()
			. ' AND '.$this->entity['con']['alias'].'.news = '.$item->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();

		$exist = false;
		if (count($result) > 0) {
			$exist = true;
		}
		if ($bypass) {
			return $exist;
		}
		return new ModelResponse($exist, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			listCategoriesOfNews()
	 *
	 * @since			1.0.9
	 * @version         1.0.9
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $item
	 * @param           array           $filter
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listCategoriesOfNews($item, $filter = null, $sortOrder = null, $limit = null) {
		$timeStamp = time();
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$qStr = 'SELECT '.$this->entity['con']['alias']
			. ' FROM '.$this->entity['con']['name'].' '.$this->entity['con']['alias']
			. ' WHERE '.$this->entity['con']['alias'].'.news = '.$item->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();
		$totalRows = count($result);

		$catIds = array();
		if($totalRows > 0){
			foreach($result as $gm){
				$catIds[] = $gm->getCategory()->getId();
			}
		}
		else{
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		$filter[] = array('glue' => 'and',
						  'condition' => array(
							  array(
								  'glue' => 'and',
								  'condition' => array('column' => 'nc.id', 'comparison' => 'in', 'value' => $catIds),
							  )
						  )
		);

		$response = $this->listNewsCategories($filter, $sortOrder, $limit);

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			listFilesOfNews()
	 *
	 * @since			1.0.9
	 * @version         1.0.9
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $item
	 * @param           array           $filter
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listFilesOfNews($item, $filter = array(), $sortOrder = array(), $limit = array()) {
		$timeStamp = time();
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$qStr = 'SELECT '.$this->entity['fon']['alias']
			. ' FROM '.$this->entity['fon']['name'].' '.$this->entity['fon']['alias']
			. ' WHERE '.$this->entity['fon']['alias'].'.news = '.$item->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();
		$totalRows = count($result);

		$fileIds = array();
		if($totalRows > 0){
			foreach($result as $gm){
				$fileIds[] = $gm->getFile()->getId();
			}
		}
		else{
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}

		$filter[] = array('glue' => 'and',
						  'condition' => array(
							  array(
								  'glue' => 'and',
								  'condition' => array('column' => 'f.id', 'comparison' => 'in', 'value' => $fileIds),
							  )
						  )
		);
		$fModel = $this->kernel->getContainer()->get('filemanagement.model');

		$response = $fModel->listFiles($filter, $sortOrder, $limit);

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			listNewsCategories()
	 *
	 * @since			1.0.0
	 * @version         1.0.2
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param   		array   $filter
	 * @param   		array   $sortOrder
	 * @param   		array   $limit
	 *
	 * @return   		BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNewsCategories($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '.$this->entity['nc']['alias'].', '.$this->entity['ncl']['alias']
			.' FROM '.$this->entity['ncl']['name'].' '.$this->entity['ncl']['alias']
			.' JOIN '.$this->entity['ncl']['alias'].'.category '.$this->entity['nc']['alias'];

		if(!is_null($sortOrder)){
			foreach($sortOrder as $column => $direction){
				switch($column){
					case 'id':
					case 'date_added':
					case 'date_updated':
					case 'date_removed':
						$column = $this->entity['n']['alias'].'.'.$column;
						break;
					case 'name':
					case 'url_key':
						$column = $this->entity['nl']['alias'].'.'.$column;
						break;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}

		if(!is_null($filter)){
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);

		$result = $q->getResult();

		$entities = array();
		foreach($result as $entry){
			$id = $entry->getCategory()->getId();
			if(!isset($unique[$id])){
				$entities[] = $entry->getCategory();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			listNewsItems()
	 *
	 * @since			1.0.0
	 * @version         1.0.2
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param   		array   $filter
	 * @param   		array   $sortOrder
	 * @param   		array   $limit
	 *
	 * @return   		BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNewsItems($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '.$this->entity['n']['alias'].', '.$this->entity['nl']['alias']
			.' FROM '.$this->entity['nl']['name'].' '.$this->entity['nl']['alias']
			.' JOIN '.$this->entity['nl']['alias'].'.news '.$this->entity['n']['alias'];

		if(!is_null($sortOrder)){
			foreach($sortOrder as $column => $direction){
				switch($column){
					case 'id':
					case 'date_added':
					case 'date_published':
					case 'date_unpublished':
					case 'sort_order':
					case 'url':
					case 'status':
						$column = $this->entity['n']['alias'].'.'.$column;
						break;
					case 'title':
					case 'url_key':
						$column = $this->entity['nl']['alias'].'.'.$column;
						break;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}

		if(!is_null($filter)){
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;
		$q = $this->em->createQuery($qStr);
		$q = $this->addLimit($q, $limit);
		$result = $q->getResult();

		$entities = array();
		foreach($result as $entry){
			$id = $entry->getNews()->getId();
			if(!isset($unique[$id])){
				$entities[] = $entry->getNews();
			}
			$unique[$id] = $id;
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			listRecentNewsOfCategory()
	 *
	 * @since			1.0.3
	 * @version         1.0.3
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->listNews()
	 *
	 * @param   		mixed   $category
	 * @param   		integer $count
	 * @param   		array	$filter
	 * @param   		array   $sortOrder
	 *
	 * @return   		BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listRecentNewsOfCategory($category, $count = 10, $filter = null, $sortOrder = null){
		$timeStamp = time();
		$response = $this->getNewsCategory($category);
		if($response->error->exist){
			return $response;
		}

		$category = $response->result->set;

		$qStr = 'SELECT '.$this->entity['con']['alias'].', '.$this->entity['n']['alias']
			.' FROM '.$this->entity['con']['name'].' '.$this->entity['con']['alias']
			.' WHERE '.$this->entity['con']['alias'].'.category = '.$category->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();
		if(count($result) < 1 || $result == false){
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		$newsIds = array();
		foreach($result as $conEntity){
			$newsIds[] = $conEntity->getNews()->getId();
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.id', 'comparison' => 'in', 'value' => $newsIds),
				)
			)
		);
		$sortOrder['date_published'] = 'desc';
		$response = $this->listNewsItems($filter, $sortOrder, array('start' => 0, 'count' => $count));

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			listRecentNewsOfCategoryAndSite()
	 *
	 * @since			1.0.3
	 * @version         1.0.3
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->listNews()
	 *
	 * @param   		mixed   $category
	 * @param   		mixed   $site
	 * @param   		integer $count
	 * @param   		array   $filter
	 *
	 * @return   		BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listRecentNewsOfCategoryAndSite($category, $site, $count = 10, $filter = null){
		$timeStamp = time();
		$response = $this->getNewsCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;

		$sModel = new SMMService\SiteManagementModel($this->kernel, $this->dbConnection, $this->orm);
		$response = $sModel->getSite($site);
		if($response->error->exist){
			return $response;
		}
		$site = $response->result->set;
		$qStr = 'SELECT '.$this->entity['con']['alias']
			.' FROM '.$this->entity['con']['name'].' '.$this->entity['con']['alias']
			.' WHERE '.$this->entity['con']['alias'].'.category = '.$category->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();
		if(count($result) < 1 || $result == false){
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		$newsIds = array();
		foreach($result as $conEntity){
			$newsIds[] = $conEntity->getNews()->getId();
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.id', 'comparison' => 'in', 'value' => $newsIds),
				),
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.site', 'comparison' => '=', 'value' => $site->getId()),
				)
			)
		);
		$sortOrder['date_published'] = 'desc';
		$response = $this->listNewsItems($filter, array('date_published' => 'desc'), array('start' => 0, 'count' => $count));

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			listRecentNewsOfCategoryAndSiteWithStatuses()
	 *
	 * @since			1.0.6
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->listNews()
	 *
	 * @param   		mixed   $category
	 * @param   		mixed   $site
	 * @param   		mixed   $statuses
	 * @param   		integer $count
	 * @param   		array   $filter
	 *
	 * @return   		BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listRecentNewsOfCategoryAndSiteWithStatuses($category, $site, $statuses, $count = 10, $filter = null){
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.status', 'comparison' => 'in', 'value' => $statuses),
				)
			)
		);
		return $this->listRecentNewsOfCategoryAndSite($category, $site, $count, $filter);
	}
	/**
	 * @name 			listNewsOfCategory()
	 *
	 * @since			1.0.3
	 * @version         1.0.3
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->listNews()
	 *
	 * @param   		mixed   $category
	 * @param   		array	$filter
	 * @param   		array   $sortOrder
	 * @param   		array   $limit
	 *
	 * @return   		BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNewsOfCategory($category, $filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		$response = $this->getNewsCategory($category);
		if($response->error->exist){
			return $response;
		}

		$category = $response->result->set;

		$qStr = 'SELECT '.$this->entity['con']['alias']
			.' FROM '.$this->entity['con']['name'].' '.$this->entity['con']['alias']
			.' WHERE '.$this->entity['con']['alias'].'.category = '.$category->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();
		if(count($result) < 1 || $result == false){
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		$newsIds = array();
		foreach($result as $conEntity){
			$newsIds[] = $conEntity->getNews()->getId();
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.id', 'comparison' => 'in', 'value' => $newsIds),
				)
			)
		);
		$response = $this->listNewsItems($filter, $sortOrder, $limit);

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			listNewsOfCategoryAndSite()
	 *
	 * @since			1.0.3
	 * @version         1.0.3
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->listNews()
	 *
	 * @param   		mixed   $category
	 * @param   		mixed   $site
	 * @param   		array   $filter
	 * @param   		array   $sortOrder
	 * @param   		array   $limit
	 *
	 * @return   		BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNewsOfCategoryAndSite($category, $site, $filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		$response = $this->getNewsCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$sModel = new SMMService\SiteManagementModel($this->kernel, $this->dbConnection, $this->orm);
		$response = $sModel->getSite($site);
		if($response->error->exist){
			return $response;
		}
		$site = $response->result->set;
		$qStr = 'SELECT '.$this->entity['con']['alias']
			.' FROM '.$this->entity['con']['name'].' '.$this->entity['con']['alias']
			.' WHERE '.$this->entity['con']['alias'].'.category = '.$category->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();
		if(count($result) < 1 || $result == false){
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		$newsIds = array();
		foreach($result as $conEntity){
			$newsIds[] = $conEntity->getNews()->getId();
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.id', 'comparison' => 'in', 'value' => $newsIds),
				),
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.site', 'comparison' => '=', 'value' => $site->getId()),
				),
			)
		);
		$response = $this->listNewsItems($filter, $sortOrder, $limit);

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			listNewsOfCategoryAndSiteWithStatuses()
	 *
	 * @since			1.0.6
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->listNews()
	 *
	 * @param   		mixed   $category
	 * @param   		mixed   $site
	 * @param   		mixed   $statuses
	 * @param   		array   $filter
	 * @param   		array   $sortOrder
	 * @param   		array   $limit
	 *
	 * @return   		BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNewsOfCategoryAndSiteWithStatuses($category, $site, $statuses, $filter = null, $sortOrder = null, $limit = null){
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.status', 'comparison' => 'in', 'value' => $statuses),
				)
			)
		);
		return $this->listNewsOfCategoryAndSite($category, $site, $filter, $sortOrder, $limit);
	}
	/**
	 * @name            markNewsAsDeleted()
	 *
	 * @since           1.0.5
	 * @version         1.0.5
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function markNewsAsDeleted($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		$toUpdate = array();
		foreach ($collection as $news) {
			if(!$news instanceof BundleEntity\News){
				$response = $this->getNewsItem($news);
				if($response->error->exist){
					return $response;
				}
				$news = $response->result->set;
				unset($response);
			}
			$news->setStatus('d');
			$news->setDateRemoved($now);
			$toUpdate[] = $news;
		}
		$response = $this->updateNewsItems($toUpdate);
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name            publishNews()
	 *
	 * @since           1.0.7
	 * @version         1.0.7
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function publishNews($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		$toUpdate = array();
		foreach ($collection as $news) {
			if(!$news instanceof BundleEntity\News){
				$response = $this->getNewsItem($news);
				if($response->error->exist){
					return $response;
				}
				$news = $response->result->set;
				unset($response);
			}
			$news->setStatus('p');
			$news->setDatePublished($now);
			$news->setDateUnpublished(null);
			$toUpdate[] = $news;
		}
		$response = $this->updateNewsItems($toUpdate);
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			removeCategoriesFromNewsItem()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $categories
	 * @param           mixed           $item
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeCategoriesFromNewsItem($categories, $item) {
		$timeStamp = time();
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$idsToRemove = array();
		foreach ($categories as $category) {
			$response = $this->getNewsCategory($category);
			if($response->error->exist){
				return $response;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM ' . $this->entity['con']['name'] . ' ' . $this->entity['con']['alias']
			.' WHERE '.$this->entity['con']['alias'].'.news = '.$item->getId()
			.' AND '.$this->entity['con']['alias'].'.category'.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}
	/**
	 * @name 			removeFilesFromNewsItem()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $files
	 * @param           mixed           $item
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeFilesFromNewsItem($files, $item) {
		$timeStamp = time();
		$response = $this->getNewsItem($item);
		if($response->error->exist){
			return $response;
		}
		$item = $response->result->set;
		$idsToRemove = array();
		$fModel = $this->kernel->getContainer()->get('filemanagement.model');
		foreach ($files as $file) {
			$response = $fModel->getFile($file);
			if($response->error->exist){
				return $response;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM ' . $this->entity['fon']['name'] . ' ' . $this->entity['fon']['alias']
			.' WHERE '.$this->entity['fon']['alias'].'.news = '.$item->getId()
			.' AND '.$this->entity['fon']['alias'].'.file '.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}

	/**
	 * @name 			removeItemsFromNewsCategory()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $items
	 * @param           mixed           $category
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeItemsFromNewsCategory($items, $category) {
		$timeStamp = time();
		$response = $this->getNewsCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$idsToRemove = array();
		foreach ($items as $category) {
			$response = $this->getNewsCategory($category);
			if($response->error->exist){
				return $response;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM ' . $this->entity['con']['name'] . ' ' . $this->entity['con']['alias']
			.' WHERE '.$this->entity['con']['alias'].'.category  = '.$category->getId()
			.' AND '.$this->entity['con']['alias'].'.news '.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}
	/**
	 * @name            unpublishNews()
	 *
	 * @since           1.0.7
	 * @version         1.0.7
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function unpublishNews($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		$toUpdate = array();
		foreach ($collection as $news) {
			if(!$news instanceof BundleEntity\News){
				$response = $this->getNewsItem($news);
				if($response->error->exist){
					return $response;
				}
				$news = $response->result->set;
				unset($response);
			}
			$news->setStatus('u');
			$news->setDateUnpublished($now);
			$toUpdate[] = $news;
		}
		$response = $this->updateNewsItems($toUpdate);
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			updateNewsCategory()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->updateNewsCategories()
	 *
	 * @param           array           $category
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateNewsCategory($category) {
		return $this->updateNewsCategories(array($category));
	}
	/**
	 * @name 			updateNewsCategories()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateNewsCategories($collection){
		$timeStamp = time();
		/** Parameter must be an array */
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = array();
		$localizations = array();
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\NewsCategory) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			}
			else if (is_object($data)) {
				if(!property_exists($data, 'id') || !is_numeric($data->id)){
					return $this->createException('InvalidParameterException', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
				}
				if(!property_exists($data, 'site')){
					$data->site = 1;
				}
				$response = $this->getNewsCategory($data->id);
				if ($response->error->exist) {
					return $this->createException('EntityDoesNotExist', 'Category with id '.$data->id.' does not exist in database.', 'E:D:002');
				}
				$oldEntity = $response->result->set;
				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'local':
							foreach ($value as $langCode => $translation) {
								$localization = $oldEntity->getLocalization($langCode, true);
								$newLocalization = false;
								if (!$localization) {
									$newLocalization = true;
									$localization = new BundleEntity\NewsCategoryLocalization();
									$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
									$response = $mlsModel->getLanguage($langCode);
									$localization->setLanguage($response->result->set);
									$localization->setCategory($oldEntity);
								}
								foreach ($translation as $transCol => $transVal) {
									$transSet = 'set' . $this->translateColumnName($transCol);
									$localization->$transSet($transVal);
								}
								if ($newLocalization) {
									$this->em->persist($localization);
								}
								$localizations[] = $localization;
							}
							$oldEntity->setLocalizations($localizations);
							break;
						case 'site':
							$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
							$response = $sModel->getSite($value);
							if (!$response->error->exist) {
								$oldEntity->$set($response->result->set);
							} else {
								return $this->createException('EntityDoesNotExist', 'The site with the id / key / domain "'.$value.'" does not exist in database.', 'E:D:002');
							}
							unset($response, $sModel);
							break;
						case 'id':
							break;
						default:
							$oldEntity->$set($value);
							break;
					}
					if ($oldEntity->isModified()) {
						$this->em->persist($oldEntity);
						$countUpdates++;
						$updatedItems[] = $oldEntity;
					}
				}
			}
		}
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}
	/**
	 * @name 			updateNewsItem()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->updateNewsItems()
	 *
	 * @param           array           $item
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateNewsItem($item) {
		return $this->updateNewsItems(array($item));
	}
	/**
	 * @name 			updateNewsItems()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateNewsItems($collection){
		$timeStamp = time();
		/** Parameter must be an array */
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = array();
		$localizations = array();
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\News) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			}
			else if (is_object($data)) {
				if(!property_exists($data, 'id') || !is_numeric($data->id)){
					return $this->createException('InvalidParameterException', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
				}
				if(!property_exists($data, 'site')){
					$data->site = 1;
				}
				if(property_exists($data, 'date_ddded')){
					unset($data->date_added);
				}
				$response = $this->getNewsItem($data->id);
				if ($response->error->exist) {
					return $this->createException('EntityDoesNotExist', 'News item with id '.$data->id.' does not exist in database.', 'E:D:002');
				}
				$oldEntity = $response->result->set;
				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'local':
							foreach ($value as $langCode => $translation) {
								$localization = $oldEntity->getLocalization($langCode, true);
								$newLocalization = false;
								if (!$localization) {
									$newLocalization = true;
									$localization = new BundleEntity\NewsLocalization();
									$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
									$response = $mlsModel->getLanguage($langCode);
									$localization->setLanguage($response->result->set);
									$localization->setNews($oldEntity);
								}
								foreach ($translation as $transCol => $transVal) {
									$transSet = 'set' . $this->translateColumnName($transCol);
									$localization->$transSet($transVal);
								}
								if ($newLocalization) {
									$this->em->persist($localization);
								}
								$localizations[] = $localization;
							}
							$oldEntity->setLocalizations($localizations);
							break;
						case 'site':
							$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
							$response = $sModel->getSite($value);
							if (!$response->error->exist) {
								$oldEntity->$set($response->result->set);
							} else {
								return $this->createException('EntityDoesNotExist', 'The site with the id / key / domain "'.$value.'" does not exist in database.', 'E:D:002');
							}
							unset($response, $sModel);
							break;
						case 'id':
							break;
						default:
							$oldEntity->$set($value);
							break;
					}
					if ($oldEntity->isModified()) {
						$this->em->persist($oldEntity);
						$countUpdates++;
						$updatedItems[] = $oldEntity;
					}
				}
			}
		}
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}
	/**
	 * @name listActiveNewsItemsByDateColumnWhichBeforeGivenDate()
	 * @author Said Imamoglu
	 * @since 1.1.3
	 * @version 1.1.3
	 * @param $dateColumn
	 * @param $date
	 * @param $sortOrder
	 * @param $limit
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveNewsItemsByDateColumnWhichBeforeGivenDate($dateColumn,$date,$sortOrder = null,$limit = null){
		$timeStamp = time();
		if (! $date instanceof \DateTime) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'Invalid date object.', $timeStamp, time());
		}
		if (!in_array($dateColumn,array('date_added','date_published','date_unpublished'))) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'Invalid date column.', $timeStamp, time());
		}
		// Prepare SQL conditions
		$filter = array(
			array(
				'glue' => 'and',
				'condition' => array('column' => $this->entity['n']['alias'].'.'.$dateColumn, 'comparison' => '<', 'value' => $date->format('Y-m-d H:i:s')),
			),
			array(
				'glue' => 'and',
				'condition' => array('column' => $this->entity['n']['alias'].'.status', 'comparison' => '!=', 'value' => 'u'),
			)
		);
		$response = $this->listNewsItems($filter,$sortOrder,$limit);
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();
		return $response;
	}
	/**
	 * @name            listNewsOfCategories ()
	 *
	 * @since            1.1.3
	 * @version         1.1.3
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->listNewsItems()
	 *
	 * @param        array $categories
	 * @param        array $filter
	 * @param        array $sortOrder
	 * @param        array $limit
	 *
	 * @return        \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNewsOfCategories($categories, $filter = null, $sortOrder = null, $limit = null)
	{
		$timeStamp = time();
		foreach ($categories as $category) {
			$response = $this->getNewsCategory($category);
			if ($response->error->exist) {
				continue;
			}
			$categoryIds[] = $response->result->set->getId();
		}

		$qStr = 'SELECT ' . $this->entity['con']['alias']
			. ' FROM ' . $this->entity['con']['name'] . ' ' . $this->entity['con']['alias']
			. ' WHERE ' . $this->entity['con']['alias'] . '.category IN ' . implode(',', $categoryIds);

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();
		if (count($result) < 1 || $result == false) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		$newsIds = array();
		foreach ($result as $conEntity) {
			$newsIds[] = $conEntity->getNews()->getId();
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'] . '.id', 'comparison' => 'in', 'value' => $newsIds),
				)
			)
		);
		$response = $this->listNewsItems($filter, $sortOrder, $limit);

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}

	/**
	 * @name  listNewsOfCategoriesWithStatuses ()
	 * @version 1.1.3
	 * @since 1.1.3
	 * @param $categories
	 * @param $statuses
	 * @param $sortOrder
	 * @param $limit
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNewsOfCategoriesWithStatuses($categories, $statuses, $sortOrder, $limit)
	{
		$filter = array();
		$filter[] = array(
			'glue' => 'and',
			'condition' => array('column' => $this->entity['n']['alias'] . '.status', 'comparison' => 'in', 'value' => implode(',', $statuses)),
		);
		return $this->listNewsCategories($categories, $filter, $sortOrder, $limit);
	}
	/**
	 * @name listNewsItemsByDateColumnWhichBeforeGivenDate()
	 * @author Said Imamoglu
	 * @since 1.1.3
	 * @version 1.1.3
	 * @param $dateColumn
	 * @param $date
	 * @param $sortOrder
	 * @param $limit
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNewsItemsByDateColumnWhichBeforeGivenDate($dateColumn,$date,$sortOrder = null,$limit = null){
		$timeStamp = time();
		if (! $date instanceof \DateTime) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'Invalid date object.', $timeStamp, time());
		}
		if (!in_array($dateColumn,array('date_added','date_published','date_unpublished'))) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'Invalid date column.', $timeStamp, time());
		}
		// Prepare SQL conditions
		$filter = array(
			array(
				'glue' => 'and',
				'condition' => array('column' => $this->entity['n']['alias'].'.'.$dateColumn, 'comparison' => '<', 'value' => $date->format('Y-m-d H:i:s')),
			)
		);
		$response = $this->listNewsItems($filter,$sortOrder,$limit);
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();
		return $response;
	}
	/**
	 * @name unPublishNewsOfCategoriesWithStatuses()
	 * @author  Said Imamoglu
	 * @since 1.1.3
	 * @version 1.1.3
	 * @param $categories
	 * @param $statuses
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function unPublishNewsOfCategoriesWithStatuses($categories,$statuses){
		$timeStamp = time();
		$response = $this->listNewsOfCategoriesWithStatuses($categories,$statuses);
		if ($response->error->exist) {
			return $response;
		}
		$response =  $this->unpublishNews($response->result->set);
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();
		return $response;
	}
	/**
	 * @name unPublishNewsItemsByDateColumnWhichBeforeGivenDate()
	 * @author Said Imamoglu
	 * @since 1.1.3
	 * @version 1.1.3
	 * @param $dateColumn
	 * @param $date
	 * @return ModelResponse
	 */
	public function unPublishNewsItemsByDateColumnWhichBeforeGivenDate($dateColumn,$date){
		$timeStamp = time();
		$response = $this->listNewsItemsByDateColumnWhichBeforeGivenDate($dateColumn,$date);
		if ($response->error->exist) {
			return $response;
		}
		$response = $this->unpublishNews($response->result->set);
		if ($response->error->exist) {
			return $response;
		}
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();
		return $response;
	}

	/**
	 * @name unPublishNewsItemsByDateColumnWhichBeforeGivenDate()
	 * @author Said Imamoglu
	 * @since 1.1.3
	 * @version 1.1.3
	 * @param $dateColumn
	 * @param $date
	 * @return ModelResponse
	 */
	public function unPublishActiveNewsItemsByDateColumnWhichBeforeGivenDate($dateColumn,$date){
		$timeStamp = time();
		$response = $this->listActiveNewsItemsByDateColumnWhichBeforeGivenDate($dateColumn,$date);
		if ($response->error->exist) {
			return $response;
		}
		$response = $this->unpublishNews($response->result->set);
		if ($response->error->exist) {
			return $response;
		}
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();
		return $response;
	}

	/**
	 * @param           $site
	 * @param \DateTime $dateStart
	 * @param \DateTime $dateEnd
	 * @param bool      $inclusive
	 * @param null      $sortOrder
	 * @param null      $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|\BiberLtd\Bundle\NewsManagementBundle\Services\BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listNewsOfSitePublishedBetween($site, \DateTime $dateStart, \DateTime $dateEnd, $inclusive = true, $sortOrder = null, $limit = null){
		$timeStamp = time();

		$sModel = new SMMService\SiteManagementModel($this->kernel, $this->dbConnection, $this->orm);
		$response = $sModel->getSite($site);
		if($response->error->exist){
			return $response;
		}
		$site = $response->result->set;

		$lt = '<';
		$gt = '>';

		if($inclusive){
			$lt = $lt.'=';
			$gt = $gt.'=';
		}

		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.date_published', 'comparison' => $gt, 'value' => $dateStart->format('Y-m-d H:i:s')),
				),
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.date_published', 'comparison' => $lt, 'value' => $dateEnd->format('Y-m-d H:i:s')),
				),
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['n']['alias'].'.site', 'comparison' => '=', 'value' => $site->getId()),
				),
			)
		);
		$response = $this->listNewsItems($filter, $sortOrder, $limit);

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listCurrentlyActiveNewsItems(array $filter = null, array $sortOrder = null, array $limit = null){
		$timeStamp = time();
		$date = new \DateTime('now');
		// Prepare SQL conditions
		$filter[] = array(
			'glue' => 'and',
			'condition' => array('column' => $this->entity['n']['alias'].'.date_published', 'comparison' => '<=', 'value' => $date->format('Y-m-d H:i:s')),
		);
		$filter[] =   array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'or',
					'condition' => array('column' => $this->entity['n']['alias'].'.date_unpublished', 'comparison' => '>', 'value' => $date->format('Y-m-d H:i:s'))),
				array(
					'glue' => 'or',
					'condition' => array('column' => 'n.date_unpublished','comparison' => 'null','value' => '')),
			)
		);
		$filter[] = array(
			'glue' => 'and',
			'condition' => array('column' => $this->entity['n']['alias'].'.status', 'comparison' => 'in', 'value' => array('p','a','f')),
		);
		$response = $this->listNewsItems($filter,$sortOrder,$limit);
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();
		return $response;
	}

	/**
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActivePublishYearsOfNewsItems(){
		$timeStamp = time();
		$response = $this->listCurrentlyActiveNewsItems();
		if($response->error->exist){
			return $response;
		}
		$years = array();
		foreach($response->result->set as $newsItem){
			$years[$newsItem->getDatePublished()->format('Y')] = $newsItem->getDatePublished()->format('Y');
		}
		$response->result->set = $years;
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
	}

	/**
	 * @param integer $year
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|\BiberLtd\Bundle\NewsManagementBundle\Services\BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveNewsItemsInYear($year, array $filter = null, array $sortOrder = null, array $limit = null){
		$timeStamp = time();
		$response = $this->listCurrentlyActiveNewsItems($filter, $sortOrder, $limit);
		if($response->error->exist){
			return $response;
		}

		$newSet = array();
		foreach($response->result->set as $newsItem){
			if($newsItem->getDatePublished()->format('Y') == $year){
				$newSet[] = $newsItem;
			}
		}

		$response->result->set = $newSet;
		$response->result->count->set = count($newSet);
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();
		return $response;
	}
	/**
	 * @param integer $month
	 * @param integer $year
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|\BiberLtd\Bundle\NewsManagementBundle\Services\BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveNewsItemsInMonthOfYear($month, $year, array $filter = null, array $sortOrder = null, array $limit = null){
		$timeStamp = time();
		$response = $this->listCurrentlyActiveNewsItems($filter, $sortOrder, $limit);
		if($response->error->exist){
			return $response;
		}

		$newSet = array();
		foreach($response->result->set as $newsItem){
			if($newsItem->getDatePublished()->format('Y') == $year && $newsItem->getDatePublished()->format('n') == $month){
				$newSet[] = $newsItem;
			}
		}

		$response->result->set = $newSet;
		$response->result->count->set = count($newSet);
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();
		return $response;
	}

	/**
	 * @param string|null $status
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function setAllPopupsTo($status = 'n'){
		$timeStamp = time();
		$qStr = 'UPDATE '.$this->entity['n']['name'].' '.$this->entity['n']['alias'].' SET '.$this->entity['n']['alias'].'.popup = "'.$status.'"';

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$updated = true;
		if (!$result) {
			$updated = false;
		}
		if ($updated) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully updated.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Selected entries cannot be updated at the moment.', $timeStamp, time());
	}

	/**
	 * @param string|null $status
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function setAllPopupsExceptTo($excludedItem, $status = 'n'){
		$timeStamp = time();
		$response = $this->getNewsItem($excludedItem);
		if($response->error->exist){
			return $response;
		}
		$newsItem = $response->result->set;
		$qStr = 'UPDATE '.$this->entity['n']['name'].' '.$this->entity['n']['alias'].' SET '.$this->entity['n']['alias'].'.popup = "'.$status.'" WHERE '.$this->entity['n']['alias'].'.id <> '.$newsItem->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();

		$updated = true;
		if (!$result) {
			$updated = false;
		}
		if ($updated) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully updated.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Selected entries cannot be updated at the moment.', $timeStamp, time());
	}

	/**
	 * @param            $status
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveNewsWithPopupStatus($status, array $filter = null, array $sortOrder = null, array $limit = null){
		$timeStamp = time();
		$filter[] = array(
			'glue' => 'and',
			'condition' => array('column' => $this->entity['n']['alias'].'.popup', 'comparison' => '=', 'value' => $status),
		);
		return $this->listNewsItems($filter, $sortOrder, $limit);
	}
}
/**
 * Change Log
 * **************************************
 * v1.1.2                      06.07.2015
 * Can Berkol
 * **************************************
 * BF :: 3783696 :: insertNewsItems() "persist" issues fixed.
 *
 * **************************************
 * v1.1.1                      19.06.2015
 * Can Berkol
 * **************************************
 * BF :: getNewsByUrlKey() was returning array.
 *
 * **************************************
 * v1.1.0                      18.06.2015
 * Can Berkol
 * **************************************
 * BF :: getLastAddedFileOfNews() has an invalid error check. Fixed.
 *
 * **************************************
 * v1.0.9                      16.06.2015
 * Can Berkol
 * **************************************
 * FR :: addFilesToNews() added.
 * FR :: getLastAddedFileOfNews() added.
 * FR :: removeFilesFromNews() added.
 * FR :: listFilesOfNews() added.
 *
 * **************************************
 * v1.0.8                      15.06.2015
 * Can Berkol
 * **************************************
 * CR :: insertNewsItems() now supports author field.
 *
 * **************************************
 * v1.0.7                      13.06.2015
 * Can Berkol
 * **************************************
 * FR :: publishNews) added.
 * FR :: unpublishNews() added.
 *
 * **************************************
 * v1.0.6                      13.06.2015
 * Can Berkol
 * **************************************
 * CR :: getNewsCategory() now can get category by url key..
 * CR :: getNewsItem() now can get news item by url key..
 * FR :: getNewsCategoryByUrlKey() added.
 * FR :: getNewsItemByUrlKey() added.
 * FR :: listNewsOfCategoryAndSiteWithStatuses() added.
 * FR :: listRecentNewsOfCategoryAndSiteWithStatuses() added.
 *
 * **************************************
 * v1.0.5                      11.06.2015
 * Can Berkol
 * **************************************
 * BF :: insertNewsLocalizations() rewritten.
 * BF :: insertNewsCategoryLocalizations() rewritten.
 * FR :: markNewsAsDeleted() added.
 *
 * **************************************
 * v1.0.4                      10.06.2015
 * Can Berkol
 * **************************************
 * BF :: getNewsCategory() has copy/paste errors.
 *
 * **************************************
 * v1.0.3                      09.06.2015
 * Can Berkol
 * **************************************
 * BF :: ModelResponse added in use statement.
 * FR :: listRecentNewsOfCategory()
 * FR :: listRecentNewsOfCategoryAndSite()
 * FR :: listNewsOfCategory()
 * FR :: listNewsOfCategoryAndSite()
 *
 * **************************************
 * v1.0.2                      03.05.2015
 * Can Berkol
 * **************************************
 * CR :: Made compatible with CoreBundle v3.3.
 *
 * **************************************
 * v1.0.1                      Said İmamoğlu
 * 26.12.2014
 * **************************************
 * U listNewsItems()
 **/