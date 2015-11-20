<?php
/**
 * @vendor      BiberLtd
 * @package		GalleryBundle
 * @subpackage	Services
 * @name	    GalleryModel
 *
 * @author		Can Berkol
 * @author      Said Imamoglu
 *
 * @copyright   Biber Ltd. (www.biberltd.com)
 *
 * @version     1.2.3
 * @date        20.11.2015
 */

namespace BiberLtd\Bundle\GalleryBundle\Services;

/** Extends CoreModel */
use BiberLtd\Bundle\CoreBundle\CoreModel;
/** Entities to be used */
use BiberLtd\Bundle\CoreBundle\Responses\ModelResponse;
use BiberLtd\Bundle\GalleryBundle\Entity as BundleEntity;
use BiberLtd\Bundle\FileManagementBundle\Entity as FileBundleEntity;
use BiberLtd\Bundle\MultiLanguageSupportBundle\Entity as MLSEntity;
/** Helper Models */
use BiberLtd\Bundle\GalleryBundle\Services as SMMService;
use BiberLtd\Bundle\FileManagementBundle\Services as FMMService;
/** Core Service */
use BiberLtd\Bundle\CoreBundle\Services as CoreServices;
use BiberLtd\Bundle\CoreBundle\Exceptions as CoreExceptions;

class GalleryModel extends CoreModel {
	/**
	 * @name            __construct()
	 *
	 * @author          Can Berkol
	 * @author          Said Imamoglu
	 *
	 * @since           1.0.0
	 * @version         1.1.4
	 *
	 * @param           object          $kernel
	 * @param           string          $dbConnection
	 * @param           string          $orm
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
	 * @name            __destruct()
	 *
	 * @author          Said Imamoglu
	 *
	 * @since           1.0.0
	 * @version         1.0.0
	 *
	 */
	public function __destruct() {
		foreach ($this as $property => $value) {
			$this->$property = null;
		}
	}
	/**
	 * @name 		    addFileToGallery()
	 *
	 * @since		    1.0.5
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->addFilesToGallery()
	 *
	 * @param           mixed			$file
	 * @param           mixed           $gallery
	 *
	 * @return          array           $response
	 */
	public function addFileToGallery($file, $gallery) {
		return $this->addFilesToGallery(array($file), $gallery);
	}

