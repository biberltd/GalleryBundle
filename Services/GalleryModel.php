<?php
/**
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        23.12.2015
 */
namespace BiberLtd\Bundle\GalleryBundle\Services;

use BiberLtd\Bundle\CoreBundle\CoreModel;
use BiberLtd\Bundle\CoreBundle\Responses\ModelResponse;
use BiberLtd\Bundle\GalleryBundle\Entity as BundleEntity;
use BiberLtd\Bundle\FileManagementBundle\Entity as FileBundleEntity;
use BiberLtd\Bundle\MultiLanguageSupportBundle\Entity as MLSEntity;
use BiberLtd\Bundle\GalleryBundle\Services as SMMService;
use BiberLtd\Bundle\FileManagementBundle\Services as FMMService;
use BiberLtd\Bundle\CoreBundle\Services as CoreServices;
use BiberLtd\Bundle\CoreBundle\Exceptions as CoreExceptions;

class GalleryModel extends CoreModel {
	/**
	 * GalleryModel constructor.
	 *
	 * @param object $kernel
	 * @param string $dbConnection
	 * @param string $orm
	 */
	public function __construct($kernel, $dbConnection = 'default', $orm = 'doctrine') {
		parent::__construct($kernel, $dbConnection, $orm);

		$this->entity = array(
			'agl' 		=> array('name' => 'GalleryBundle:ActiveGalleryLocale', 'alias' => 'agl'),
			'agcl' 		=> array('name' => 'GalleryBundle:ActiveGalleryCategoryLocale', 'alias' => 'agcl'),
			'cog'		=> array('name' => 'GalleryBundle:CategoriesOfGallery', 'alias' => 'cog'),
			'f' 		=> array('name' => 'FileManagementBundle:File', 'alias' => 'f'),
			'g' 		=> array('name' => 'GalleryBundle:Gallery', 'alias' => 'g'),
			'gc' 		=> array('name' => 'GalleryBundle:GalleryCategory', 'alias' => 'gc'),
			'gcl' 		=> array('name' => 'GalleryBundle:GalleryCategoryLocalization', 'alias' => 'gcl'),
			'gl' 		=> array('name' => 'GalleryBundle:GalleryLocalization', 'alias' => 'gl'),
			'gm' 		=> array('name' => 'GalleryBundle:GalleryMedia', 'alias' => 'gm'),
		);
	}

	/**
	 *
	 */
	public function __destruct() {
		foreach ($this as $property => $value) {
			$this->$property = null;
		}
	}

	/**
	 * @param mixed $file
	 * @param mixed $gallery
	 *
	 * @return array
	 */
	public function addFileToGallery($file, $gallery) {
		return $this->addFilesToGallery(array($file), $gallery);
	}

	/**
	 * @param array $files
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|mixed
	 */
	public function addFilesToGallery(array $files, $gallery) {
		$timeStamp = microtime(true);
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$fModel = new FMMService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);

