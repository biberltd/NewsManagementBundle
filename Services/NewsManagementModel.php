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
 * @version     1.0.3
 * @date        09.06.2015
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
	 * @use             $this->isFileOfNewsItem()
	 * @use             $this->createException()
	 *
	 * @param           mixed           $item
	 * @param           array           $files
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addFilesToNewsItems($item, $files) {
		$timeStamp = time();
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
			if (!$this->isFileOfNewsItem($item, $file, true)) {
				$toAdd[] = $file;
			}
		}
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
		$insertedItems = array();
		foreach ($toAdd as $file) {
			$entity = new BundleEntity\FilesOfNews();
			$entity->setFile($file)->setNews($item)->setDateAdded($now);
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
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function addNewsToCategories($item, $categories) {
		$timeStamp = time();
		$response = $this->getNewsItem($item);
		if($response->error->exist){
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
			$entity->setCategory($cat)->setNews($item)->setDateAdded($now);
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
				if(!$response->error->exists){
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

		if ($response->error->exists) {
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
	 * @name 			getNewsCategory()
	 *
	 * @since			1.0.2
	 * @version         1.0.2
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
		switch($news){
			case is_numeric($news):
				$result = $this->em->getRepository($this->entity['nc']['name'])->findOneBy(array('id' => $news));
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
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
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
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
	 * @param           mixed           $member
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertNewsItem($member) {
		return $this->insertNewsItems(array($member));
	}
	/**
	 * @name 			insertNewsItems()
	 *
	 * @since			1.0.0
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertNewsItems($collection)	{
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
	 * @version         1.0.2
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
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
			else if(is_object($data)){
				$entity = new BundleEntity\NewsCategoryLocalization();
				foreach($data as $column => $value){
					$set = 'set'.$this->translateColumnName($column);
					switch($column){
						case 'language':
							$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
							$response = $lModel->getLanguage($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						case 'category':
							$response = $this->getNewsCategory($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						default:
							$entity->$set($value);
							break;
					}
				}
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name 			insertNewsLocalizations()
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
	public function insertNewsLocalizations($collection) {
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
			else if(is_object($data)){
				$entity = new BundleEntity\NewsLocalization();
				foreach($data as $column => $value){
					$set = 'set'.$this->translateColumnName($column);
					switch($column){
						case 'language':
							$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
							$response = $lModel->getLanguage($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						case 'news':
							$response = $this->getNewsItem($value);
							if(!$response->error->exists){
								$entity->$set($response->result->set);
							}
							unset($response, $lModel);
							break;
						default:
							$entity->$set($value);
							break;
					}
				}
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
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
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
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
			. ' FROM '.$this->entity['fon']['name'].' '.$this->entity['con']['alias']
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
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
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

		$qStr = 'SELECT '.$this->entity['nc']['alias'].', '.$this->entity['nc']['alias']
			.' FROM '.$this->entity['ncl']['name'].' '.$this->entity['ncl']['alias']
			.' JOIN '.$this->entity['ncl']['alias'].'.member '.$this->entity['nc']['alias'];

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
			$id = $entry->getMember()->getId();
			if(!isset($unique[$id])){
				$entities[] = $entry->getMember();
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

		$qStr = 'SELECT '.$this->entity['n']['alias'].', '.$this->entity['n']['alias']
			.' FROM '.$this->entity['nl']['name'].' '.$this->entity['nl']['alias']
			.' JOIN '.$this->entity['nl']['alias'].'.member '.$this->entity['n']['alias'];

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
			$id = $entry->getMember()->getId();
			if(!isset($unique[$id])){
				$entities[] = $entry->getMember();
			}
		}
		$totalRows = count($entities);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($entities, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
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
		$response = $this->listNewsItems($filter, $sortOrder, $limit);

		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = time();

		return $response;
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
		$qStr = 'SELECT '.$this->entity['con']['alias'].', '.$this->entity['n']['alias']
			.' FROM '.$this->entity['con']['name'].' '.$this->entity['con']['alias']
			.' WHERE '.$this->entity['con']['alias'].'.category = '.$category->getId()
			.' AND '.$this->entity['con']['alias'].'.site = '.$site->getId();

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
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
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
	 * @return          BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
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
}
/**
 * Change Log
 * **************************************
 * v1.0.3                      09.06.2015
 * Can Berkol
 * **************************************
 * BF :: ModelResponse added in use statement.
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