	/**
	 * @name            addFilesToGallery()
	 *
	 * @since           1.0.0
	 * @version         1.2.2
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array           $files
	 * @param           mixed           $gallery
	 *
	 * @return          array           $response
	 */
	public function addFilesToGallery($files, $gallery) {
		$timeStamp = time();
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$fModel = new FMMService\FileManagementModel($this->kernel, $this->dbConnection, $this->orm);

		$fogCollection = array();
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
			return new ModelResponse($fogCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name            addLocalesToGallery()
	 *
	 * @since           1.1.3
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @use             $this->isLocaleAssociatedWithGallery()
	 * @use             $this->validateAndGetGallery()
	 * @use             $this->validateAndGetLocale()
	 *
	 * @param           array       $locales
	 * @param           mixed       $gallery
	 *
	 * @return          array       $response
	 */
	public function addLocalesToGallery($locales, $gallery){
		$timeStamp = time();
		$response = $this->getGallery($gallery);

		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$aglCollection = array();
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
			return new ModelResponse($aglCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name            addLocalesToGalleryCategory()
	 *
	 * @since           1.1.3
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->validateAndGetLocale()
	 * @use             $this->validateAndGetGalleryCategory()
	 * @use             $this->isLocaleAssociatedWithGalleryCategory()
	 *
	 * @param           array           $locales        Language entities, ids or iso_codes
	 * @param           mixed           $category       entity, id
	 *
	 * @return          array           $response
	 */
	public function addLocalesToGalleryCategory($locales, $category){
		$timeStamp = time();
		$response = $this->getGalleryCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		unset($response);
		$aglCollection = array();
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
			return new ModelResponse($aglCollection, $count, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name 			countDistinctMediaTotal()
	 *
	 * @since			1.0.2
	 * @version         1.1.9
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @return          array           $response
	 */
	public function countDistinctMediaTotal() {
		$timeStamp = time();
		$qStr = 'SELECT COUNT( DISTINCT '. $this->entity['gm']['alias'].'.file)'
			.' FROM '.$this->entity['gm']['name'].' '.$this->entity['gm']['alias'];

		$q = $this->em->createQuery($qStr);

		$result = $q->getSingleScalarResult();

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            countTotalAudioInGallery()
	 *
	 * @since           1.0.7
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->countTotalMediaInGallery()
	 *
	 * @param           mixed           $gallery
	 *
	 * @return          array           $response
	 */
	public function countTotalAudioInGallery($gallery){
		return $this->countTotalMediaInGallery($gallery, 'a');
	}
	/**
	 * @name            countTotalDocumentsInGallery()
	 *
	 * @since           1.0.7
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->countTotalMediaInGallery()
	 *
	 * @param           mixed           $gallery
	 *
	 * @return          array           $response
	 */
	public function countTotalDocumentsInGallery($gallery){
		return $this->countTotalMediaInGallery($gallery, 'd');
	}
	/**
	 * @name            countTotalImagesInGallery ()
	 *
	 * @since           1.0.6
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 *
	 * @use             $this->countTotalMediaInGallery()
	 *
	 * @param           mixed           $gallery
	 *
	 * @return          array           $response
	 */
	public function countTotalImagesInGallery($gallery){
		return $this->countTotalMediaInGallery($gallery, 'i');
	}
	/**
	 * @name            countTotalMediaInGallery()
	 *
	 * @since           1.0.6
	 * @version         1.1.9
	 *
	 * @author          Can Berkol
	 *
	 * @param           mixed       	$gallery
	 * @param           string      	$mediaType      all, i, a, v, f, d, p, s
	 *
	 * @return          array           $response
	 */
	public function countTotalMediaInGallery($gallery, $mediaType = 'all'){
		$timeStamp = time();
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
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            countTotalImagesInGallery ()
	 *
	 * @since           1.0.7
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 *
	 * @use             $this->countTotalMediaInGallery()
	 *
	 * @param           mixed           $gallery
	 *
	 * @return          array           $response
	 */
	public function countTotalVideoInGallery($gallery){
		return $this->countTotalMediaInGallery($gallery, 'v');
	}
	/**
	 * @name 			deleteGalleries()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          array           $response
	 */
	public function deleteGalleries($collection) {
		$timeStamp = time();
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
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}

	/**
	 * @name 			deleteGallery()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @use             $this->geleteGalleries()
	 *
	 * @param           mixed           $gallery           Gallery entity, id or url key.
	 *
	 * @return          mixed           $response
	 */
	public function deleteGallery($gallery) {
		return $this->deleteGalleries(array($gallery));
	}

	/**
	 * @name 			doesGalleryExist()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->getGallery()
	 *
	 * @param           mixed           $gallery
	 * @param           bool            $bypass
	 *
	 * @return          mixed           $response
	 */
	public function doesGalleryExist($gallery, $bypass = false) {
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
	 * @name 		    doesGalleryMediaExist()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->getGalleryMedia()
	 *
	 * @param           array           $galleryMedia
	 * @param           bool            $bypass
	 *
	 * @return          mixed           $response
	 */
	public function doesGalleryMediaExist($galleryMedia, $bypass = false) {
		$timeStamp = time();
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
		return new ModelResponse($exist, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			getGallery()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berlpş
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->listProducts()
	 *
	 * @param           mixed           $gallery
	 *
	 * @return          mixed           $response
	 */
	public function getGallery($gallery) {
		$timeStamp = time();
		if($gallery instanceof BundleEntity\Gallery){
			return new ModelResponse($gallery, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
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
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getGalleryByUrlKey()
	 *
	 * @since           1.1.4
	 * @version         1.1.4
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
	public function getGalleryByUrlKey($urlKey, $language = null){
		$timeStamp = time();
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
		$response->stats->execution->end = time();

		return $response;
	}
	/**
	 * @name 			getGalleryMedia()
	 *
	 * @since			1.0.3
	 * @version         1.1.4
	 * @author          Can Berkol

	 * @param           mixed           $file
	 * @param           mixed           $gallery
	 *
	 * @return          mixed           $response
	 */
	public function getGalleryMedia($file, $gallery) {
		$timeStamp = time();
		$fModel = $this->kernel->getContainer()->get('filemanagement.model');
		$response = $fModel->getFile($file);
		if($response->error->exist){
			return $response;
		}
		$file = $response->result->set;
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response->result->set;
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
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());

	}
	/**
	 * @name 		    getMaxSortOrderOfGalleryMedia()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->getGallery()
	 *
	 * @param           mixed           $gallery
	 * @param           bool            $bypass
	 *
	 * @return          mixed           bool | $response
	 */
	public function getMaxSortOrderOfGalleryMedia($gallery, $bypass = false) {
		$timeStamp = time();
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
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            insertGalleries()
	 *
	 * @since           1.0.0
	 * @version         1.1.4
	 *
	 * @author          Said İmamoğlu
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $collection
	 *
	 * @return          array           $response
	 */

	public function insertGalleries($collection) {
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$countLocalizations = 0;
		$insertedItems = array();
		foreach($collection as $data){
			if($data instanceof BundleEntity\Gallery){
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else if(is_object($data)){
				$localizations = array();
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
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());

	}
	/**
	 * @name            insertGallery()
	 *
	 * @since           1.0.0
	 * @version         1.1.4
	 *
	 * @author          Said İmamoğlu
	 * @author          Can Berkol
	 *
	 * @use             $this->insertGalleries()
	 *
	 * @param           mixed       $gallery
	 *
	 * @return          array       $response
	 *
	 */
	public function insertGallery($gallery) {
		return $this->insertGalleries(array($gallery));
	}
	/**
	 * @name 			insertGalleryLocalizations()
	 *
	 * @since			1.0.1
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection        Collection of entities or post data.
	 *
	 * @return          array           $response
	 */
	public function insertGalleryLocalizations($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
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
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name 		    isFileAssociatedWithGallery()
	 *
	 * @since		    1.0.7
	 * @version         1.2.0
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $file
	 * @param           mixed           $gallery
	 * @param           bool            $bypass
	 *
	 * @return          mixed           bool or $response
	 */
	public function isFileAssociatedWithGallery($file, $gallery, $bypass = false) {
		$timeStamp = time();
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
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            isLocaleAssociatedWithGallery()
	 *
	 * @since           1.1.3
	 * @version         1.2.0
	 *
	 * @author          Can Berkol
	 *
	 * @user            $this->createException
	 *
	 * @param           mixed 	$locale
	 * @param           mixed 	$gallery
	 * @param           bool 	$bypass
	 *
	 * @return          mixed
	 */
	public function isLocaleAssociatedWithGallery($locale, $gallery, $bypass = false){
		$timeStamp = time();
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
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @name            isLocaleAssociatedWithGalleryCategory()
	 *
	 * @since           1.1.3
	 * @version         1.2.0
	 * @author          Can Berkol
	 *
	 * @user            $this->createException
	 *
	 * @param           mixed 		$locale
	 * @param           mixed 		$category
	 * @param           bool 		$bypass
	 *
	 * @return          mixed
	 */
	public function isLocaleAssociatedWithGalleryCategory($locale, $category, $bypass = false){
		$timeStamp = time();
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
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            listActiveLocalesOfGallery()
	 *                  List active locales of a given gallery.
	 *
	 * @since           1.1.3
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $gallery entity, id, or sku
	 *
	 * @return          array           $gallery
	 */
	public function listActiveLocalesOfGallery($gallery){
		$timeStamp = time();
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
		$locales = array();
		$unique = array();
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($locales, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @name            listActiveLocalesOfGalleryCategory ()
	 *
	 * @since           1.1.3
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed           $category
	 *
	 * @return          array           $response
	 */
	public function listActiveLocalesOfGalleryCategory($category){
		$timeStamp = time();
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
		$locales = array();
		$unique = array();
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($locales, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			listAllAudioOfGallery()
	 *
	 * @since			1.0.8
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed           $gallery
	 * @param           array           $sortOrder
	 *
	 * @return          array           $response
	 */
	public function listAllAudioOfGallery($gallery, $sortOrder = null) {
		return $this->listMediaOfGallery($gallery, 'a', $sortOrder);
	}
	/**
	 * @name 			listAllGalleries()
	 *
	 * @since			1.0.1
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleries()
	 *
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listAllGalleries($sortOrder = null, $limit = null) {
		return $this->listGalleries(null, $sortOrder, $limit);
	}
	/**
	 * @name 			listAllImagesOfGallery()
	 *
	 * @since			1.0.1
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed           $gallery
	 * @param           array           $sortOrder
	 *
	 * @return          array           $response
	 */
	public function listAllImagesOfGallery($gallery, $sortOrder = null) {
		return $this->listMediaOfGallery($gallery, 'i', $sortOrder);
	}
	/**
	 * @name 			listDocumentsOfGallery()
	 *
	 * @since			1.1.1
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @param           mixed           $gallery
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listDocumentsOfGallery($gallery, $sortOrder = null, $limit = null) {
		return $this->listMediaOfGallery($gallery, 'd', $sortOrder, $limit);
	}
	/**
	 * @name 			listAllIVideosOfGallery()
	 *
	 * @since			1.0.1
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @param           mixed           $gallery
	 * @param           array           $sortOrder
	 *
	 * @return          array           $response
	 */
	public function listAllVideosOfGallery($gallery, $sortOrder = null) {
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
		$timeStamp = time();
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

		$galleries = array();
		$unique = array();
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($galleries, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			listGalleryAddedAfter()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesAdded()
	 *
	 * @param           array           $date
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleryAddedAfter($date, $sortOrder = null, $limit = null) {
		return $this->listGalleriesAdded($date, 'after', $sortOrder, $limit);
	}
	/**
	 * @name            listGalleryMedia()
	 *
	 * @since           1.2.0
	 * @version         1.2.0
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 * @use             $fModel->listFiles()
	 *
	 * @param           mixed       $gallery
	 * @param           string      $mediaType      all, i, a, v, f, d, p, s
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 * @param           array       $filter
	 *
	 * @return          array           $response
	 */
	public function listGalleryMedia($gallery, $mediaType = 'all', $sortOrder = null, $limit = null, $filter = null){
		$timeStamp = time();
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 		    listGalleriesAddedBefore()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesAdded()
	 *
	 * @param           array           $date
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesAddedBefore($date, $sortOrder = null, $limit = null) {
		return $this->listGalleriesAdded($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @name 			listGalleriesAddedBetween()
	 *
	 * @since			1.0.0
	 * @version         1.1.7
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesAdded()
	 *
	 * @param           array           $dates
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesAddedBetween($dates, $sortOrder = null, $limit = null) {
		return $this->listGalleriesAdded($dates, 'between', $sortOrder, $limit);
	}
	/**
	 * @name 			listGalleriesUpdatedAfter()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleriesUpdated()
	 *
	 * @param           array           $date
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesUpdatedAfter($date, $sortOrder = null, $limit = null) {
		return $this->listGalleriesUpdated($date, 'after', $sortOrder, $limit);
	}

	/**
	 * @name 			listGalleriesUpdatedBefore()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleriesUpdated()
	 *
	 * @param           array           $date
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesUpdatedBefore($date, $sortOrder = null, $limit = null) {
		return $this->listGalleriesUpdated($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @name 			listGalleriesUpdatedBetween()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleriesUpdated()
	 *
	 * @param           array           $dates
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesUpdatedBetween($dates, $sortOrder = null, $limit = null) {
		return $this->listGalleriesUpdated($dates, 'between', $sortOrder, $limit);
	}
	/**
	 * @name 			listGalleriesUnpublishedAfter()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleriesUnpublished()
	 *
	 * @param           array           $date
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesUnpublishedAfter($date, $sortOrder = null, $limit = null) {
		return $this->listGalleriesUnpublished($date, 'after', $sortOrder, $limit);
	}

	/**
	 * @name 			listGalleriesUnpublishedBefore()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleriesUnpublished()
	 *
	 * @param           array           $date
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesUnpublishedBefore($date, $sortOrder = null, $limit = null) {
		return $this->listGalleriesUnpublished($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @name 			listGalleriesUnpublishedBetween()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleriesUnpublished()
	 *
	 * @param           array           $dates
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesUnpublishedBetween($dates, $sortOrder = null, $limit = null) {
		return $this->listGalleriesUnpublished($dates, 'between', $sortOrder, $limit);
	}
	/**
	 * @name            listImagesOfAllGalleries()
	 *
	 * @since           1.0.1
	 * @version         1.1.4
	 *
	 * @author          Said İmamoğlu
	 * @author          Can Berkol
	 *
	 * @param           integer     $count
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 * @param           array       $filter
	 *
	 * @return          array           $response
	 */
	public function listImagesOfAllGalleries($count = 1, $sortOrder = null, $limit = null, $filter = null){
		return $this->listMediaOfAllGalleries($count, 'i', $sortOrder, $limit, $filter);
	}
	/**
	 * @name            listGalleriesOfMedia()
	 *
	 * @since           1.0.8
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @param           mixed       $file
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 * @param           array       $filter
	 *
	 * @return          array           $response
	 */
	public function listGalleriesOfMedia($file, $sortOrder = null, $limit = null, $filter = null){
		$timeStamp = time();
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

		$galleryIds = array();
		$totalRows = count($result);

		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}

		foreach($result as $gm){
			$galleryIds[] = $gm->getGallery()->getId();
			$this->em->detach($gm);
		}


		$galleryFilter[] = array('glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => 'g.id', 'comparison' => 'in', 'value' => $galleryIds),
				)
			)
		);
		return $this->listGalleries($galleryFilter, $sortOrder, $limit);
	}
	/**
	 * @name 		    listGalleriesOfSite()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleries()
	 *
	 * @param           array           $site
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesOfSite($site, $sortOrder = null, $limit = null) {
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
	 * @name 		    listGalleriesWithAudioCount()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           mixed           $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithAudioCount($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('a', 'eq', $count, $sortOrder, $limit);
	}
	/**
	 * @name 		    listGalleriesWithAudioCountBetween()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithAudioCountBetween($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('a', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithAudioCountLessThan()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithAudioCountLessThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('a', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithAudioCountMoreThan()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithAudioCountMoreThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('a', 'more', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithDocumentCount()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithDocumentCount($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('d', 'eq', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithDocumentCountBetween()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithDocumentCountBetween($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('d', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithDocumentCountLessThan()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithDocumentCountLessThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('d', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithDocumentCountMoreThan()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithDocumenCounttMoreThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('d', 'more', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithImageCount()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithImageCount($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('i', 'eq', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithImageCountBetween()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           intger          $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithImageCountBetween($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('i', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithImageCountLessThan()
	 *                  List image files with count less than given value.
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithImageCountLessThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('i', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithImageCountMoreThan()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithImageCountMoreThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('i', 'more', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithMediaCount()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithMediaCount($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('m', 'eq', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithMediaCountBetween()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithMediaCountBetween($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('m', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithMediaCountLessThan()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithMediaCountLessThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('m', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithMediaCountMoreThan()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithMediaCountMoreThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('m', 'more', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithVideoCount()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithVideoCount($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('v', 'eq', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithVideoCountBetween()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithVideoCountBetween($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('v', 'between', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithVideoCountLessThan()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithVideoCountLessThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('v', 'less', $count, $sortOrder, $limit);
	}

	/**
	 * @name 		    listGalleriesWithVideoCountMoreThan()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleriesWithTypeCount()
	 *
	 * @param           integer         $count
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesWithVideoCountMoreThan($count, $sortOrder = null, $limit = null) {
		return $this->listGalleriesWithTypeCount('v', 'more', $count, $sortOrder, $limit);
	}
	/**
	 * @name 			listGalleriesPublishedAfter()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleriesPublished()
	 *
	 * @param           array           $date                   The date to be checked.
	 * @param           array           $sortorder              Array
	 *                                      'column'            => 'asc|desc'
	 * @param           array           $limit
	 *                                      start
	 *                                      count
	 *
	 * @return          array           $response
	 */
	public function listGalleriesPublishedAfter($date, $sortorder = null, $limit = null) {
		return $this->listGalleriesPublished($date, 'after', $sortorder, $limit);
	}

	/**
	 * @name 			listGalleriesPublishedBefore()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleriesPublished()
	 *
	 * @param           array           $date
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesPublishedBefore($date, $sortOrder = null, $limit = null) {
		return $this->listGalleriesPublished($date, 'before', $sortOrder, $limit);
	}

	/**
	 * @name 			listGalleriesPublishedBetween()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleriesPublished()
	 *
	 * @param           array           $dates
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listGalleriesPublishedBetween($dates, $sortOrder = null, $limit = null) {
		return $this->listGalleriesPublished($dates, 'between', $sortOrder, $limit);
	}
	/**
	 * @name 			listLastImagesOfAllGalleries()
	 *
	 * @since			1.0.1
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @use             $this->listImagesOfAllGalleries
	 *
	 * @param           integer         $limit
	 * @param           array           $sortOrder
	 *
	 * @return          array           $response
	 */
	public function listLastImagesOfAllGalleries($limit, $sortOrder = null) {
		return $this->listImagesOfAllGalleries($sortOrder, array('start' => 0, 'count' => $limit));
	}
	/**
	 * @name            listMediaOfAllGalleries()
	 *
	 * @since           1.0.7
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 * @use             $fModel->listFiles()
	 *
	 * @param           string      $mediaType
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 * @param           array       $filter
	 *
	 * @return          array           $response
	 */
	public function listMediaOfAllGalleries($mediaType = 'all', $sortOrder = null, $limit = null, $filter = null){
		$timeStamp = time();
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

		$fileIds = array();
		$totalRows = count($result);

		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
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
	 * @name            listMediaOfGallery()
	 *
	 * @since           1.0.7
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 * @use             $fModel->listFiles()
	 *
	 * @param           mixed       $gallery
	 * @param           string      $mediaType      all, i, a, v, f, d, p, s
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 * @param           array       $filter
	 *
	 * @return          array           $response
	 */
	public function listMediaOfGallery($gallery, $mediaType = 'all', $sortOrder = null, $limit = null, $filter = null){
		$timeStamp = time();
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
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
	 * @name            listMediaOfGalleryWithStatus()
	 *
	 * @since           1.1.6
	 * @version         1.1.6
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 * @use             $fModel->listFiles()
	 *
	 * @param           mixed       $gallery
	 * @param           string      $status
	 * @param           string      $mediaType      all, i, a, v, f, d, p, s
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 * @param           array       $filter
	 *
	 * @return          array           $response
	 */
	public function listMediaOfGalleryWithStatus($gallery, $status, $mediaType = 'all', $sortOrder = null, $limit = null, $filter = null){
		$timeStamp = time();
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
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
	 * @name 			listImagesOfGallery()
	 *
	 * @since			1.0.1
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed           $gallery
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listImagesOfGallery($gallery, $sortOrder = null, $limit = null) {
		return $this->listMediaOfGallery($gallery, 'i', $sortOrder, $limit);
	}
	/**
	 * @name            listPublishedMediaOfGallery()
	 *
	 * @since           1.1.6
	 * @version         1.1.6
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 * @use             $fModel->listFiles()
	 *
	 * @param           mixed       $gallery
	 * @param           string      $mediaType      all, i, a, v, f, d, p, s
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 * @param           array       $filter
	 *
	 * @return          array           $response
	 */
	public function listPublishedMediaOfGallery($gallery, $mediaType = 'all', $sortOrder = null, $limit = null, $filter = null){
		return $this->listMediaOfGalleryWithStatus($gallery, 'p', $mediaType, $sortOrder, $limit, $filter);
	}
	/**
	 * @name            listRandomImagesFromGallery()
	 *
	 * @since           1.0.7
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @param           mixed       $gallery
	 * @param           integer     $count
	 *
	 * @return          array           $response
	 */
	public function listRandomImagesFromGallery($gallery, $count = 1){
		return $this->listRandomMediaFromGallery($gallery, $count, 'i');
	}
	/**
	 * @name            listRandomMediaFromGallery()
	 *
	 * @since           1.0.7
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 *
	 * @param           mixed       $gallery
	 * @param           integer     $count          Limit the number of items to be returned
	 * @param           string      $mediaType      all, i, a, v, f, d, p, s
	 *
	 * @return          array           $response
	 */
	public function listRandomMediaFromGallery($gallery, $count = 1, $mediaType = 'all'){
		$timeStamp = time();
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

		$files = array();
		$totalRows = count($result);
		$lastIndex = $totalRows - 1;

		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}

		for($counter = 0; $counter >= $count; $counter++){
			$index = rand(0, $lastIndex);
			$files[] = $result[$index]->getFile();
			$counter++;
		}

		return new ModelResponse($files, $counter, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name 			listVideosOfGallery()
	 *
	 * @since			1.1.1
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed           $gallery
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listVideosOfGallery($gallery, $sortOrder = null, $limit = null) {
		return $this->listMediaOfGallery($gallery, 'v', $sortOrder, $limit);
	}
	/**
	 *  @name 		    removeFilesFromProduct()
	 *
	 * @since		    1.0.0
	 * @version         1.2.0
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array           $files
	 * @param           mixed           $gallery
	 *
	 * @return          array           $response
	 */
	public function removeFilesFromGallery($files, $gallery) {
		$timeStamp = time();
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		unset($response);
		$idsToRemove = array();
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
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}
	/**
	 * @name            removeLocalesFromGallery()
	 *
	 * @since           1.1.3
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @use             $this->doesGalleryExist()
	 * @use             $this->isLocaleAssociatedWithGallery()
	 *
	 * @param           array 		$locales
	 * @param           mixed 		$gallery
	 *
	 * @return          array           $response
	 */
	public function removeLocalesFromGallery($locales, $gallery){
		$timeStamp = time();
		$response = $this->getGallery($gallery);
		if($response->error->exist){
			return $response;
		}
		$gallery = $response->result->set;
		$idsToRemove = array();
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
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}

	/**
	 * @name            removeLocalesFromGalleryCategory()
	 *
	 * @since           1.1.3
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @use             $this->doesGalleryExist()
	 *
	 * @param           array 		$locales
	 * @param           mixed 		$category
	 *
	 * @return          array       $response
	 */
	public function removeLocalesFromGalleryCategory($locales, $category){
		$timeStamp = time();
		$response = $this->getGalleryCategory($category);
		if($response->error->exist){
			return $response;
		}
		$category = $response->result->set;
		$idsToRemove = array();
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
			return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
	}
	/**
	 * @name            updateGallery()
	 *                  Updates single gallery from database
	 *
	 * @since           1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->updateGalleries()
	 *
	 * @param           mixed   $gallery
	 *
	 * @return          array   $response
	 *
	 */
	public function updateGallery($gallery) {
		return $this->updateGalleries(array($gallery));
	}
	/**
	 * @name            updateGalleryMedia()
	 *
	 * @since           1.2.0
	 * @version         1.2.0
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           mixed   $collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 *
	 */
	public function updateGalleryMedia($collection) {
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = array();
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
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}
	/**
	 * @name            updateGalleryLocalizations()
	 *
	 * @since           1.1.7
	 * @version         1.1.7
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateGalleryLocalizations($collection) {
		$timeStamp = time();
		/** Parameter must be an array */
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = array();
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
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}
	/**
	 * @name            updateGalleries()
	 *
	 * @since           1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @throw           InvalidParameterException
	 *
	 * @param           mixed   $collection
	 *
	 * @return          array   $response
	 *
	 */
	public function updateGalleries($collection) {
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = array();
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
							$localizations = array();
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
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}

	/**
	 * @name 		    listGalleriesAdded()
	 *
	 * @since		    1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @uses            $this->listGalleries()
	 *
	 * @param           mixed           $date
	 * @param           string          $eq
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	private function listGalleriesAdded($date, $eq, $sortOrder = null, $limit = null) {
		$timeStamp = time();

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
	 * @name 			listGalleriesUpdated()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listGalleries()
	 *
	 * @param           mixed           $date
	 * @param           string          $eq
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	private function listGalleriesUpdated($date, $eq, $sortOrder = null, $limit = null) {
		$timeStamp = time();
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
	 * @name 			listGalleriesPublished()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listProducts()
	 *
	 * @param           mixed           $date
	 * @param           string          $eq
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	private function listGalleriesPublished($date, $eq, $sortOrder = null, $limit = null) {
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
	 * @name 			listGalleriesUnpublished()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 * @author          Can Berkol
	 *
	 * @uses            $this->listProducts()
	 *
	 * @param           mixed           $date
	 * @param           string          $eq
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	private function listGalleriesUnpublished($date, $eq, $sortOrder = null, $limit = null) {
		$timeStamp = time();

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
	 * @name            insertGalleryCategory()
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->insertGalleryCategories()
	 *
	 * @param           mixed 			$category
	 *
	 * @return          array           $response
	 */
	public function insertGalleryCategory($category) {
		return $this->insertGalleryCategories(array($category));
	}

	/**
	 * @name            insertGalleryCategories()
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array			$collection
	 *
	 * @return          array           $response
	 */
	public function insertGalleryCategories($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$countLocalizations = 0;
		$insertedItems = array();
		$localizations = array();
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
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());

	}
	/**
	 * @name            insertGalleryCategoryLocalizations()
	 *
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param           array $collection Collection of entities or post data.
	 *
	 * @return          array           $response
	 */
	public function insertGalleryCategoryLocalizations($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
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
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}

	/**
	 * @name            updateGalleryCategory()
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->updateGalleryCategories()
	 *
	 * @param           mixed 			$category
	 *
	 * @return          mixed           $response
	 */
	public function updateGalleryCategory($category){
		return $this->updateGalleryCategories(array($category));
	}
	/**
	 * @name            updateGalleryCategoryLocalizations()
	 *
	 * @since           1.1.7
	 * @version         1.1.7
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array 			$collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateGalleryCategoryLocalizations($collection) {
		$timeStamp = time();
		/** Parameter must be an array */
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$updatedItems = array();
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
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}
	/**
	 * @name            updateGalleryCategories()
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array			$collection
	 *
	 * @return          array           $response
	 */
	public function updateGalleryCategories($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		$localizations = array();
		$updatedItems = array();
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
							$localizations = array();
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
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}

	/**
	 * @name            deleteGalleryCategory ()
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->deleteGalleryCategories()
	 *
	 * @param           mixed           $category
	 *
	 * @return          mixed           $response
	 */
	public function deleteGalleryCategory($category){
		return $this->deleteGalleryCategories(array($category));
	}
	/**
	 * @name            deleteGalleryCategories()
	 *                  Deletes provided gallery categories from database.
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Said İmamoğlu
	 *
	 * @param           array           $collection
	 *
	 * @return          array           $response
	 */
	public function deleteGalleryCategories($collection){
		$timeStamp = time();
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
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();
		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}

	/**
	 * @name            addCategoriesToProduct()
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array           $set
	 * @param           mixed           $gallery
	 *
	 * @return          array           $response
	 */
	public function addCategoriesToGallery($set, $gallery){
		$timeStamp = time();
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
			$aopCollection = array();
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
			return new ModelResponse($aopCollection, count($aopCollection), 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}

	/**
	 * @name            listGalleryCategories()
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->resetResponse()
	 * @use             $this->createException()
	 *
	 * @param           array 	$filter
	 * @param           array 	$sortOrder
	 * @param           array 	$limit
	 *
	 * @return          array   $response
	 */
	public function listGalleryCategories($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
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

		$categories = array();
		$unique = array();
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
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($categories, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @name 			getGalleryCategory()
	 *
	 * @since			1.0.0
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->listProducts()
	 *
	 * @param           mixed           $category
	 *
	 * @return          mixed           $response
	 */
	public function getGalleryCategory($category) {
		$timeStamp = time();
		if($category instanceof BundleEntity\GalleryCategory){
			return new ModelResponse($category, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
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
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}
		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getGalleryCategoryByUrlKey()
	 *
	 * @since           1.1.4
	 * @version         1.1.5
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed 			$urlKey
	 * @param			mixed			$language
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getGalleryCategoryByUrlKey($urlKey, $language = null){
		$timeStamp = time();
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
		$response->stats->execution->end = time();

		return $response;
	}

	/**
	 * @name            doesGalleryCategoryExist()
	 *                  Checks if entry exists in database.
	 *
	 * @since           1.0.9
	 * @version         1.0.9
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->resetResponse()
	 * @use             $this->getGalleryCategory()
	 *
	 * @param           mixed           $category
	 * @param           bool            $bypass
	 *
	 * @return          mixed           $response
	 */
	public function doesGalleryCategoryExist($category, $bypass = false) {
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
	 * @name            isCategoryAssociatedWithGallery()
	 *
	 * @since           1.0.9
	 * @version         1.1.9
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed 		$category
	 * @param           mixed 		$gallery
	 * @param           bool 		$bypass
	 *
	 * @return          mixed       bool or $response
	 */
	public function isCategoryAssociatedWithGallery($category, $gallery, $bypass = false){
		$timeStamp = time();
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
		return new ModelResponse($found, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @name            listGalleriesOfCategory()
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->listGalleryCategories()
	 *
	 * @param           mixed   $category
	 * @param           array 	$filter
	 * @param           array 	$sortOrder
	 * @param           array 	$limit
	 *
	 * @return          array
	 *
	 */
	public function listGalleriesOfCategory($category, $filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
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

		$collection= array();
		if (count($result) > 0) {
			foreach ($result as $cog) {
				$collection[] = $cog->getGallery()->getId();
			}
		}
		if (count($collection) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
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
	 * @name            listGalleriesOfCategory()
	 *
	 * @since           1.0.9
	 * @version         1.1.4
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->listGalleryCategories()
	 *
	 * @param           mixed   $gallery
	 * @param           array   $filter
	 * @param           array   $sortOrder
	 * @param           array   $limit
	 *
	 * @return          array
	 *
	 */
	public function listCategoriesOfGallery($gallery, $filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
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

		$collection= array();
		if (count($result) > 0) {
			foreach ($result as $cog) {
				$collection[] = $cog->getCategory()->getId();
			}
		}
		if (count($collection) < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
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
	 * @name        listGalleryCategoriesInLocale()
	 *
	 * @since       1.1.2
	 * @version     1.1.4
	 *
	 * @author      Said İmamoglu
	 *
	 * @param       mixed   $locale
	 * @param       array   $filter
	 * @param       array   $sortOrder
	 * @param       array   $limit
	 *
	 * @return      array
	 *
	 */
	public function listGalleryCategoriesInLocales($locale,$filter=array(),$sortOrder = array(),$limit = array()){
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
/**
 * Change Log
 * **************************************
 * v1.2.3                      20.11.2015
 * Can Berkol
 * **************************************
 * BF :: 4069378 :: listMediaOfGallery() and listMediaOfGalleryWithStatus() fixed to support gallery_media sort_order field.
 *
 * **************************************
 * v1.2.2                      19.09.2015
 * Can Berkol
 * **************************************
 * BF :: 3949800 :: addFilesToCategory() was setting default status to 'a'. It is fixed to 's'.
 *
 * **************************************
 * v1.2.1                      19.08.2015
 * Can Berkol
 * **************************************
 * BF :: addCategoriesToGallery() fixed.
 *
 * **************************************
 * v1.2.0                      18.08.2015
 * Can Berkol
 * **************************************
 * BF :: gallery and gallery category active localization methods have been fixed.
 * BF :: db_connection is replaced with dbConnection.
 * BF :: addMediaGallery now can handle recently added status field.
 * FR :: listGalleryMedia() method added. This method returns GalleryMedia entities.
 * FR :: updateGalleryMedia() method added.
 *
 * **************************************
 * v1.1.9                      16.08.2015
 * Can Berkol
 * **************************************
 * BF :: Fix for "Error: Invalid PathExpression. Must be a StateFieldPathExpression." applied.
 *
 * **************************************
 * v1.1.8                      12.08.2015
 * Can Berkol
 * **************************************
 * FR :: listGalleries() can now handle 'sort_order' field as an order column.
 *
 * **************************************
 * v1.1.7                      09.08.2015
 * Can Berkol
 * **************************************
 * FR :: updateGalleryLocalizations() added.
 * FR :: updateGalleryCategoryLocalizations() added.
 *
 * **************************************
 * v1.1.6                      06.08.2015
 * Can Berkol
 * **************************************
 * FR :: listMediaOfGalleryWithStatus()
 * FR :: listPublishedMediaOfGallery()
 *
 * **************************************
 * v1.1.5                      14.07.2015
 * Said İmamoğlu
 * **************************************
 * BF :: Entity namespaces were wrong in getGalleryCategoryByUrlKey() method. Fixed
 *
 * **************************************
 * v1.1.4                      23.06.2015
 * Can Berkol
 * **************************************
 * FR :: Made compatible with Core 3.3.
 *
 * **************************************
 * v1.1.3                      Can Berkol
 * 21.08.2014
 * **************************************
 * A addLocalesToGallery()
 * A addLocalesToGalleryCategory()
 * A isGalleryAssociatedWithLocale()
 * A isGalleryCategoryAssociatedWithLocale()
 * A listActiveLocalesOfGallery()
 * A listActiveLocalesOfGalleryCategory()
 * A removeLocalesFromGallery()
 * A removeLocalesFromGalleryCategory()
 * A validateAndGetLocale()
 * U __construct()
 *
 * **************************************
 * v1.1.2                      Can Berkol
 * 25.07.2014
 * **************************************
 * B listGalleryCategories()
 *
 * **************************************
 * v1.1.1                      Said İmamoğlu
 * 18.07.2014
 * **************************************
 * A listGalleryCategoriesInLocales()
 * **************************************
 * v1.1.1                      Can Berkol
 * 16.07.2014
 * **************************************
 * A listDocumentsOfGallery()
 * A listVideosOfGallery()
 *
 * **************************************
 * v1.0.9                   Said İmamoğlu
 * 14.07.2014
 * **************************************
 * A addCategoriesToGallery()
 * A doesGalleryCategoryExist()
 * A insertGalleryCategory()
 * A insertGalleryCategories()
 * A insertGalleryCategoryLocalizations()
 * A deleteGalleryCategory()
 * A deleteGalleryCategories()
 * A getGalleryCategory()
 * A isCategoryAssociatedWithGallery()
 * A listGalleryCategories()
 * A listGalleriesOfCategory()
 * A listCategoriesOfGallery()
 * A validateAndGetGallery()
 * A validateAndGetGalleryCategory()
 * A updateGalleryCategory()
 * A updateGalleryCategories()
 *
 * **************************************
 * v1.0.8                      Can Berkol
 * 09.06.2014
 * **************************************
 * A listAllAudioOfGallery()
 * B listMediaOfGallery()
 *
 * **************************************
 * v1.0.7                      Can Berkol
 * 30.05.2014
 * **************************************
 * A countTotalAudioInGallery()
 * A countTotalDocumentsInGallery()
 * A countTotalMediaInGallery()
 * A getRandomImageFromGallery()
 * A getRandomMediaFromGallery()
 * A isGalleryAssociatedWithFile()
 * A listMediaOfAllGalleries()
 * D getNext... and similar methods (were all terribly codded + no more immediate need)
 * D isFileAssociatedWithGallery()
 * D listAll... methods
 * D listGalleryMedias()
 * D listFilesWithType()
 * U countTotalImagesInGallery()
 * U doesGalleryExist()
 * U getGalleryLocalization()
 * U listMediaOfGallery()
 * U listImagesOfAllGalleries()
 * U listImagesOfGallery()
 * U listGalleriesOfSite()
 * U listGalleriesAdded... methods
 * U listGalleriesUnpublished... methods
 * U listGalleriesWith... methods
 *
 * **************************************
 * v1.0.6                   Said İmamoğlu
 * 08.04.2014
 * **************************************
 * A countTotalImagesInGallery()
 * A listImagesInGalleryBy()
 * A getPrevImageInGallery()
 * A getNextImageInGallery()
 *
 * **************************************
 * v1.0.5                      Can Berkol
 * 20.02.2014
 * **************************************
 * A addFileToGallery()
 * U removeFilesFormGallery()
 *
 * **************************************
 * v1.0.3                      Can Berkol
 * 11.02.2014
 * **************************************
 * A getGalleryMedia()
 * A updateGalleryMedia()
 *
 * **************************************
 * v1.0.2                      Can Berkol
 * 30.01.2014
 * **************************************
 * A deleteGalleries()
 * A deleteGallery()
 * A insertGallery()
 * A insertGalleryLocalizations()
 * A insertyGalleries()
 * A updateGallery()
 * A updateGalleries()
 *
 */