		$fogCollection = [];
		$count = 0;
		$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($files as $entity){
			if(!isset($entity['sort_order']) && isset($entity['sortorder'])){
				$entity['sort_order'] = $entity['sortorder'];
			}
			$response = $fModel->getFile($entity['file']);
			if($response->error->exist){
				return $response;
			}
			$entity['file'] = $response->result->set;
			if (!$this->isFileAssociatedWithGallery($entity['file']->getId(), $gallery, true)){
				$galleryMedia = new BundleEntity\GalleryMedia();
				$galleryMedia->setFile($entity['file'])->setGallery($gallery)->setDateAdded($now);
				if (!is_null($entity['sort_order'])) {
					$galleryMedia->setSortOrder($entity['sort_order']);
				}
				else {
					$galleryMedia->setSortOrder($this->getMaxSortOrderOfGalleryMedia($gallery, true) + 1);
				}
				$galleryMedia->setCountView(0)->setType($entity['file']->getType());
				if(!isset($entity['status'])){
					$galleryMedia->setStatus('p');
				}
				$this->em->persist($galleryMedia);
				$count++;
				$fogCollection[] = $galleryMedia;
			}
		}
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($fogCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $locales
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|mixed
	 */
	public function addLocalesToGallery(array $locales, $gallery){
		$timeStamp = microtime(true);
		$response = $this->getGallery($gallery);

		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$aglCollection = [];
		$count = 0;
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if($response->error->exist){
				return $response;
			}
			$locale = $response->result->set;
			unset($response);
			/** If no entity s provided as file we need to check if it does exist */
			/** Check if association exists */
			if(!$this->isLocaleAssociatedWithGallery($locale, $gallery, true)) {
				$agl = new BundleEntity\ActiveGalleryLocale();
				$agl->setLanguage($locale)->setGallery($gallery);
				$this->em->persist($agl);
				$aglCollection[] = $agl;
				$count++;
			}
		}
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($aglCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $locales
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|mixed
	 */
	public function addLocalesToGalleryCategory(array $locales, $category){
		$timeStamp = microtime(true);
		$response = $this->getGalleryCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		unset($response);
		$aglCollection = [];
		$count = 0;
		/** Start persisting locales */
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if($response->error->exist){
				return $response;
			}
			$locale = $response->result->set;
			unset($response);
			/** If no entity s provided as file we need to check if it does exist */
			/** Check if association exists */
			if(!$this->isLocaleAssociatedWithGalleryCategory($locale, $category, true)) {
				$agl = new BundleEntity\ActiveGalleryCategoryLocale();
				$agl->setLanguage($locale)->setGalleryCategory($category);
				$this->em->persist($agl);
				$aglCollection[] = $agl;
				$count++;
			}
		}
		if($count > 0){
			$this->em->flush();
			return new ModelResponse($aglCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function countDistinctMediaTotal() {
		$timeStamp = microtime(true);
		$qStr = 'SELECT COUNT( DISTINCT '. $this->entity['gm']['alias'].'.file)'
			.' FROM '.$this->entity['gm']['name'].' '.$this->entity['gm']['alias'];

		$q = $this->em->createQuery($qStr);

		$result = $q->getSingleScalarResult();

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $gallery
	 *
	 * @return array
	 */
	public function countTotalAudioInGallery($gallery){
		return $this->countTotalMediaInGallery($gallery, 'a');
	}

	/**
	 * @param mixed $gallery
	 *
	 * @return array
	 */
	public function countTotalDocumentsInGallery($gallery){
		return $this->countTotalMediaInGallery($gallery, 'd');
	}

	/**
	 * @param mixed $gallery
	 *
	 * @return array
	 */
	public function countTotalImagesInGallery($gallery){
		return $this->countTotalMediaInGallery($gallery, 'i');
	}
	/**
	 * @param mixed $gallery
	 * @param string $mediaType  all|i|a|v|f|d|p|s
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|mixed
	 */
	public function countTotalMediaInGallery($gallery, string $mediaType = 'all'){
		$timeStamp = microtime(true);
		$allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
		if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
			return $this->createException('InvalidParameter', '$mediaType parameter can only have one of the following values: '.implode(', ',$allowedTypes), 'err.invalid.parameter.collection');
		}
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$qStr = 'SELECT COUNT('.$this->entity['gm']['alias'].'.file)'
			.' FROM '.$this->entity['gm']['name'].' '.$this->entity['gm']['alias']
			.' WHERE '.$this->entity['gm']['alias'].'.gallery = '.$gallery->getId();
		unset($response, $gallery);
		$wStr = '';
		if($mediaType != 'all'){
			$whereStr = ' AND '.$this->entity['gm']['alias'].".type = '".$mediaType."'";
		}
		$qStr .= $wStr;

		$query = $this->em->createQuery($qStr);

		$result = $query->getSingleScalarResult();
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|mixed
	 */
	public function countTotalVideoInGallery($gallery){
		return $this->countTotalMediaInGallery($gallery, 'v');
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteGalleries(array $collection) {
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\Gallery){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getGallery($entry);
				if(!$response->error->exists){
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteGallery($gallery) {
		return $this->deleteGalleries(array($gallery));
	}

	/**
	 * @param mixed $gallery
	 * @param bool $bypass
	 *
	 * @return bool|mixed
	 */
	public function doesGalleryExist($gallery, bool $bypass = false) {
		$response = $this->getGallery($gallery);
		$exist = true;
		if($response->error->exist){
			$exist = false;
			$response->result->set = false;
		}
		if($bypass){
			return $exist;
		}
		return $response;
	}

	/**
	 * @param mixed $galleryMedia
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool|mixed
	 */
	public function doesGalleryMediaExist($galleryMedia, bool $bypass = false) {
		$timeStamp = microtime(true);
		$exist = false;

		$fModel = $this->kernel->getContainer()->get('filemanagement.model');
		$response = $fModel->getFile($galleryMedia['file']);
		if($response->error->exist){
			return $response;
		}
		$file = $response->result->set;
		$response = $this->getGallery($galleryMedia['gallery']);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$response = $this->getGalleryMedia($file, $gallery);
		if(!$response->error->exist){
			$exist = true;
		}
		if ($bypass) {
			return $exist;
		}
		return new ModelResponse($exist, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getGallery($gallery) {
		$timeStamp = microtime(true);
		if($gallery instanceof BundleEntity\Gallery){
			return new ModelResponse($gallery, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
		}
		$result = null;
		switch($gallery){
			case is_numeric($gallery):
				$result = $this->em->getRepository($this->entity['g']['name'])->findOneBy(array('id' => $gallery));
				break;
			case is_string($gallery):
				$response = $this->getGalleryByUrlKey($gallery);
				if(!$response->error->exist){
					$result = $response->result->set;
				}
				unset($response);
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param string $urlKey
	 * @param mixed|null   $language
	 *
	 * @return array|\BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getGalleryByUrlKey(string $urlKey, $language = null){
		$timeStamp = microtime(true);
		if(!is_string($urlKey)){
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['gl']['alias'].'.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if(!is_null($language)){
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if(!$response->error->exists){
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['gl']['alias'].'.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listGalleries($filter, null, array('start' => 0, 'count' => 1));

		$response->result->set = $response->result->set[0];
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = microtime(true);

		return $response;
	}

	/**
	 * @param mixed $file
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getGalleryMedia($file, $gallery) {
		$timeStamp = microtime(true);
		$fModel = $this->kernel->getContainer()->get('filemanagement.model');
		$response = $fModel->getFile($file);
		if($response->error->exist){
			return $response;
		}
		$file = $response->result->set;
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$qStr = 'SELECT '.$this->entity['gallery_media']['alias']
			.' FROM '.$this->entity['gallery_media']['name'].' '.$this->entity['gallery_media']['alias']
			.' WHERE '.$this->entity['gallery_media']['alias'].'.gallery = '.$gallery->getId()
			.' AND '.$this->entity['gallery_media']['alias'].'.file = '.$file->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getSingleResult();

		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));

	}

	/**
	 * @param mixed $gallery
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getMaxSortOrderOfGalleryMedia($gallery, bool $bypass = false) {
		$timeStamp = microtime(true);
		if (!is_object($gallery) && !is_numeric($gallery) && !is_string($gallery)) {
			return $this->createException('InvalidParameterException', 'Gallery', 'err.invalid.parameter.product');
		}
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$q_str = 'SELECT MAX('.$this->entity['gm']['alias'].'.sort_order) FROM ' . $this->entity['gm']['name'] .' '. $this->entity['gm']['alias']
			. ' WHERE ' . $this->entity['gm']['alias'] . '.gallery = ' . $gallery->getId();
		$query = $this->em->createQuery($q_str);
		$result = $query->getSingleScalarResult();

		if ($bypass) {
			return $result;
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertGalleries(array $collection) {
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$countLocalizations = 0;
		$insertedItems = [];
		foreach($collection as $data){
			if($data instanceof BundleEntity\Gallery){
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else if(is_object($data)){
				$localizations = [];
				$entity = new BundleEntity\Gallery;
				if(!property_exists($data, 'date_added')){
					$data->date_added = $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
				}
				if(!property_exists($data, 'date_published')){
					$data->date_published = $data->date_added;
				}
				if(!property_exists($data, 'site')){
					$data->site = 1;
				}
				/**
				 * Default data for count columns
				 */
				$requiredFields = array('count_media', 'count_image', 'count_audio', 'count_video', 'count_audio', 'count_document');
				foreach($requiredFields as $field){
					if(!property_exists($data, $field)){
						$data->$field = 0;
					}
				}
				foreach($data as $column => $value){
					$localeSet = false;
					$set = 'set'.$this->translateColumnName($column);
					switch($column){
						case 'local':
							$localizations[$countInserts]['localizations'] = $value;
							$localeSet = true;
							$countLocalizations++;
							break;
						case 'site':
							$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
							$response = $sModel->getSite($value, 'id');
							if(!$response['error']){
								$entity->$set($response['result']['set']);
							}
							else{
								new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
							}
							unset($response, $sModel);
							break;
						case 'preview_file':
							$fModel = $this->kernel->getContainer()->get('filemanagement.model');
							$response = $fModel->getFile($value);
							if($response->error->exist){
								return $response;
							}
							$entity->$set($response['result']['set']);
							unset($response, $fModel);
							break;
						default:
							$entity->$set($value);
							break;
					}
					if($localeSet){
						$localizations[$countInserts]['entity'] = $entity;
					}
				}
				$this->em->persist($entity);
				$insertedItems[] = $entity;

				$countInserts++;
			}
		}
		if($countInserts > 0){
			$this->em->flush();
		}
		/** Now handle localizations */
		if($countInserts > 0 && $countLocalizations > 0){
			$this->insertGalleryLocalizations($localizations);
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));

	}

	/**
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertGallery($gallery) {
		return $this->insertGalleries(array($gallery));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertGalleryLocalizations(array $collection){
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = [];
		foreach($collection as $item){
			if($item instanceof BundleEntity\GalleryLocalization){
				$entity = $item;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else{
				foreach($item['localizations'] as $language => $data){
					$entity = new BundleEntity\GalleryLocalization;
					$entity->setGallery($item['entity']);
					$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
					$response = $mlsModel->getLanguage($language, 'iso_code');
					if($response->error->exist){
						return $response;
					}
					$entity->setLanguage($response->result->set);
					foreach($data as $column => $value){
						$set = 'set'.$this->translateColumnName($column);
						$entity->$set($value);
					}
					$this->em->persist($entity);
					$insertedItems[] = $entity;
					$countInserts++;
				}
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $file
	 * @param mixed $gallery
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function isFileAssociatedWithGallery($file, $gallery, bool $bypass = false) {
		$timeStamp = microtime(true);
		$fModel = new FMMService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);
		$response = $fModel->getFile($file);
		if($response->error->exist){
			return $response;
		}
		$file = $response->result->set;
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['gm']['alias'] . '.file)'
			. ' FROM ' . $this->entity['gm']['name'] . ' ' . $this->entity['gm']['alias']
			. ' WHERE ' . $this->entity['gm']['alias'] . '.file = ' . $file->getId()
			. ' AND ' . $this->entity['gm']['alias'] . '.gallery = ' . $gallery->getId();
		$q = $this->em->createQuery($qStr);

		$result = $q->getSingleScalarResult();

		if ($result > 0) {
			$found = true;
		}
		if ($bypass) {
			return $found;
		}
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $locale
	 * @param mixed $gallery
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function isLocaleAssociatedWithGallery($locale, $gallery, bool $bypass = false){
		$timeStamp = microtime(true);
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if($response->error->exist){
			return $response;
		}
		$locale = $response->result->set;
		unset($response);
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['agl']['alias'] . '.gallery)'
			. ' FROM ' . $this->entity['agl']['name'] . ' ' . $this->entity['agl']['alias']
			. ' WHERE ' . $this->entity['agl']['alias'] . '.language = ' . $locale->getId()
			. ' AND ' . $this->entity['agl']['alias'] . '.gallery = ' . $gallery->getId();
		$q = $this->em->createQuery($qStr);

		$result = $q->getSingleScalarResult();

		if ($result > 0) {
			$found = true;
		}
		if ($bypass) {
			return $found;
		}
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $locale
	 * @param mixed $gallery
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function isLocaleAssociatedWithGalleryCategory($locale, $category, bool $bypass = false){
		$timeStamp = microtime(true);
		$response = $this->getGalleryCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale);
		if($response->error->exist){
			return $response;
		}
		$locale = $response->result->set;
		unset($response);
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['agcl']['alias'] . '.category)'
			. ' FROM ' . $this->entity['agcl']['name'] . ' ' . $this->entity['agcl']['alias']
			. ' WHERE ' . $this->entity['agcl']['alias'] . '.language = ' . $locale->getId()
			. ' AND ' . $this->entity['agcl']['alias'] . '.category = ' . $category->getId();
		$q = $this->em->createQuery($qStr);

		$result = $q->getSingleScalarResult();

		if ($result > 0) {
			$found = true;
		}
		if ($bypass) {
			return $found;
		}
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listActiveLocalesOfGallery($gallery){
		$timeStamp = microtime(true);
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$qStr = 'SELECT ' . $this->entity['agl']['alias']
			. ' FROM ' . $this->entity['agl']['name'] . ' ' . $this->entity['agl']['alias']
			. ' WHERE ' . $this->entity['agl']['alias'] . '.gallery = ' . $gallery->getId();
		$query = $this->em->createQuery($qStr);

		$result = $query->getResult();
		$locales = [];
		$unique = [];
		foreach ($result as $entry) {
			$id = $entry->getLanguage()->getId();
			if (!isset($unique[$id])) {
				$locales[] = $entry->getLanguage();
				$unique[$id] = $entry->getLanguage();
			}
		}
		unset($unique);
		$totalRows = count($locales);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($locales, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|mixed
	 */
	public function listActiveLocalesOfGalleryCategory($category){
		$timeStamp = microtime(true);
		$response = $this->getGalleryCategory($category);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$qStr = 'SELECT ' . $this->entity['agcl']['alias']
			. ' FROM ' . $this->entity['agcl']['name'] . ' ' . $this->entity['agcl']['alias']
			. ' WHERE ' . $this->entity['agcl']['alias'] . '.category = ' . $category->getId();
		$query = $this->em->createQuery($qStr);
		$result = $query->getResult();
		$locales = [];
		$unique = [];
		foreach ($result as $entry) {
			$id = $entry->getLanguage()->getId();
			if (!isset($unique[$id])) {
				$locales[] = $entry->getLanguage();
				$unique[$id] = '';
			}
		}
		unset($unique);
		$totalRows = count($locales);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($locales, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $gallery
	 * @param int|null $sortOrder
	 *
	 * @return array
	 */
	public function listAllAudioOfGallery($gallery, int $sortOrder = null) {
		return $this->listMediaOfGallery($gallery, 'a', $sortOrder);
	}

	/**
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listAllGalleries(array $sortOrder = null, array $limit = null) {
		return $this->listGalleries(null, $sortOrder, $limit);
	}

	/**
	 * @param mixed $gallery
	 * @param array|null $sortOrder
	 *
	 * @return array
	 */
	public function listAllImagesOfGallery($gallery, array $sortOrder = null) {
		return $this->listMediaOfGallery($gallery, 'i', $sortOrder);
	}

	/**
	 * @param mixed $gallery
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listDocumentsOfGallery($gallery, array $sortOrder = null, array $limit = null) {
		return $this->listMediaOfGallery($gallery, 'd', $sortOrder, $limit);
	}

	/**
	 * @param mixed $gallery
	 * @param array|null $sortOrder
	 *
	 * @return array
	 */
	public function listAllVideosOfGallery($gallery, array $sortOrder = null) {
		return $this->listMediaOfGallery($gallery, 'v', $sortOrder);
	}
	/**
	 * @name 			listGalleries()
	 *
	 * @since			1.0.1
	 * @version         1.1.8
	 * @author          Can Berkol
	 *
	 * @use             $this->createException
	 *
	 * @param           array           $filter
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleries($filter = null, $sortOrder = null, $limit = null) {
		$timeStamp = microtime(true);
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['gl']['alias']
			. ' FROM ' . $this->entity['gl']['name'] . ' ' . $this->entity['gl']['alias']
			. ' JOIN ' . $this->entity['gl']['alias'] . '.gallery ' . $this->entity['g']['alias'];

		if ($sortOrder != null) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'id':
					case 'date_added':
					case 'date_updated':
					case 'date_published':
					case 'date_unpublished':
					case 'count_media':
					case 'count_image':
					case 'count_video':
					case 'count_document':
					case 'site':
					case 'sort_order':
						$column = $this->entity['g']['alias'] . '.' . $column;
						break;
					case 'tile':
					case 'url_key':
					case 'description':
						$column = $this->entity['gl']['alias'] . '.' . $column;
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

		$galleries = [];
		$unique = [];
		foreach ($result as $entry) {
			$id = $entry->getGallery()->getId();
			if (!isset($unique[$id])) {
				$galleries[] = $entry->getGallery();
				$unique[$id] = $entry->getGallery();
			}
		}
		unset($unique);
		$totalRows = count($galleries);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($galleries, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleryAddedAfter(\DateTime $date, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesAdded($date, 'after', $sortOrder, $limit);
	}

	/**
	 * @param mixed $gallery
	 * @param string     $mediaType i|a|v|f|d|p|s
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 * @param array|null $filter
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listGalleryMedia($gallery, string $mediaType = 'all', array $sortOrder = null, array $limit = null, array $filter = null){
		$timeStamp = microtime(true);
		$allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
		if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
			return $this->createException('InvalidParameter', '$mediaType parameter can only have one of the following values: '.implode(', ',$allowedTypes), 'err.invalid.parameter.collection');
		}
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$qStr = 'SELECT '.$this->entity['gm']['alias']
			.' FROM '.$this->entity['gm']['name'].' '.$this->entity['gm']['alias']
			.' WHERE '.$this->entity['gm']['alias'].'.gallery = '.$gallery->getId();
		unset($response, $gallery);
		$whereStr = '';
		if($mediaType != 'all'){
			$whereStr = ' AND '.$this->entity['gm']['alias'].".type = '".$mediaType."'";
		}
		$qStr .= $whereStr;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();
		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesAddedBefore(\DateTime $date, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesAdded($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @param array      $dates
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesAddedBetween(array $dates, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesAdded($dates, 'between', $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesUpdatedAfter(\DateTime $date, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesUpdated($date, 'after', $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesUpdatedBefore(\DateTime $date, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesUpdated($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @param array      $dates
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesUpdatedBetween(array $dates, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesUpdated($dates, 'between', $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesUnpublishedAfter(\DateTime $date, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesUnpublished($date, 'after', $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesUnpublishedBefore(\DateTime $date, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesUnpublished($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @param array      $dates
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesUnpublishedBetween(array $dates, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesUnpublished($dates, 'between', $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 * @param array|null $filter
	 *
	 * @return array
	 */
	public function listImagesOfAllGalleries(int $count = 1, array $sortOrder = null, array $limit = null, array $filter = null){
		return $this->listMediaOfAllGalleries($count, 'i', $sortOrder, $limit, $filter);
	}

	/**
	 * @param            $file
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 * @param array|null $filter
	 *
	 * @return array|\BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listGalleriesOfMedia($file, array $sortOrder = null, array $limit = null, array $filter = null){
		$timeStamp = microtime(true);
		$fModel = $this->kernel->getContainer()->get('filemanagement.model');
		$response = $fModel->getFile($file);
		if($response->error->exist){
			return $response;
		}
		$file = $response->result->set;
		$qStr = 'SELECT '.$this->entity['gm']['alias']
			.' FROM '.$this->entity['gm']['name'].' '.$this->entity['gm']['alias']
			.' WHERE '.$this->entity['gm']['alias'].'.file = '.$file->getId();
		unset($response, $file);

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();

		$galleryIds = [];
		$totalRows = count($result);

		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}

		foreach($result as $gm){
			$galleryIds[] = $gm->getGallery()->getId();
			$this->em->detach($gm);
		}


		$filter[] = array('glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => 'g.id', 'comparison' => 'in', 'value' => $galleryIds),
				)
			)
		);
		return $this->listGalleries($filter, $sortOrder, $limit);
	}

	/**
	 * @param mixed $site
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesOfSite($site, array $sortOrder = null, array $limit = null) {
		$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
		$response = $sModel->getSite($site);
		if($response->error->exist){
			return $response;
		}
		$site = $response->result->set;
		unset($response);
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['g']['alias'] . '.site', 'comparison' => '=', 'value' => $site->getId()),
				)
			)
		);
		return $this->listGalleries($filter, $sortOrder, $limit);
	}

	/**
	 * @param int                                                $count
	 * @param \BiberLtd\Bundle\GalleryBundle\Services\mixed|null $sortOrder
	 * @param \BiberLtd\Bundle\GalleryBundle\Services\mixed|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithAudioCount(int $count, mixed $sortOrder = null, mixed $limit = null) {
		return $this->listGalleriesWithTypeCount('a', 'eq', $count, $sortOrder, $limit);
	}

	/**
	 * @param int                                                $count
	 * @param \BiberLtd\Bundle\GalleryBundle\Services\mixed|null $sortOrder
	 * @param \BiberLtd\Bundle\GalleryBundle\Services\mixed|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithAudioCountBetween(int $count, mixed $sortOrder = null, mixed $limit = null) {
		return $this->listGalleriesWithTypeCount('a', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithAudioCountLessThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('a', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithAudioCountMoreThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('a', 'more', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithDocumentCount(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('d', 'eq', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithDocumentCountBetween(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('d', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithDocumentCountLessThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('d', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithDocumenCounttMoreThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('d', 'more', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithImageCount(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('i', 'eq', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithImageCountBetween(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('i', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithImageCountLessThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('i', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithImageCountMoreThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('i', 'more', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithMediaCount(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('m', 'eq', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithMediaCountBetween(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('m', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithMediaCountLessThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('m', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithMediaCountMoreThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('m', 'more', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithVideoCount(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('v', 'eq', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithVideoCountBetween(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('v', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithVideoCountLessThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('v', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @param int        $count
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return mixed
	 */
	public function listGalleriesWithVideoCountMoreThan(int $count, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesWithTypeCount('v', 'more', $count, $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortorder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesPublishedAfter(\DateTime $date, array $sortorder = null, array $limit = null) {
		return $this->listGalleriesPublished($date, 'after', $sortorder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesPublishedBefore(\DateTime $date, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesPublished($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @param array      $dates
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	public function listGalleriesPublishedBetween(array $dates, array $sortOrder = null, array $limit = null) {
		return $this->listGalleriesPublished($dates, 'between', $sortOrder, $limit);
	}

	/**
	 * @param \intger    $limit
	 * @param array|null $sortOrder
	 *
	 * @return array
	 */
	public function listLastImagesOfAllGalleries(\intger $limit, array $sortOrder = null) {
		return $this->listImagesOfAllGalleries($sortOrder, array('start' => 0, 'count' => $limit));
	}

	/**
	 * @param string     $mediaType
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 * @param array|null $filter
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listMediaOfAllGalleries(string $mediaType = 'all', array $sortOrder = null, array $limit = null, array $filter = null){
		$timeStamp = microtime(true);
		$allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
		if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
			return $this->createException('InvalidParameter', '$mediaType parameter can only have one of the following values: '.implode(', ',$allowedTypes), 'err.invalid.parameter.collection');
		}
		$qStr = 'SELECT '.$this->entity['gm']['alias']
			.' FROM '.$this->entity['gm']['name'].' '.$this->entity['gm']['alias'];
		unset($response, $gallery);
		$wStr = '';
		if($mediaType != 'all'){
			$wStr = ' WHERE '.$this->entity['gm']['alias'].".type = '".$mediaType."'";
		}
		$qStr = $qStr.$wStr;
		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();

		$fileIds = [];
		$totalRows = count($result);

		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}

		foreach($result as $gm){
			$fileIds[] = $gm->getFile()->getId();
			$this->em->detach($gm);
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

		return $fModel->listFiles($filter, $sortOrder, $limit);
	}

	/**
	 * @param mixed $gallery
	 * @param string     $mediaType
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 * @param array|null $filter
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listMediaOfGallery($gallery, string $mediaType = 'all', array $sortOrder = null, array $limit = null, array $filter = null){
		$timeStamp = microtime(true);
		$allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
		if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
			return $this->createException('InvalidParameter', '$mediaType parameter can only have one of the following values: '.implode(', ',$allowedTypes), 'err.invalid.parameter.collection');
		}
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$qStr = 'SELECT '.$this->entity['gm']['alias']
			.' FROM '.$this->entity['gm']['name'].' '.$this->entity['gm']['alias']
			.' WHERE '.$this->entity['gm']['alias'].'.gallery = '.$gallery->getId();
		unset($response, $gallery);
		$oStr = '';
		if(!is_null($sortOrder)){
			foreach($sortOrder as $column => $direction){
				switch($column){
					case 'gallery':
					case 'file':
					case 'type':
					case 'sort_order':
						$column = $this->entity['gm']['alias'].'.'.$column;
						break;
					case 'date_added':
						$column = $this->entity['gm']['alias'].'.'.$column;
						break;
					case 'count_view':
					case 'status':
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			if(!empty($oStr)){
				$oStr = rtrim($oStr, ', ');
				$oStr = ' ORDER BY '.$oStr.' ';
			}
		}

		$whereStr = '';
		if($mediaType != 'all'){
			$whereStr = ' AND '.$this->entity['gm']['alias'].".type = '".$mediaType."'";
		}
		$qStr .= $whereStr;
		$qStr .= $oStr;

		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();
		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}

		foreach($result as $gm){
			$fileIds[] = $gm->getFile()->getId();
			$this->em->detach($gm);
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
		$collection = array();
		foreach($fileIds as $id){
			foreach($response->result->set as $entity){
				if($id == $entity->getId()){
					$collection[] = $entity;
				}
			}
		}
		$response->result->set = $collection;
		return $response;
	}

	/**
	 * @param mixed $gallery
	 * @param string     $status
	 * @param string     $mediaType
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 * @param array|null $filter
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listMediaOfGalleryWithStatus($gallery, string $status, string $mediaType = 'all', array $sortOrder = null, array $limit = null, array $filter = null){
		$timeStamp = microtime(true);
		$allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
		if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
			return $this->createException('InvalidParameter', '$mediaType parameter can only have one of the following values: '.implode(', ',$allowedTypes), 'err.invalid.parameter.collection');
		}
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$qStr = 'SELECT '.$this->entity['gm']['alias']
				.' FROM '.$this->entity['gm']['name'].' '.$this->entity['gm']['alias']
				.' WHERE '.$this->entity['gm']['alias'].'.gallery = '.$gallery->getId();
		unset($response, $gallery);
		$whereStr = '';
		if($mediaType != 'all'){
			$whereStr = ' AND '.$this->entity['gm']['alias'].".type = '".$mediaType."'";
		}
		$whereStr = ' AND '.$this->entity['gm']['alias'].".status = '".$status."'";

		$qStr .= $whereStr;
		$oStr = '';
		if ($sortOrder != null) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'sort_order':
						$column = $this->entity['gm']['alias'] . '.' . $column;
						unset($sortOrder['sort_order']);
						break;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}
		$qStr .= $oStr;
		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();
		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}

		foreach($result as $gm){
			$fileIds[] = $gm->getFile()->getId();
			$this->em->detach($gm);
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
		$collection = [];
		foreach($fileIds as $id){
			foreach($response->result->set as $entity){
				if($id == $entity->getId()){
					$collection[] = $entity;
				}
			}
		}
		$response->result->set = $collection;
		return $response;
	}

	/**
	 * @param mixed $gallery
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listImagesOfGallery($gallery, array $sortOrder = null, array $limit = null) {
		return $this->listMediaOfGallery($gallery, 'i', $sortOrder, $limit);
	}

	/**
	 * @param string $gallery
	 * @param string     $mediaType
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 * @param array|null $filter
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listPublishedMediaOfGallery($gallery, string $mediaType = 'all', array $sortOrder = null, array $limit = null, array $filter = null){
		return $this->listMediaOfGalleryWithStatus($gallery, 'p', $mediaType, $sortOrder, $limit, $filter);
	}

	/**
	 * @param mixed $gallery
	 * @param int $count
	 *
	 * @return array
	 */
	public function listRandomImagesFromGallery($gallery, int $count = 1){
		return $this->listRandomMediaFromGallery($gallery, $count, 'i');
	}

	/**
	 * @param mixed  $gallery
	 * @param int    $count
	 * @param string $mediaType
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listRandomMediaFromGallery($gallery, int $count = 1, string $mediaType = 'all'){
		$timeStamp = microtime(true);
		$allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
		if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
			return $this->createException('InvalidParameter', '$mediaType parameter can only have one of the following values: '.implode(', ',$allowedTypes), 'err.invalid.parameter.collection');
		}
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$qStr = 'SELECT '.$this->entity['gm']['alias']
			.' FROM '.$this->entity['gm']['name'].' '.$this->entity['gm']['alias']
			.' WHERE '.$this->entity['gm']['alias'].'.gallery = '.$gallery->getId();
		unset($response, $gallery);
		$wStr = '';
		if($mediaType != 'all'){
			$wStr = ' AND '.$this->entity['gm']['alias'].".type = '".$mediaType."'";
		}
		$qStr .= $wStr;
		$q = $this->em->createQuery($qStr);

		$result = $q->getResult();

		$files = [];
		$totalRows = count($result);
		$lastIndex = $totalRows - 1;

		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}

		for($counter = 0; $counter >= $count; $counter++){
			$index = rand(0, $lastIndex);
			$files[] = $result[$index]->getFile();
			$counter++;
		}

		return new ModelResponse($files, $counter, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $gallery
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listVideosOfGallery($gallery, array $sortOrder = null, array $limit = null) {
		return $this->listMediaOfGallery($gallery, 'v', $sortOrder, $limit);
	}

	/**
	 * @param array $files
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeFilesFromGallery(array  $files, $gallery) {
		$timeStamp = microtime(true);
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$idsToRemove = [];
		$fModel = new FMMService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);
		foreach ($files as $file) {
			$response = $fModel->getFile($file);
			if($response->error->exist){
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM '.$this->entity['fog']['name'].' '.$this->entity['fog']['alias']
			.' WHERE '.$this->entity['fog']['alias'].'.gallery '.$gallery->getId()
			.' AND '.$this->entity['fog']['alias'].'.file '.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $locales
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function removeLocalesFromGallery(array $locales, $gallery){
		$timeStamp = microtime(true);
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$idsToRemove = [];
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if($response->error->exist){
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM '.$this->entity['agl']['name'].' '.$this->entity['agl']['alias']
			.' WHERE '.$this->entity['agl']['alias'].'.gallery = '.$gallery->getId()
			.' AND '.$this->entity['agl']['alias'].'.language '.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $locales
	 * @param mixed $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|mixed
	 */
	public function removeLocalesFromGalleryCategory(array $locales, $category){
		$timeStamp = microtime(true);
		$response = $this->getGalleryCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$idsToRemove = [];
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		foreach ($locales as $locale) {
			$response = $mlsModel->getLanguage($locale);
			if($response->error->exist){
				continue;
			}
			$idsToRemove[] = $response->result->set->getId();
		}
		$in = ' IN (' . implode(',', $idsToRemove) . ')';
		$qStr = 'DELETE FROM '.$this->entity['agcl']['name'].' '.$this->entity['agcl']['alias']
			.' WHERE '.$this->entity['agcl']['alias'].'.category = '.$category->getId()
			.' AND '.$this->entity['agcl']['alias'].'.language '.$in;

		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$deleted = true;
		if (!$result) {
			$deleted = false;
		}
		if ($deleted) {
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $gallery
	 *
	 * @return array
	 */
	public function updateGallery($gallery) {
		return $this->updateGalleries(array($gallery));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateGalleryMedia(array $collection) {
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = [];
		foreach($collection as $entity){
			if(!$entity instanceof BundleEntity\GalleryMedia){
				continue;
			}
			$this->em->persist($entity);
			$updatedItems[] = $entity;
			$countUpdates++;
		}
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateGalleryLocalizations(array $collection) {
		$timeStamp = microtime(true);
		/** Parameter must be an array */
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\GalleryLocalization) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			}
		}
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateGalleries(array $collection) {
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = [];
		foreach($collection as $data){
			if($data instanceof BundleEntity\Gallery){
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			}
			else if(is_object($data)){
				if(!property_exists($data, 'id') || !is_numeric($data->id)){
					return $this->createException('InvalidParameterException', 'Parameter must be an object with the "id" property and id property ​must have an integer value.', 'E:S:003');
				}
				if(!property_exists($data, 'date_updated')){
					$data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
				}
				if(property_exists($data, 'date_added')){
					unset($data->date_added);
				}
				if(!property_exists($data, 'site')){
					$data->site = 1;
				}
				$response = $this->getGallery($data->id, 'id');
				if($response['error']){
					return $this->createException('EntityDoesNotExist', 'GalleryCategory with id '.$data->id, 'err.invalid.entity');
				}
				$oldEntity = $response['result']['set'];
				foreach($data as $column => $value){
					$set = 'set'.$this->translateColumnName($column);
					switch($column){
						case 'local':
							$localizations = [];
							foreach($value as $langCode => $translation){
								$localization = $oldEntity->getLocalization($langCode, true);
								$newLocalization = false;
								if(!$localization){
									$newLocalization = true;
									$localization = new BundleEntity\GalleryLocalization();
									$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
									$response = $mlsModel->getLanguage($langCode);
									if($response->error->exist){
										return $response;
									}
									$localization->setLanguage($response->result->set);
									$localization->setGallery($oldEntity);
								}
								foreach($translation as $transCol => $transVal){
									$transSet = 'set'.$this->translateColumnName($transCol);
									$localization->$transSet($transVal);
								}
								if($newLocalization){
									$this->em->persist($localization);
								}
								$localizations[] = $localization;
							}
							$oldEntity->setLocalizations($localizations);
							break;
						case 'preview_file':
							$fModel = $this->kernel->getContainer()->get('filemanagement.model');
							$response = $fModel->getFile($value, 'id');
							if($response->error->exist){
								return $response;
							}
							$oldEntity->$set($response->result->set);
							unset($response, $fModel);
							break;
						case 'site':
							$sModel = $this->kernel->getContainer()->get('sitemanagement.model');
							$response = $sModel->getSite($value, 'id');
							if($response->error->exist){
								return $response;
							}
							$oldEntity->$set($response->result->set);
							unset($response, $sModel);
							break;
						case 'id':
							break;
						default:
							$oldEntity->$set($value);
							break;
					}
					if($oldEntity->isModified()){
						$this->em->persist($oldEntity);
						$countUpdates++;
						$updatedItems[] = $oldEntity;
					}
				}
			}
		}if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param \DateTime  $date
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	private function listGalleriesAdded(\DateTime $date, string $eq, array $sortOrder = null, array $limit = null) {
		$timeStamp = microtime(true);

		$column = $this->entity['g']['alias'] . '.date_added';
		if ($eq == 'after' || $eq == 'before' || $eq == 'on') {
			switch ($eq) {
				case 'after':
					$eq = '>';
					break;
				case 'before':
					$eq = '<';
					break;
				case 'on':
				default:
					$eq = '=';
					break;
			}
			$condition = array('column' => $column, 'comparison' => $eq, 'value' => $date);
			$filter[] = array(
				'glue' => 'and',
				'condition' => array(
					array(
						'glue' => 'and',
						'condition' => $condition,
					)
				)
			);
		}
		else {
			$filter[] = array(
				'glue' => 'and',
				'condition' => array(
					array(
						'glue' => 'and',
						'condition' => array('column' => $column, 'comparison' => '>', 'value' => $date[0]),
					),
					array(
						'glue' => 'and',
						'condition' => array('column' => $column, 'comparison' => '<', 'value' => $date[1]),
					)
				)
			);
		}
		return $this->listGalleries($filter, $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	private function listGalleriesUpdated(\DateTime $date, string $eq, array $sortOrder = null, array $limit = null) {
		$timeStamp = microtime(true);
		$column = $this->entity['g']['alias'] . '.date_added';

		if ($eq == 'after' || $eq == 'before' || $eq == 'on') {
			switch ($eq) {
				case 'after':
					$eq = '>';
					break;
				case 'before':
					$eq = '<';
					break;
				case 'on':
				default:
					$eq = '=';
					break;
			}
			$condition = array('column' => $column, 'comparison' => $eq, 'value' => $date);
			$filter[] = array(
				'glue' => 'and',
				'condition' => array(
					array(
						'glue' => 'and',
						'condition' => $condition,
					)
				)
			);
		} else {
			$filter[] = array(
				'glue' => 'and',
				'condition' => array(
					array(
						'glue' => 'and',
						'condition' => array('column' => $column, 'comparison' => '>', 'value' => $date[0]),
					),
					array(
						'glue' => 'and',
						'condition' => array('column' => $column, 'comparison' => '<', 'value' => $date[1]),
					)
				)
			);
		}
		return $this->listGalleries($filter, $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	private function listGalleriesPublished(\DateTime $date, string $eq, array $sortOrder = null, array $limit = null) {
		$this->resetResponse();

		$column = $this->entity['g']['alias'] . '.date_published';

		if ($eq == 'after' || $eq == 'before' || $eq == 'on') {
			switch ($eq) {
				case 'after':
					$eq = '>';
					break;
				case 'before':
					$eq = '<';
					break;
				case 'on':
				default:
					$eq = '=';
					break;
			}
			$condition = array('column' => $column, 'comparison' => $eq, 'value' => $date);
			$filter[] = array(
				'glue' => 'and',
				'condition' => array(
					array(
						'glue' => 'and',
						'condition' => $condition,
					)
				)
			);
		} else {
			$filter[] = array(
				'glue' => 'and',
				'condition' => array(
					array(
						'glue' => 'and',
						'condition' => array('column' => $column, 'comparison' => '>', 'value' => $date[0]),
					),
					array(
						'glue' => 'and',
						'condition' => array('column' => $column, 'comparison' => '<', 'value' => $date[1]),
					)
				)
			);
		}
		return $this->listGalleries($filter, $sortOrder, $limit);
	}

	/**
	 * @param \DateTime  $date
	 * @param string     $eq
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array
	 */
	private function listGalleriesUnpublished(\DateTime $date, string $eq, array $sortOrder = null, array $limit = null) {
		$timeStamp = microtime(true);

		$column = $this->entity['g']['alias'] . '.date_unpublished';

		if ($eq == 'after' || $eq == 'before' || $eq == 'on') {
			switch ($eq) {
				case 'after':
					$eq = '>';
					break;
				case 'before':
					$eq = '<';
					break;
				case 'on':
					$eq = '=';
					break;
			}
			$condition = array('column' => $column, 'comparison' => $eq, 'value' => $date);
			$filter[] = array(
				'glue' => 'and',
				'condition' => array(
					array(
						'glue' => 'and',
						'condition' => $condition,
					)
				)
			);
		} else {
			$filter[] = array(
				'glue' => 'and',
				'condition' => array(
					array(
						'glue' => 'and',
						'condition' => array('column' => $column, 'comparison' => '>', 'value' => $date[0]),
					),
					array(
						'glue' => 'and',
						'condition' => array('column' => $column, 'comparison' => '<', 'value' => $date[1]),
					)
				)
			);
		}
		return $this->listGalleries($filter, $sortOrder, $limit);

	}

	/**
	 * @param mixed $category
	 *
	 * @return array
	 */
	public function insertGalleryCategory($category) {
		return $this->insertGalleryCategories(array($category));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertGalleryCategories(array $collection){
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$countLocalizations = 0;
		$insertedItems = [];
		$localizations = [];
		$now = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\GalleryCategory) {
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			} else if (is_object($data)) {
				$entity = new BundleEntity\GalleryCategory;
				if (!property_exists($data, 'date_added')) {
					$data->date_added = $now;
				}
				if (!property_exists($data, 'site')) {
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

		if ($countInserts > 0 && $countLocalizations > 0) {
			$this->insertGalleryCategoryLocalizations($localizations);
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));

	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertGalleryCategoryLocalizations(array $collection){
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = [];
		foreach($collection as $data){
			if($data instanceof BundleEntity\GalleryCategoryLocalization){
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else{
				$category = $data['entity'];
				foreach($data['localizations'] as $locale => $translation){
					$entity = new BundleEntity\GalleryCategoryLocalization();
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
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 *
	 * @return array
	 */
	public function updateGalleryCategory($category){
		return $this->updateGalleryCategories(array($category));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateGalleryCategoryLocalizations(array $collection) {
		$timeStamp = microtime(true);
		/** Parameter must be an array */
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\GalleryCategoryLocalization) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			}
		}
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateGalleryCategories(array $collection){
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$localizations = [];
		$updatedItems = [];
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\GalleryCategory) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			} else if (is_object($data)) {
				if (!property_exists($data, 'id') || !is_numeric($data->id)) {
					return $this->createException('InvalidParameter', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
				}
				if (!property_exists($data, 'date_updated')) {
					$data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
				}
				if (property_exists($data, 'date_added')) {
					unset($data->date_added);
				}
				$response = $this->getGalleryCategory($data->id, 'id');
				if ($response['error']) {
					return $this->createException('EntityDoesNotExist', 'GalleryCategory with id ' . $data->id, 'err.invalid.entity');
				}
				$oldEntity = $response['result']['set'];
				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'local':
							$localizations = [];
							foreach ($value as $langCode => $translation) {
								$localization = $oldEntity->getLocalization($langCode, true);
								$newLocalization = false;
								if (!$localization) {
									$newLocalization = true;
									$localization = new BundleEntity\GalleryCategoryLocalization();
									$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
									$response = $mlsModel->getLanguage($langCode);
									$localization->setLanguage($response['result']['set']);
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
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 *
	 * @return array
	 */
	public function deleteGalleryCategory($category){
		return $this->deleteGalleryCategories(array($category));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteGalleryCategories(array $collection){
		$timeStamp = microtime(true);
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\GalleryCategory){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getGalleryCategory($entry);
				if(!$response->error->exists){
					$this->em->remove($response->result->set);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, microtime(true));
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array $set
	 * @param mixed $gallery
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|mixed
	 */
	public function addCategoriesToGallery(array $set, $gallery){
		$timeStamp = microtime(true);
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		foreach ($set as $item) {
			$response = $this->getGalleryCategory($item);
			if($response->error->exist){
				continue;
			}
			$category = $response->result->set;
			$aopCollection = [];
			/** Check if association exists */
			if (!$this->isCategoryAssociatedWithGallery($category, $gallery, true)) {
				$aop = new BundleEntity\CategoriesOfGallery();
				$now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
				$aop->setCategory($category)->setGallery($gallery)->setDateAdded($now);
				/** persist entry */
				$this->em->persist($aop);
				$aopCollection[] = $aop;
			}
		}
		/** flush all into database */

		if(count($aopCollection) > 0){
			$this->em->flush();
			return new ModelResponse($aopCollection, count($aopCollection), 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, microtime(true));
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, microtime(true));
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listGalleryCategories(array $filter = null, array $sortOrder = null, array $limit = null){
		$timeStamp = microtime(true);
		if(!is_array($sortOrder) && !is_null($sortOrder)){
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['gcl']['alias'] . ', ' . $this->entity['gcl']['alias']
			. ' FROM ' . $this->entity['gcl']['name'] . ' ' . $this->entity['gcl']['alias']
			. ' JOIN ' . $this->entity['gcl']['alias'] . '.category ' . $this->entity['gc']['alias'];

		if ($sortOrder != null) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'id':
					case 'date_added':
						$column = $this->entity['gc']['alias'] . '.' . $column;
						break;
					case 'name':
					case 'url_key':
						$column = $this->entity['gcl']['alias'] . '.' . $column;
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

		$categories = [];
		$unique = [];
		foreach($result as $gcl){
			$id = $gcl->getCategory()->getId();
			if (!isset($unique[$id])) {
				$categories[] = $gcl->getCategory();
				$unique[$id] = '';
			}
			$localizations[$id][] = $gcl;
		}
		$totalRows = count($categories);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		return new ModelResponse($categories, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param $category
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getGalleryCategory($category) {
		$timeStamp = microtime(true);
		if($category instanceof BundleEntity\GalleryCategory){
			return new ModelResponse($category, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
		}
		$result = null;
		switch($category){
			case is_numeric($category):
				$result = $this->em->getRepository($this->entity['gc']['name'])->findOneBy(array('id' => $category));
				break;
			case is_string($category):
				$result = $this->em->getRepository($this->entity['gcl']['name'])->findOneBy(array('url_key' => $category));
				if(is_null($result)){
					$response = $this->getGalleryCategoryByUrlKey($category);
					if(!$response->error->exist){
						$result = $response->result->set;
					}
				}
				unset($response);
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, microtime(true));
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param string $urlKey
	 * @param mixed|null   $language
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getGalleryCategoryByUrlKey(string $urlKey, $language = null){
		$timeStamp = microtime(true);
		if(!is_string($urlKey)){
			return $this->createException('InvalidParameterValueException', '$urlKey must be a string.', 'E:S:007');
		}
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['gcl']['alias'].'.url_key', 'comparison' => '=', 'value' => $urlKey),
				)
			)
		);
		if(!is_null($language)){
			$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
			$response = $mModel->getLanguage($language);
			if(!$response->error->exists){
				$filter[] = array(
					'glue' => 'and',
					'condition' => array(
						array(
							'glue' => 'and',
							'condition' => array('column' => $this->entity['gcl']['alias'].'.language', 'comparison' => '=', 'value' => $response->result->set->getId()),
						)
					)
				);
			}
		}
		$response = $this->listGalleryCategories($filter, null, array('start' => 0, 'count' => 1));

		$response->result->set = $response->result->set[0];
		$response->stats->execution->start = $timeStamp;
		$response->stats->execution->end = microtime(true);

		return $response;
	}

	/**
	 * @param mixed $category
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesGalleryCategoryExist($category, bool $bypass = false) {
		$response = $this->getGalleryCategory($category);
		$exist = true;
		if ($response->error->exist) {
			$exist = false;
			$response->result->set = false;
		}
		if ($bypass) {
			return $exist;
		}

		return $response;
	}

	/**
	 * @param mixed $category
	 * @param mixed $gallery
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function isCategoryAssociatedWithGallery($category, $gallery, bool $bypass = false){
		$timeStamp = microtime(true);
		$response = $this->getGalleryCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$found = false;

		$qStr = 'SELECT COUNT(' . $this->entity['cog']['alias'] . '.category)'
			. ' FROM ' . $this->entity['cog']['name'] . ' ' . $this->entity['cog']['alias']
			. ' WHERE ' . $this->entity['cog']['alias'] . '.category = ' . $category->getId()
			. ' AND ' . $this->entity['cog']['alias'] . '.gallery = ' . $gallery->getId();

		$q = $this->em->createQuery($qStr);

		$result = $q->getSingleScalarResult();

		if ($result > 0) {
			$found = true;
		}
		if ($bypass) {
			return $found;
		}
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, microtime(true));
	}

	/**
	 * @param mixed $category
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return array|\BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listGalleriesOfCategory($category, array $filter = null, array $sortOrder = null, array $limit = null){
		$timeStamp = microtime(true);
		$response  = $this->getGalleryCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$qStr = 'SELECT ' . $this->entity['cog']['alias']
			. ' FROM ' . $this->entity['cog']['name'] . ' ' . $this->entity['cog']['alias']
			. ' WHERE ' . $this->entity['cog']['alias'] . '.category = ' . $category->getId();
		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$collection= [];
		if (count($result) > 0) {
			foreach ($result as $cog) {
				$collection[] = $cog->getGallery()->getId();
			}
		}
		if (count($collection) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		$columnI = $this->entity['g']['alias'] . '.id';
		$conditionI = array('column' => $columnI, 'comparison' => 'in', 'value' => $collection);
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => $conditionI,
				)
			)
		);
		return $this->listGalleries($filter, $sortOrder, $limit);
	}

	/**
	 * @param \BiberLtd\Bundle\GalleryBundle\Services\mixed $gallery
	 * @param array|null                                    $filter
	 * @param array|null                                    $sortOrder
	 * @param array|null                                    $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listCategoriesOfGallery(mixed $gallery, array $filter = null, array $sortOrder = null, array $limit = null){
		$timeStamp = microtime(true);
		$response  = $this->getGallerY($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$qStr = 'SELECT ' . $this->entity['cog']['alias']
			. ' FROM ' . $this->entity['cog']['name'] . ' ' . $this->entity['cog']['alias']
			. ' WHERE ' . $this->entity['cog']['alias'] . '.GALLERY = ' . $gallery->getId();
		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();

		$collection= [];
		if (count($result) > 0) {
			foreach ($result as $cog) {
				$collection[] = $cog->getCategory()->getId();
			}
		}
		if (count($collection) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, microtime(true));
		}
		$columnI = $this->entity['gc']['alias'] . '.id';
		$conditionI = array('column' => $columnI, 'comparison' => 'in', 'value' => $collection);
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => $conditionI,
				)
			)
		);
		return $this->listGalleryCategories($filter, $sortOrder, $limit);
	}

	/**
	 * @param mixed $locale
	 * @param array|null $filter
	 * @param null       $sortOrder
	 * @param null       $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listGalleryCategoriesInLocales($locale, array $filter = null, $sortOrder = null, $limit = null){
		$mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mlsModel->getLanguage($locale,'iso_code');
		if($response->error->exist){
			return $response;
		}
		$language = $response->result->set;
		unset($response);
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['gcl']['alias'] . '.language', 'comparison' => '=', 'value' => $language->getId() ),
				)
			)
		);
		return $this->listGalleryCategories($filter, $sortOrder, $limit);
	}
}