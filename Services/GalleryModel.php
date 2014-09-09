<?php
/**
 * GalleryModel Class
 *
 * This class acts as a database proxy model for GalleryBundle functionalities.
 *
 * @vendor      BiberLtd
 * @package		Core\Bundles\GalleryBundle
 * @subpackage	Services
 * @name	    FileManagementModel
 *
 * @author		Can Berkol
 * @author      Said Imamoglu
 *
 * @copyright   Biber Ltd. (www.biberltd.com)
 *
 * @version     1.1.1
 * @date        16.07.2014
 *
 * =============================================================================================================
 * !! INSTRUCTIONS ON IMPORTANT ASPECTS OF MODEL METHODS !!!
 *
 * Each model function must return a $response ARRAY.
 * The array must contain the following keys and corresponding values.
 *
 * $response = array(
 *              'result'    =>   An array that contains the following keys:
 *                               'set'         Actual result set returned from ORM or null
 *                               'total_rows'  0 or number of total rows
 *                               'last_insert_id' The id of the item that is added last (if insert action)
 *              'error'     =>   true if there is an error; false if there is none.
 *              'code'      =>   null or a semantic and short English string that defines the error concanated
 *                               with dots, prefixed with err and the initials of the name of model class.
 *                               EXAMPLE: err.amm.action.not.found success messages have a prefix called scc..
 *
 *                               NOTE: DO NOT FORGET TO ADD AN ENTRY FOR ERROR CODE IN BUNDLE'S
 *                               RESOURCES/TRANSLATIONS FOLDER FOR EACH LANGUAGE.
 *
 */

namespace BiberLtd\Core\Bundles\GalleryBundle\Services;

/** Extends CoreModel */
use BiberLtd\Core\CoreModel;
/** Entities to be used */
use BiberLtd\Core\Bundles\GalleryBundle\Entity as BundleEntity;
use BiberLtd\Core\Bundles\FileManagementBundle\Entity as FileBundleEntity;
/** Helper Models */
use BiberLtd\Core\Bundles\GalleryBundle\Services as SMMService;
use BiberLtd\Core\Bundles\FileManagementBundle\Services as FMMService;
/** Core Service */
use BiberLtd\Core\Services as CoreServices;
use BiberLtd\Core\Exceptions as CoreExceptions;

class GalleryModel extends CoreModel {
    /**
     * @name            __construct()
     *                  Constructor.
     *
     * @author          Said Imamoglu
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @param           object          $kernel
     * @param           string          $db_connection  Database connection key as set in app/config.yml
     * @param           string          $orm            ORM that is used.
     */

    /** @var $by_opitons handles by options */
    public $by_opts = array('entity', 'id', 'code', 'url_key', 'post');

    /* @var $type must be [i=>image,s=>software,v=>video,f=>flash,d=>document,p=>package] */
    public $type_opts = array('m' => 'media', 'i' => 'image', 'a' => 'audio', 'v' => 'video', 'f' => 'flash', 'd' => 'document', 'p' => 'package', 's' => 'software');
    public $eq_opts = array('after', 'before', 'between', 'on', 'more', 'less', 'eq');

    public function __construct($kernel, $db_connection = 'default', $orm = 'doctrine') {
        parent::__construct($kernel, $db_connection, $orm);

        /**
         * Register entity names for easy reference.
         */
        $this->entity = array(
            'categories_of_gallery' => array('name' => 'GalleryBundle:CategoriesOfGallery', 'alias' => 'cog'),
            'file' => array('name' => 'FileManagementBundle:File', 'alias' => 'f'),
            'gallery' => array('name' => 'GalleryBundle:Gallery', 'alias' => 'g'),
            'gallery_category' => array('name' => 'GalleryBundle:GalleryCategory', 'alias' => 'gc'),
            'gallery_category_localization' => array('name' => 'GalleryBundle:GalleryCategoryLocalization', 'alias' => 'gcl'),
            'gallery_localization' => array('name' => 'GalleryBundle:GalleryLocalization', 'alias' => 'gl'),
            'gallery_media' => array('name' => 'GalleryBundle:GalleryMedia', 'alias' => 'gm'),
        );
    }

    /**
     * @name            __destruct()
     *                  Destructor.
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
     *                  Adds a single file to gallery.
     *
     * @since		    1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->addFilesToGallery()
     *
     * @param           array           $file          Collection consists one of the following: 'entity' or entity 'id'
     *                                                  Contains an array with two keys: file, and sortorder
     * @param           mixed           $gallery        'entity' or 'entity' id.
     *
     * @return          array           $response
     */
    public function addFileToGallery($file, $gallery) {
        return $this->addFilesToGallery(array($file), $gallery);
    }

    /**
     * @name            addFilesToGallery()
     *                  Add files into gallery.
     *
     * @since           1.0.0
     * @version         1.0.7
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
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        $count = 0;
        /** remove invalid file entries */
        foreach ($files as $file) {
            if (!is_numeric($file['file']) && !$file['file'] instanceof FileBundleEntity\File) {
                unset($files[$count]);
            }
            $count++;
        }
        /** issue an error onlu if there is no valid file entries */
        if (count($files) < 1) {
            return $this->createException('InvalidParameterException', '$files', 'err.invalid.parameter.files');
        }
        unset($count);
        if (!is_numeric($gallery) && !$gallery instanceof BundleEntity\Gallery) {
            return $this->createException('InvalidParameterException', '$gallery', 'err.invalid.parameter.product');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($gallery)) {
            $response = $this->getGallery($gallery, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Gallery', 'err.db.gallery.notexist');
            }
            $gallery = $response['result']['set'];
        }
        $fmmodel = new FMMService\FileManagementModel($this->kernel, $this->db_connection, $this->orm);

        $fop_collection = array();
        $count = 0;
        /** Start persisting files */
        foreach ($files as $file) {
            /** If no entity s provided as file we need to check if it does exist */
            if (is_numeric($file['file'])) {
                $response = $fmmodel->getFile($file['file'], 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'File', 'err.db.file.notexist');
                }
                $file['file'] = $response['result']['set'];
            }
            /** Check if association exists */
            if ($this->isFileAssociatedWithGallery($file['file']->getId(), $gallery, true)) {
                new CoreExceptions\DuplicateAssociationException($this->kernel, 'File => Gallery');
                $this->response['code'] = 'err.db.entry.notexist';
                /** If file association already exist move silently to next file */
                break;
            }
            $gm = new BundleEntity\GalleryMedia();
            $now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
            $gm->setFile($file['file'])->setGallery($gallery)->setDateAdded($now);
            if (!is_null($file['sortorder'])) {
                $gm->setSortOrder($file['sortorder']);
            } else {
                $gm->setSortOrder($this->getMaxSortOrderOfGalleryMedia($gallery, true) + 1);
            }
            $gm->setCountView(0);
            $gm->setType($file['file']->getType());
            /** persist entry */
            $this->em->persist($gm);
            $gm_collection[] = $gm;
            $count++;
        }
        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        } else {
            $this->response['error'] = true;
            $this->response['code'] = 'err.db.insert.failed';
            return $this->response;
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $gm_collection,
                'total_rows' => $count,
                'last_insert_id' => null, //$fop->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        unset($count, $gm_collection);
        return $this->response;
    }
    /**
     * @name 			countDistinctMediaTotal()
     *  				Returns the count of total but distinct media. Count same item only once even if it is
     *                  associated with multiple galleries.
     *
     * @since			1.0.2
     * @version         1.0.2
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @return          array           $response
     */
    public function countDistinctMediaTotal() {
        $this->resetResponse();
        $query_str = 'SELECT COUNT( DISTINCT '. $this->entity['gallery_media']['alias'].')'
            .' FROM '.$this->entity['gallery_media']['name'].' '.$this->entity['gallery_media']['alias'];

        $query = $this->em->createQuery($query_str);

        /**
         * Prepare & Return Response
         */
        $result = $query->getSingleScalarResult();

        $this->response = array(
            'result' => array(
                'set' => $result,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            countTotalAudioInGallery()
     *                  Counts total audio in gallery.
     *
     * @since           1.0.7
     * @version         1.0.7
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
     *                  Counts total audio in gallery.
     *
     * @since           1.0.7
     * @version         1.0.7
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
     *                  Counts total images in gallery.
     *
     * @since           1.0.6
     * @version         1.0.7
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
     *                  Counts total media in gallery.
     *
     * @since           1.0.6
     * @version         1.0.7
     *
     * @author          Can Berkol
     *
     * @param           mixed       $gallery
     * @param           string      $mediaType      all, i, a, v, f, d, p, s
     *
     * @return          array           $response
     */
    public function countTotalMediaInGallery($gallery, $mediaType = 'all'){
        $this->resetResponse();
        $allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
        if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
            return $this->createException('InvalidParameterValueException', 'i, a, v, f, d, p, or s', 'err.invalid.parameter.mediaType');
        }
        if(!$gallery instanceof BundleEntity\Gallery && !is_numeric($gallery)){
            return $this->createException('InvalidParameterException', 'Gallery entity or numeric value representing row id', 'err.invalid.parameter.gallery');
        }
        if(is_numeric($gallery)){
            $response = $this->getGallery(($gallery));
            $gallery = $response['result']['set'];
        }
        $qStr = 'SELECT COUNT('.$this->entity['gallery_media']['alias'].')'
            .' FROM '.$this->entity['gallery_media']['name'].' '.$this->entity['gallery_media']['alias']
            .' WHERE '.$this->entity['gallery_media']['alias'].'.gallery = '.$gallery->getId();
        unset($response, $gallery);
        $whereStr = '';
        if($mediaType != 'all'){
            $whereStr = ' AND '.$this->entity['gallery_media']['alias'].".type = '".$mediaType."'";
        }
        $qStr .= $whereStr;

        $query = $this->em->createQuery($qStr);

        $result = $query->getSingleScalarResult();

        $this->response = array(
            'result' => array(
                'set' => $result,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            countTotalImagesInGallery ()
     *                  Counts total images in gallery.
     *
     * @since           1.0.7
     * @version         1.0.7
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
     *  				Deletes provided galleries from database.
     *
     * @since			1.0.0
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array           $collection     Collection of Module entities, ids, or codes or url keys
     *
     * @return          array           $response
     */
    public function deleteGalleries($collection) {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterValueException', 'Array', 'err.invalid.parameter.collection');
        }
        $countDeleted = 0;
        foreach($collection as $entry){
            if($entry instanceof BundleEntity\Gallery){
                $this->em->remove($entry);
                $countDeleted++;
            }
            else{
                switch($entry){
                    case is_numeric($entry):
                        $response = $this->getGallery($entry, 'id');
                        break;
                    case is_string($entry):
                        $response = $this->getGallery($entry, 'url_key');
                        break;
                }
                if($response['error']){
                    $this->createException('EntityDoesNotExist', $entry, 'err.invalid.entry');
                }
                $entry = $response['result']['set'];
                $this->em->remove($entry);
                $countDeleted++;
            }
        }
        if($countDeleted < 0){
            $this->response['error'] = true;
            $this->response['code'] = 'err.db.fail.delete';

            return $this->response;
        }
        $this->em->flush();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => $countDeleted,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.deleted',
        );
        return $this->response;
    }

    /**
     * @name 			deleteGallery()
     *  				Deletes an existing gallery from database.
     *
     * @since			1.0.0
     * @version         1.0.3
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
     *  				Checks if entry exists in database.
     *
     * @since			1.0.0
     * @version         1.0.7
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     *
     * @use             $this->getGallery()
     *
     * @param           mixed           $gallery        id, url_key
     *
     * @param           bool            $bypass         If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesGalleryExist($gallery, $bypass = false) {
        $this->resetResponse();
        $exist = false;

        $response = $this->getGallery($gallery, 'id');
        if($response['error']){
            $response = $this->getGallery($gallery, 'url_key');
        }

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = true;
            $error = false;
        } else {
            $exist = false;
            $error = true;
        }

        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => $error,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name 		    doesGalleryExist()
     *                  Checks if entry exists in database.
     *
     * @since			1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @use             $this->getGalleryMedia()
     *
     * @param           mixed           $galleryMedia   id
     * @param           string          $by             by options
     *
     * @param           bool            $bypass         If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesGalleryMediaExist($galleryMedia, $by = 'id', $bypass = false) {
        $this->resetResponse();
        $exist = false;

        $response = $this->getGalleryMedia($galleryMedia, $by);

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = true;
        }
        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name 			getGallery()
     *  				Returns details of a gallery.
     *
     * @since			1.0.0
     * @version         1.0.1
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->listProducts()
     *
     * @param           mixed           $gallery            id, url_key
     * @param           string          $by                 entity, id, url_key
     *
     * @return          mixed           $response
     */
    public function getGallery($gallery, $by = 'id') {
        $this->resetResponse();
        $by_opts = array('id', 'url_key');
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidParameterValueException', implode(',', $by_opts), 'err.invalid.parameter.by');
        }
        if (!is_object($gallery) && !is_numeric($gallery) && !is_string($gallery)) {
            return $this->createException('InvalidParameterException', 'Gallery', 'err.invalid.parameter.gallery');
        }
        if (is_object($gallery)) {
            if (!$gallery instanceof BundleEntity\Gallery) {
                return $this->createException('InvalidParameterException', 'Gallery', 'err.invalid.parameter.gallery');
            }
            /**
             * Prepare & Return Response
             */
            $this->response = array(
	            'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $gallery,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }
        switch($by){
            case 'url_key':
                $column = $this->entity['gallery_localization']['alias'] . '.' . $by;
                break;
            default:
                $column = $this->entity['gallery']['alias'] . '.' . $by;
                break;
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $column, 'comparison' => '=', 'value' => $gallery),
                )
            )
        );

        $response = $this->listGalleries($filter, null, array('start' => 0, 'count' => 1));
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            getGalleryLocalization
     *                  Returns given gallery's localizations.
     *
     * @since           1.0.0
     * @version         1.0.7
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           mixed           $gallery
     * @param           mixed           $language
     *
     * @return          array           $response
     */
    public function getGalleryLocalization($gallery, $language) {
        $this->resetResponse();
        if (!$gallery instanceof BundleEntity\Gallery && !is_numeric($gallery)) {
            return $this->createException('InvalidParameterException', 'Gallery', 'err.invalid.parameter.gallery');
        }
        if(is_numeric($gallery)){
            $response = $this->getGallery($gallery);
            if($response['error']){
                return $this->createException('InvalidParameterException', 'Gallery', 'err.invalid.parameter.gallery');
            }
            $gallery = $resposne['result']['set'];
        }
        /** Parameter must be an array */
        if (!$language instanceof MLSEntity\Language && !is_numeric($language) && !is_string($language)) {
            return $this->createException('InvalidParameterException', 'Language', 'err.invalid.parameter.language');
        }
        if(is_numeric($language)){
            $mlsModel = $sModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
            $response = $mlsModel->getLanguage($language, 'id');
            if($response['error']){
                return $this->createException('InvalidParameterException', 'Language id', 'err.invalid.parameter.language');
            }
            $language = $resposne['result']['set'];
        }
        else if(is_string($language)){
            $mlsModel = $sModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
            $response = $mlsModel->getLanguage($language, 'iso_code');
            if($response['error']){
                return $this->createException('InvalidParameterException', 'Language iso code', 'err.invalid.parameter.language');
            }
            $language = $resposne['result']['set'];
        }
        $q_str = 'SELECT ' . $this->entity['gallery_localization']['alias'] . ' FROM ' . $this->entity['gallery_localization']['name'] . ' ' . $this->entity['gallery_localization']['alias']
            . ' WHERE ' . $this->entity['gallery_localization']['alias'] . '.gallery = ' . $gallery->getId()
            . ' AND ' . $this->entity['gallery_localization']['alias'] . '.language = ' . $language->getId();

        $query = $this->em->createQuery($q_str);
        /**
         * 6. Run query
         */
        $result = $query->getResult();
        /**
         * Prepare & Return Response
         */
        $total_rows = count($result);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => $total_rows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist.',
        );
        return $this->response;
    }
    /**
     * @name 			getGalleryMedia()
     *  				Returns GalleryMedia entry.
     *
     * @since			1.0.3
     * @version         1.0.3
     * @author          Can Berkol

     * @param           mixed           $file           id, entity
     * @param           mixed           $gallery        id, entity
     *
     * @return          mixed           $response
     */
    public function getGalleryMedia($file, $gallery) {
        $this->resetResponse();
        if($file instanceof FileBundleEntity\File){
            $file = $file->getId();
        }
        if($gallery instanceof BundleEntity\Gallery){
            $gallery = $file->getId();
        }
        $qStr = 'SELECT '.$this->entity['gallery_media']['alias']
                    .' FROM '.$this->entity['gallery_media']['name'].' '.$this->entity['gallery_media']['alias']
                    .' WHERE '.$this->entity['gallery_media']['alias'].'.gallery = '.$gallery
                    .' AND '.$this->entity['gallery_media']['alias'].'.file = '.$file;

        $query = $this->em->createQuery($qStr);

        $response = $query->getSingleResult();

        if(!$response){
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => 0,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name 		    getMaxSortOrderOfGalleryMedia()
     *                  Returns the largest sort order value for a given gallery from gallery_media table.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->getGallery()
     *
     * @throws          InvalidParameterException
     *
     * @param           mixed           $gallery            entity, id
     * @param           bool            $bypass             if set to true return bool instead of response
     *
     * @return          mixed           bool | $response
     */
    public function getMaxSortOrderOfGalleryMedia($gallery, $bypass = false) {
        $this->resetResponse();
        if (!is_object($gallery) && !is_numeric($gallery) && !is_string($gallery)) {
            return $this->createException('InvalidParameterException', 'Gallery', 'err.invalid.parameter.product');
        }
        if (is_object($gallery)) {
            if (!$gallery instanceof BundleEntity\Gallery) {
                return $this->createException('InvalidParameterException', 'Gallery', 'err.invalid.parameter.product');
            }
        } else {
            /** if numeric value given check if category exists */
            $response = $this->getGallery($gallery, 'id');
            if ($response['error']) {
                return $this->createException('InvalidParameterException', 'Gallery', 'err.invalid.parameter.product');
            }
            $gallery = $response['result']['result'];
        }
        $q_str = 'SELECT MAX('.$this->entity['gallery_media']['alias'].'.sort_order) FROM ' . $this->entity['gallery_media']['name'] .' '. $this->entity['gallery_media']['alias']
            . ' WHERE ' . $this->entity['gallery_media']['alias'] . '.gallery = ' . $gallery->getId();
        $query = $this->em->createQuery($q_str);
        $result = $query->getSingleScalarResult();

        if ($bypass) {
            return $result;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            insertGalleries()
     *
     * @since           1.0.0
     * @version         1.0.7
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
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterException', 'Array', 'err.invalid.parameter.collection');
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
                            $response = $fModel->getFile($value, 'id');
                            if(!$response['error']){
                                $entity->$set($response['result']['set']);
                            }
                            else{
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
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
            else{
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if($countInserts > 0){
            $this->em->flush();
        }
        /** Now handle localizations */
        if($countInserts > 0 && $countLocalizations > 0){
            $this->insertGalleryLocalizations($localizations);
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }
    /**
     * @name            insertGallery()
     *                  Inserts single gallery into database
     *
     * @since           1.0.0
     * @version         1.0.2
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
     *  				Inserts one or more gallery localizations into database.
     *
     * @since			1.0.1
     * @version         1.0.1
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array           $collection        Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertGalleryLocalizations($collection){
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterException', 'Array', 'err.invalid.parameter.collection');
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
                    if(!$response['error']){
                        $entity->setLanguage($response['result']['set']);
                    }
                    else{
                        break 1;
                    }
                    foreach($data as $column => $value){
                        $set = 'set'.$this->translateColumnName($column);
                        $entity->$set($value);
                    }
                    $this->em->persist($entity);
                }
                $insertedItems[] = $entity;
                $countInserts++;
            }
        }
        if($countInserts > 0){
            $this->em->flush();
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }
    /**
     * @name 		    isGalleryAssocitedWithFile()
     *  		        Checks if the gallery is already associated with the file.
     *
     * @since		    1.0.7
     * @version         1.0.7
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           mixed           $file           'entity' or 'entity' id
     * @param           mixed           $gallery        'entity' or 'entity' id.
     * @param           bool            $bypass         true or false
     *
     * @return          mixed           bool or $response
     */
    public function isFileAssociatedWithGallery($file, $gallery, $bypass = false) {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        if (!is_numeric($file) && !$file instanceof FileBundleEntity\File) {
            return $this->createException('InvalidParameterException', 'File', 'err.invalid.parameter.file');
        }

        if (!is_numeric($gallery) && !$gallery instanceof BundleEntity\Gallery) {
            return $this->createException('InvalidParameterException', 'Gallery', 'err.invalid.parameter.product');
        }
        $fmmodel = new FMMService\FileManagementModel($this->kernel, $this->db_connection, $this->orm);
        /** If no entity is provided as file we need to check if it does exist */
        if (is_numeric($file)) {
            $response = $fmmodel->getFile($file, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'File', 'err.db.file.notexist');
            }
            $file = $response['result']['set'];
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($gallery)) {
            $response = $this->getGallery($gallery, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Gallery', 'err.db.product.notexist');
            }
            $gallery = $response['result']['set'];
        }
        $found = false;

        $q_str = 'SELECT COUNT(' . $this->entity['gallery_media']['alias'] . ')'
            . ' FROM ' . $this->entity['gallery_media']['name'] . ' ' . $this->entity['gallery_media']['alias']
            . ' WHERE ' . $this->entity['gallery_media']['alias'] . '.file = ' . $file->getId()
            . ' AND ' . $this->entity['gallery_media']['alias'] . '.gallery = ' . $gallery->getId();
        $query = $this->em->createQuery($q_str);

        $result = $query->getSingleScalarResult();

        if ($result > 0) {
            $found = true;
            $code = 'scc.db.entry.exist';
        } else {
            $code = 'scc.db.entry.notexist';
        }

        if ($bypass) {
            return $found;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $found,
                'total_rows' => $result,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => $code,
        );
        return $this->response;
    }
    /**
     * @name 			listAllAudioOfGallery()
     *  				List all audio files that belong to a certain gallery and that match to a certain criteria.
     *
     * @since			1.0.8
     * @version         1.0.8
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @param           mixed           $gallery
     * @param           array           $sortorder
     *
     * @return          array           $response
     */
    public function listAllAudioOfGallery($gallery, $sortorder = null) {
        return $this->listMediaOfGallery($gallery, 'a', $sortorder);
    }
    /**
     * @name 			listAllGalleries()
     *  				List all galleries from database.
     *
     * @since			1.0.1
     * @version         1.0.1
     * @author          Can Berkol
     *
     * @uses            $this->listGalleries()
     *
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listAllGalleries($sortorder = null, $limit = null) {
        return $this->listGalleries(null, $sortorder, $limit);
    }
    /**
     * @name 			listAllImagesOfGallery()
     *  				List all image files that belong to a certain gallery and that match to a certain criteria.
     *
     * @since			1.0.1
     * @version         1.0.7
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @param           mixed           $gallery
     * @param           array           $sortorder
     *
     * @return          array           $response
     */
    public function listAllImagesOfGallery($gallery, $sortorder = null) {
        return $this->listMediaOfGallery($gallery, 'i', $sortorder);
    }
    /**
     * @name 			listDocumentsOfGallery()
     *  				List all documents files that belong to a certain gallery and that match to a certain criteria.
     *
     * @since			1.1.1
     * @version         1.1.1
     *
     * @author          Can Berkol
     *
     * @param           mixed           $gallery
     * @param           array           $sortorder
     * @param           array           $limit
     *
     * @return          array           $response
     */
    public function listDocumentsOfGallery($gallery, $sortorder = null, $limit = null) {
        return $this->listMediaOfGallery($gallery, 'd', $sortorder, $limit);
    }
    /**
     * @name 			listGalleries()
     *  				List galleries from database based on a variety of conditions.
     *
     * @since			1.0.1
     * @version         1.0.7
     * @author          Can Berkol
     *
     * @use             $this->createException
     *
     * @param           array           $filter
     * @param           array           $sortorder
     * @param           array           $limit
     * @param           string          $query_str             If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listGalleries($filter = null, $sortorder = null, $limit = null, $query_str = null) {
        $this->resetResponse();
        if (!is_array($sortorder) && !is_null($sortorder)) {
            return $this->createException('InvalidSortOrderException', '', 'err.invalid.parameter.sortorder');
        }
        /**
         * Add filter checks to below to set join_needed to true.
         */
        /**         * ************************************************** */
        $orderStr = '';
        $whereStr = '';
        $groupStr = '';
        $filterStr = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($query_str)) {
            $query_str = 'SELECT ' . $this->entity['gallery_localization']['alias']
                . ' FROM ' . $this->entity['gallery_localization']['name'] . ' ' . $this->entity['gallery_localization']['alias']
                . ' JOIN ' . $this->entity['gallery_localization']['alias'] . '.gallery ' . $this->entity['gallery']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortorder != null) {
            foreach ($sortorder as $column => $direction) {
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
                        $column = $this->entity['gallery']['alias'] . '.' . $column;
                        break;
                    case 'tile':
                    case 'url_key':
                    case 'description':
                        $column = $this->entity['gallery_localization']['alias'] . '.' . $column;
                        break;
                }
                $orderStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $orderStr = rtrim($orderStr, ', ');
            $orderStr = ' ORDER BY ' . $orderStr . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filterStr = $this->prepareWhere($filter);
            $whereStr .= ' WHERE ' . $filterStr;
        }

        $query_str .= $whereStr . $groupStr . $orderStr;

        $query = $this->em->createQuery($query_str);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                $this->createException('InvalidLimit', '', 'err.invalid.limit');
            }
        }
        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();

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
        $total_rows = count($galleries);
        if ($total_rows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $galleries,
                'total_rows' => $total_rows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name 		listGalleryAddedAfter()
     *              List galleries that are added after the given date.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesAdded()
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
    public function listGalleryAddedAfter($date, $sortorder = null, $limit = null) {
        return $this->listGalleriesAdded($date, 'after', $sortorder, $limit);
    }
    /**
     * @name 		    listGalleriesAddedBefore()
     *                  List galleries that are added before the given date.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesAdded()
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
    public function listGalleriesAddedBefore($date, $sortorder = null, $limit = null) {
        return $this->listGalleriesAdded($date, 'before', $sortorder, $limit);
    }

    /**
     * @name 			listGalleriesAddedBetween()
     *  				List products that are added between two dates.
     *
     * @since			1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesAdded()
     *
     * @param           array           $dates                  The earlier and the later dates.
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesAddedBetween($dates, $sortorder = null, $limit = null) {
        return $this->listGalleriesAdded($dates, 'between', $sortorder, $limit);
    }
    /**
     * @name 			listGalleriesUpdatedAfter()
     *  				List products that are updated after the given date.
     *
     * @since			1.0.0
     * @version         1.0.0
     * @author          Can Berkol
     *
     * @uses            $this->listGalleriesUpdated()
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
    public function listGalleriesUpdatedAfter($date, $sortorder = null, $limit = null) {
        return $this->listGalleriesUpdated($date, 'after', $sortorder, $limit);
    }

    /**
     * @name 			listGalleriesUpdatedBefore()
     *  				List products that are updated before the given date.
     *
     * @since			1.0.0
     * @version         1.0.0
     * @author          Can Berkol
     *
     * @uses            $this->listGalleriesUpdated()
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
    public function listGalleriesUpdatedBefore($date, $sortorder = null, $limit = null) {
        return $this->listGalleriesUpdated($date, 'before', $sortorder, $limit);
    }

    /**
     * @name 			listGalleriesUpdatedBetween()
     *  				List products that are updated between two dates.
     *
     * @since			1.0.0
     * @version         1.0.0
     * @author          Can Berkol
     *
     * @uses            $this->listGalleriesUpdated()
     *
     * @param           array           $dates                  The earlier and the later dates.
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesUpdatedBetween($dates, $sortorder = null, $limit = null) {
        return $this->listGalleriesUpdated($dates, 'between', $sortorder, $limit);
    }
    /**
     * @name 			listGalleriesUnpublishedAfter()
     *  				List products that are updated after the given date.
     *
     * @since			1.0.0
     * @version         1.0.7
     * @author          Can Berkol
     *
     * @uses            $this->listGalleriesUnpublished()
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
    public function listGalleriesUnpublishedAfter($date, $sortorder = null, $limit = null) {
        return $this->listGalleriesUnpublished($date, 'after', $sortorder, $limit);
    }

    /**
     * @name 			listGalleriesUnpublishedBefore()
     *  				List products that are updated before the given date.
     *
     * @since			1.0.0
     * @version         1.0.7
     * @author          Can Berkol
     *
     * @uses            $this->listGalleriesUnpublished()
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
    public function listGalleriesUnpublishedBefore($date, $sortorder = null, $limit = null) {
        return $this->listGalleriesUnpublished($date, 'before', $sortorder, $limit);
    }

    /**
     * @name 			listGalleriesUnpublishedBetween()
     *  				List products that are updated between two dates.
     *
     * @since			1.0.0
     * @version         1.0.7
     * @author          Can Berkol
     *
     * @uses            $this->listGalleriesUnpublished()
     *
     * @param           array           $dates                  The earlier and the later dates.
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesUnpublishedBetween($dates, $sortorder = null, $limit = null) {
        return $this->listGalleriesUnpublished($dates, 'between', $sortorder, $limit);
    }
    /**
     * @name            listImagesOfAllGalleries()
     *                  Lists one ore more random media from gallery
     *
     * @since           1.0.1
     * @version         1.0.7
     *
     * @author          Said İmamoğlu
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $fModel->listFiles()
     *
     * @param           integer     $count          Limit the number of items to be returned
     * @param           array       $sortorder
     * @param           array       $limit
     * @param           array       $filter
     *
     * @return          array           $response
     */
    public function listImagesOfAllGalleries($count = 1, $sortorder = null, $limit = null, $filter = null){
        $mediaType = 'i';
        return $this->listMediaOfAllGalleries($count, $mediaType, $sortorder, $limit, $filter);
    }
    /**
     * @name            listGalleriesOfMedia()
     *                  Lists all galleries that file belongs to.
     *
     * @since           1.0.8
     * @version         1.0.8
     *
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $fModel->listFiles()
     *
     * @param           mixed       $file
     * @param           array       $sortorder
     * @param           array       $limit
     * @param           array       $filter
     *
     * @return          array           $response
     */
    public function listGalleriesOfMedia($file, $sortorder = null, $limit = null, $filter = null){
        $this->resetResponse();
        if(!$file instanceof FileBundleEntity\File && !is_integer($file)){
            return $this->createException('InvalidParameterValueException', 'File entity or integer  representing row id', 'err.invalid.parameter.file');
        }
        if(is_numeric($file)){
            $fModel = $this->kernel->getContainer()->get('filemanagement.model');
            $response = $this->getFile($file);
            if($response['error']){
                return $this->createException('InvalidParameterValueException', 'File entity or integer  representing row id', 'err.invalid.parameter.file');
            }
            $file = $response['result']['set'];
        }
        $qStr = 'SELECT '.$this->entity['gallery_media']['alias']
            .' FROM '.$this->entity['gallery_media']['name'].' '.$this->entity['gallery_media']['alias']
            .' WHERE '.$this->entity['gallery_media']['alias'].'.file = '.$file->getId();
        unset($response, $gallery);

        $query = $this->em->createQuery($qStr);

        $result = $query->getResult();

        $galleryIds = array();
        $totalRows = count($result);

        if($totalRows > 0){
            foreach($result as $gm){
                $galleryIds[] = $gm->getGallery()->getId();
                $this->em->detach($gm);
            }
        }
        else{
            $this->response = array(
                'result' => array(
                    'set' => null,
                    'total_rows' => 0,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }

        $galleryFilter[] = array('glue' => 'and',
                              'condition' => array(
                                  array(
                                      'glue' => 'and',
                                      'condition' => array('column' => 'g.id', 'comparison' => 'in', 'value' => $galleryIds),
                                  )
                              )
        );
        return $this->listGalleries($galleryFilter, $sortorder, $limit);
    }
    /**
     * @name 		    listGalleriesOfSite()
     *                  List galleries with given site value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleries()
     *
     * @param           array           $site                   count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesOfSite($site, $sortorder = null, $limit = null) {
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['gallery']['alias'] . '.site', 'comparison' => '=', 'value' => $site),
                )
            )
        );
        return $this->listGalleries($filter, $sortorder, $limit);
    }
    /**
     * @name 		    listGalleriesWithAudioCount()
     *                  List audio files with count equal to given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           mixed           $count                  count number(s)
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithAudioCount($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('a', 'eq', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithAudioCountBetween()
     *                  List audio files with count in between..
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  The number of count
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithAudioCountBetween($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('a', 'between', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithAudioCountLessThan()
     *                  List audio files with count less than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithAudioCountLessThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('a', 'less', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithAudioCountMoreThan()
     *                  List audio files with count more than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithAudioCountMoreThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('a', 'more', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithDocumentCount()
     *                  List document files with count equal to given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           mixed           $count                  count number(s)
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithDocumentCount($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('d', 'eq', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithDocumentCountBetween()
     *                  List document files with between two given count values.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  The number of count
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithDocumentCountBetween($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('d', 'between', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithDocumentCountLessThan()
     *                  List document files with count less than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithDocumentCountLessThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('d', 'less', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithDocumenCounttMoreThan()
     *                  List document files with count more than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithDocumenCounttMoreThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('d', 'more', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithImageCount()
     *                  List image files that count equal to given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           mixed           $count                  count number(s)
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithImageCount($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('i', 'eq', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithImageCountBetween()
     *                  List image files that between two given count values.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  The number of count
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithImageCountBetween($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('i', 'between', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithImageCountLessThan()
     *                  List image files with count less than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithImageCountLessThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('i', 'less', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithImageCountMoreThan()
     *                  List image files with count more than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithImageCountMoreThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('i', 'more', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithMediaCount()
     *                  List audio files that count equal to given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           mixed           $count                  count number(s)
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithMediaCount($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('m', 'eq', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithMediaCountBetween()
     *                  List media files that between two given count values.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  The number of count
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithMediaCountBetween($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('m', 'between', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithMediaCountLessThan()
     *                  List media files with count less than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithMediaCountLessThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('m', 'less', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithMediaCountMoreThan()
     *                  List media files with count more than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithMediaCountMoreThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('m', 'more', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithVideoCount()
     *                  List video files that count equal to given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           mixed           $count                  count number(s)
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithVideoCount($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('v', 'eq', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithVideoCountBetween()
     *                  List video files that between two given count values.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  The number of count
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithVideoCountBetween($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('v', 'between', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithVideoCountLessThan()
     *                  List video files with count less than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithVideoCountLessThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('v', 'less', $count, $sortorder, $limit);
    }

    /**
     * @name 		    listGalleriesWithVideoCountMoreThan()
     *                  List video files with count more than given value.
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleriesWithTypeCount()
     *
     * @param           array           $count                  count number
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesWithVideoCountMoreThan($count, $sortorder = null, $limit = null) {
        return $this->listGalleriesWithTypeCount('v', 'more', $count, $sortorder, $limit);
    }
    /**
     * @name 			listGalleriesPublishedAfter()
     *  				List products that are updated after the given date.
     *
     * @since			1.0.0
     * @version         1.0.0
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
     *  				List products that are updated before the given date.
     *
     * @since			1.0.0
     * @version         1.0.0
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
    public function listGalleriesPublishedBefore($date, $sortorder = null, $limit = null) {
        return $this->listGalleriesPublished($date, 'before', $sortorder, $limit);
    }

    /**
     * @name 			listGalleriesPublishedBetween()
     *  				List products that are updated between two dates.
     *
     * @since			1.0.0
     * @version         1.0.0
     * @author          Can Berkol
     *
     * @uses            $this->listGalleriesPublished()
     *
     * @param           array           $dates                  The earlier and the later dates.
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    public function listGalleriesPublishedBetween($dates, $sortorder = null, $limit = null) {
        return $this->listGalleriesPublished($dates, 'between', $sortorder, $limit);
    }
    /**
     * @name 			listLastImagesOfAllGalleries()
     *  				List a limited number of images from all available galleries. This function is used for sampling
     *                  purposes.
     *
     * @since			1.0.1
     * @version         1.0.1
     * @author          Can Berkol
     *
     * @use             $this->listImagesOfAllGalleries
     *
     * @param           integer         $limit
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     *
     * @return          array           $response
     */
    public function listLastImagesOfAllGalleries($limit, $sortorder = null) {
        return $this->listImagesOfAllGalleries($sortorder, array('start' => 0, 'count' => $limit));
    }
    /**
     * @name            listMediaOfAllGalleries()
     *                  Lists one ore more random media from gallery
     *
     * @since           1.0.7
     * @version         1.0.7
     *
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $fModel->listFiles()
     *
     * @param           string      $mediaType      all, i, a, v, f, d, p, s
     * @param           array       $sortorder
     * @param           array       $limit
     * @param           array       $filter
     *
     * @return          array           $response
     */
    public function listMediaOfAllGalleries($mediaType = 'all', $sortorder = null, $limit = null, $filter = null){
        $this->resetResponse();
        $allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
        if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
            return $this->createException('InvalidParameterValueException', 'i, a, v, f, d, p, or s', 'err.invalid.parameter.mediaType');
        }
        $qStr = 'SELECT '.$this->entity['gallery_media']['alias']
            .' FROM '.$this->entity['gallery_media']['name'].' '.$this->entity['gallery_media']['alias'];
        unset($response, $gallery);
        $whereStr = '';
        if($mediaType != 'all'){
            $whereStr = ' WHERE '.$this->entity['gallery_media']['alias'].".type = '".$mediaType."'";
        }

        $query = $this->em->createQuery($qStr);

        $result = $query->getResult();

        $fileIds = array();
        $totalRows = count($result);

        if($totalRows > 0){
            foreach($result as $gm){
                $fileIds[] = $gm->getFile()->getId();
            }
        }
        else{
            $this->response = array(
                'result' => array(
                    'set' => null,
                    'total_rows' => 0,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }

        $fileFilter[] = array('glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => 'f.id', 'comparison' => 'in', 'value' => $fileIds),
                )
            )
        );
        $fModel = $this->kernel->getContainer()->get('filemanagement.model');

        return $fModel->listFiles($fileFilter, $sortorder, $limit);
    }
    /**
     * @name            listMediaOfGallery()
     *                  Lists one ore more random media from gallery
     *
     * @since           1.0.7
     * @version         1.0.8
     *
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $fModel->listFiles()
     *
     * @param           mixed       $gallery
     * @param           string      $mediaType      all, i, a, v, f, d, p, s
     * @param           array       $sortorder
     * @param           array       $limit
     * @param           array       $filter
     *
     * @return          array           $response
     */
    public function listMediaOfGallery($gallery, $mediaType = 'all', $sortorder = null, $limit = null, $filter = null){
        $this->resetResponse();
        $allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
        if(!$gallery instanceof BundleEntity\Gallery && !is_numeric($gallery)){
            return $this->createException('InvalidParameterValueException', 'Gallery entity or integer  representing row id', 'err.invalid.parameter.gallery');
        }
        if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
            return $this->createException('InvalidParameterValueException', 'i, a, v, f, d, p, or s', 'err.invalid.parameter.mediaType');
        }
        if(is_numeric($gallery)){
            $response = $this->getGallery($gallery);
            if($response['error']){
                return $this->createException('InvalidParameterValueException', 'Gallery entity or integer  representing row id', 'err.invalid.parameter.gallery');
            }
            $gallery = $response['result']['set'];
        }
        $qStr = 'SELECT '.$this->entity['gallery_media']['alias']
            .' FROM '.$this->entity['gallery_media']['name'].' '.$this->entity['gallery_media']['alias']
            .' WHERE '.$this->entity['gallery_media']['alias'].'.gallery = '.$gallery->getId();
        unset($response, $gallery);
        $whereStr = '';
        if($mediaType != 'all'){
            $whereStr = ' AND '.$this->entity['gallery_media']['alias'].".type = '".$mediaType."'";
        }
        $qStr .= $whereStr;

        $query = $this->em->createQuery($qStr);

        $result = $query->getResult();

        $fileIds = array();
        $totalRows = count($result);

        if($totalRows > 0){
            foreach($result as $gm){
                $fileIds[] = $gm->getFile()->getId();
            }
        }
        else{
            $this->response = array(
                'result' => array(
                    'set' => null,
                    'total_rows' => 0,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }

        $fileFilter[] = array('glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => 'f.id', 'comparison' => 'in', 'value' => $fileIds),
                )
            )
        );
        $fModel = $this->kernel->getContainer()->get('filemanagement.model');

        return $fModel->listFiles($fileFilter, $sortorder, $limit);
    }
    /**
     * @name 			listImagesOfGallery()
     *  				List all image files that belong to a certain gallery and that match to a certain criteria.
     *
     * @since			1.0.1
     * @version         1.0.7
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @param           mixed           $gallery
     * @param           array           $sortorder
     * @param           array           $limit
     *
     * @return          array           $response
     */
    public function listImagesOfGallery($gallery, $sortorder = null, $limit = null) {
        return $this->listMediaOfGallery($gallery, 'i', $sortorder, $limit);
    }
    /**
     * @name            listRandomImagesFromGallery()
     *                  Get one or more random images from gallery.
     *
     * @since           1.0.7
     * @version         1.0.7
     *
     * @author          Can Berkol
     *
     * @param           mixed       $gallery
     * @param           integer     $count          Limit the number of items to be returned
     *
     * @return          array           $response
     */
    public function listRandomImagesFromGallery($gallery, $count = 1){
        return $this->listRandomMediaFromGallery($gallery, $count, 'i');
    }
    /**
     * @name            listRandomMediaFromGallery()
     *                  Lists one ore more random media from gallery
     *
     * @since           1.0.7
     * @version         1.0.7
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
        $this->resetResponse();
        $allowedTypes = array('i', 'a', 'v', 'f', 'd', 'p', 's');
        if($mediaType != 'all' && !in_array($mediaType, $allowedTypes)){
            return $this->createException('InvalidParameterValueException', 'i, a, v, f, d, p, or s', 'err.invalid.parameter.mediaType');
        }
        if(!$gallery instanceof BundleEntity\Gallery && !is_numeric($gallery)){
            return $this->createException('InvalidParameterException', 'Gallery entity or numeric value representing row id', 'err.invalid.parameter.gallery');
        }
        if(is_numeric($gallery)){
            $response = $this->getGallery(($gallery));
            $gallery = $response['result']['set'];
        }
        $qStr = 'SELECT '.$this->entity['gallery_media']['alias']
            .' FROM '.$this->entity['gallery_media']['name'].' '.$this->entity['gallery_media']['alias']
            .' WHERE '.$this->entity['gallery_media']['alias'].'.gallery = '.$gallery->getId();
        unset($response, $gallery);
        $whereStr = '';
        if($mediaType != 'all'){
            $whereStr = ' AND '.$this->entity['gallery_media']['alias'].".type = '".$mediaType."'";
        }
        $qStr .= $whereStr;
        $query = $this->em->createQuery($qStr);

        $result = $query->getResult();

        $files = array();
        $counter = 0;
        $totalRows = count($result);
        $lastIndex = $totalRows - 1;

        if($totalRows > 0){
            foreach($result as $gm){
                $index = rand(0, $lastIndex);
                if($counter >= $count){
                    break;
                }
                $files[] = $result[$index]->getFile();
                $counter++;
            }
        }
        else{
            $this->response = array(
                'result' => array(
                    'set' => null,
                    'total_rows' => 0,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }

        $this->response = array(
            'result' => array(
                'set' => $files,
                'total_rows' => count($files),
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name 			listVideosOfGallery()
     *  				List all video files that belong to a certain gallery and that match to a certain criteria.
     *
     * @since			1.1.1
     * @version         1.1.1
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @param           mixed           $gallery
     * @param           array           $sortorder
     * @param           array           $limit
     *
     * @return          array           $response
     */
    public function listVideosOfGallery($gallery, $sortorder = null, $limit = null) {
        return $this->listMediaOfGallery($gallery, 'v', $sortorder, $limit);
    }
    /**
     *  @name 		    removeFilesFromProduct()
     *                  Removes the association of files with gallery.
     *
     * @since		    1.0.0
     * @version         1.0.7
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->doesGalleryExist()
     * @use             $this->isFileAssociatedWithGallery()
     * @use             $FMMService->doesFileExist()
     *
     * @throws          CoreExceptions\DuplicateAssociationException
     * @throws          CoreExceptions\EntityDoesNotExistException
     * @throws          CoreExceptions\InvalidParameterException
     *
     * @param           array           $files          Collection consists one of the following: 'entity' or entity 'id'
     *                                                  The entity can be a File entity or or a FilesOfProduct entity.
     * @param           mixed           $gallery        'entity' or 'entity' id.
     *
     * @return          array           $response
     */
    public function removeFilesFromGallery($files, $gallery) {
        $this->resetResponse();
        /**
         * Validate Parameters
         */
        $count = 0;
        /** remove invalid file entries */
        foreach ($files as $file) {
            if (!is_numeric($file) && !$file instanceof FileBundleEntity\File && !$file instanceof BundleEntity\GalleryMedia) {
                unset($files[$count]);
            }
            $count++;
        }
        /** issue an error only if there is no valid file or files of product entries */
        if (count($files) < 1) {
            return $this->createException('InvalidParameterException', '$files', 'err.invalid.parameter.files');
        }
        unset($count);
        if (!is_numeric($gallery) && !$gallery instanceof BundleEntity\Gallery) {
            return $this->createException('InvalidParameterException', '$gallery', 'err.invalid.parameter.product');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($gallery)) {
            $response = $this->getGallery($gallery, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Gallery', 'err.db.gallery.notexist');
            }
            $gallery = $response['result']['set'];
        }
        $fmmodel = new FMMService\FileManagementModel($this->kernel, $this->db_connection, $this->orm);

        $fop_count = 0;
        $to_remove = array();
        $count = 0;
        /** Start persisting files */
        foreach ($files as $file) {
            /** If no entity is provided as file we need to check if it does exist */
            if (is_numeric($file)) {
                $response = $fmmodel->getFile($file, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'File', 'err.db.file.notexist');
                }
                $to_remove[] = $file;
            }
            if ($file instanceof BundleEntity\GalleryMedia) {
                $this->em->remove($file);
                $fop_count++;
            }
            else {
                $to_remove[] = $file->getId();
            }
            $count++;
        }
        /** flush all into database */
        if ($fop_count > 0) {
            $this->em->flush();
        }
        if (count($to_remove) > 0) {
            $ids = implode(',', $to_remove);
            $table = $this->entity['gallery_media']['name'] . ' ' . $this->entity['gallery_media']['alias'];
            $q_str = 'DELETE FROM ' . $table
                . ' WHERE ' . $this->entity['gallery_media']['alias'] . '.gallery = ' . $gallery->getId()
                . ' AND ' . $this->entity['gallery_media']['alias'] . '.file IN(' . $ids . ')';

            $query = $this->em->createQuery($q_str);
            /**
             * 6. Run query
             */
            $query->getResult();
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $to_remove,
                'total_rows' => $count,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.delete.done',
        );
        unset($count, $to_remove);
        return $this->response;
    }
    /**
     * @name            updateGallery()
     *                  Updates single gallery from database
     *
     * @since           1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @use             $this->updateCountries()
     *
     * @param           mixed   $gallery     Entity or post data
     *
     * @return          array   $response
     *
     */
    public function updateGallery($gallery) {
        return $this->updateGalleries(array($gallery));
    }
    /**
     * @name            updateGalleries()
     *                  Updates one or more galleries of given post data or entity
     *
     * @since           1.0.0
     * @version         1.0.1
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @throw           InvalidParameterException
     *
     * @param           mixed   $collection     Entity or post data
     *
     * @return          array   $response
     *
     */
    public function updateGalleries($collection) {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterException', 'Array', 'err.invalid.parameter.collection');
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
                    return $this->createException('InvalidParameterException', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
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
                                    $response = $mlsModel->getLanguage($langCode, 'iso_code');
                                    $localization->setLanguage($response['result']['set']);
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
                            if(!$response['error']){
                                $oldEntity->$set($response['result']['set']);
                            }
                            else{
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        case 'site':
                            $sModel = $this->kernel->getContainer()->get('sitemanagement.model');
                            $response = $sModel->getSite($value, 'id');
                            if(!$response['error']){
                                $oldEntity->$set($response['result']['set']);
                            }
                            else{
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, $value);
                            }
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
            else{
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if($countUpdates > 0){
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.update.done',
        );
        return $this->response;
    }
    /**
     * @name            updateGalleryMedia()
     *                  Updates single gallery media from database
     *
     * @since           1.0.3
     * @version         1.0.3
     * @author          Can Berkol
     *
     * @use             $this->updateGalleryMediaEntries()
     *
     * @param           mixed   $collection     Entity or post data
     *
     * @return          array   $response
     *
     */

    public function updateGalleryMedia($collection) {
        return $this->updateGalleryMediaEntries(array($collection));
    }

    /**
     * @param           mixed $collection Entity or post data
     * @since           1.0.3
     * @version         1.0.3
     *
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @throw           InvalidParameterException
     *
     * @internal param string $by entity or post
     *
     * @return          array   $response
     */

    public function updateGalleryMediaEntries($collection) {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterException', 'Array', 'err.invalid.parameter.collection');
        }
        $countUpdates = 0;
        $updatedItems = array();
        foreach($collection as $data){
            if($data instanceof BundleEntity\GalleryMedia){
                $entity = $data;
                $this->em->persist($entity);
                $updatedItems[] = $entity;
                $countUpdates++;
            }
            else if(is_object($data)){
                if(property_exists($data, 'date_added')){
                    unset($data->date_added);
                }
                $response = $this->getGalleryMedia($data->file, $data->gallery);
                if($response['error']){
                    return $this->createException('EntityDoesNotExist', 'GalleryMedia with id '.$data->id, 'err.invalid.entity');
                }
                $oldEntity = $response['result']['set'];
                foreach($data as $column => $value){
                    $set = 'set'.$this->translateColumnName($column);
                    switch($column){
                        case 'file':
                            $fModel = $this->kernel->getContainer()->get('filemanagement.model');
                            $response = $fModel->getFile($value, 'id');
                            if(!$response['error']){
                                $oldEntity->$set($response['result']['set']);
                            }
                            else{
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        case 'gallery':
                            $response = $this->getGallery($value, 'id');
                            if(!$response['error']){
                                $oldEntity->$set($response['result']['set']);
                            }
                            else{
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
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
            else{
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if($countUpdates > 0){
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.update.done',
        );
        return $this->response;
    }
    /** ***************************************************************************************************
     *  PRIVATE METHODS
     *  ***************************************************************************************************/
    /**
     * @name 		    listGalleriesAdded()
     *                  List galleries that are added before, after, or in between of the given date(s).
     *
     * @since		    1.0.0
     * @version         1.0.7
     * @author          Said İmamoğlu
     *
     * @uses            $this->listGalleries()
     *
     * @param           mixed           $date                   One DateTime object or start and end DateTime objects.
     * @param           string          $eq                     after, before, between
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listGalleriesAdded($date, $eq, $sortorder = null, $limit = null) {
        $this->resetResponse();

        if (!$date instanceof \DateTime && !is_array($date)) {
            return $this->createException('InvalidParameterException', 'DateTime object or Array', 'err.invalid.parameter.date');
        }
        if (!in_array($eq, $this->eq_opts)) {
            return $this->createException('InvalidParameterValueException', implode(',', $this->eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['gallery']['alias'] . '.date_added';

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
        $response = $this->listGalleries($filter, $sortorder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }





    /**
     * @name 			listGalleriesUpdated()
     *  				List products that are updated before, after, or in between of the given date(s).
     *
     * @since			1.0.0
     * @version         1.0.4
     * @author          Can Berkol
     *
     * @uses            $this->listGalleries()
     *
     * @param           mixed           $date                   One DateTime object or start and end DateTime objects.
     * @param           string          $eq                     after, before, between
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listGalleriesUpdated($date, $eq, $sortorder = null, $limit = null) {
        $this->resetResponse();

        if (!$date instanceof \DateTime && !is_array($date)) {
            return $this->createException('InvalidParameterException', 'DateTime object or Array', 'err.invalid.parameter.date');
        }
        if (!in_array($eq, $this->eq_opts)) {
            return $this->createException('InvalidParameterValueException', implode(',', $this->eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['gallery']['alias'] . '.date_added';

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
        $response = $this->listGalleries($filter, $sortorder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }
    /**
     * @name 			listGalleriesPublished()
     *  				List products that are updated before, after, or in between of the given date(s).
     *
     * @since			1.0.0
     * @version         1.0.4
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed           $date                   One DateTime object or start and end DateTime objects.
     * @param           string          $eq                     after, before, between
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listGalleriesPublished($date, $eq, $sortorder = null, $limit = null) {
        $this->resetResponse();

        if (!$date instanceof \DateTime && !is_array($date)) {
            return $this->createException('InvalidParameterException', 'DateTime object or Array', 'err.invalid.parameter.date');
        }
        if (!in_array($eq, $this->eq_opts)) {
            return $this->createException('InvalidParameterValueException', implode(',', $this->eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['gallery']['alias'] . '.date_published';

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
        $response = $this->listGalleries($filter, $sortorder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }



    /**
     * @name 			listGalleriesUnpublished()
     *  				List products that are updated before, after, or in between of the given date(s).
     *
     * @since			1.0.0
     * @version         1.0.7
     * @author          Can Berkol
     *
     * @uses            $this->listProducts()
     *
     * @param           mixed           $date                   One DateTime object or start and end DateTime objects.
     * @param           string          $eq                     after, before, between
     * @param           array           $sortorder              Array
     *                                      'column'            => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     *
     * @return          array           $response
     */
    private function listGalleriesUnpublished($date, $eq, $sortorder = null, $limit = null) {
        $this->resetResponse();

        if (!$date instanceof \DateTime && !is_array($date)) {
            return $this->createException('InvalidParameterException', 'DateTime object or Array', 'err.invalid.parameter.date');
        }
        if (!in_array($eq, $this->eq_opts)) {
            return $this->createException('InvalidParameterValueException', implode(',', $this->eq_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['gallery']['alias'] . '.date_unpublished';

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
        $response = $this->listGalleries($filter, $sortorder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }
    /**
     * @name            listGalleriesWithTypeCount
     *                  Lists galleries with more/less/between of items in given type.
     *
     * @since            1.0.0
     * @version          1.0.7
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @uses            $this->listProducts()
     *
     * @param           string          $type
     * @param           integer         $count
     * @param           string          $eq
     * @param           array           $sortorder
     * @param           array           $limit
     *
     * @return          array           $response
     */
    private function listGalleriesWithTypeCount($type, $count, $eq, $sortorder = null, $limit = null) {
        $this->resetResponse();
        if (!in_array($eq, $this->eq_opts)) {
            return $this->createException('InvalidParameterValueException', implode(',', $this->eq_opts), 'err.invalid.parameter.eq');
        }
        if (!in_array($type, $this->type_opts)) {
            return $this->createException('InvalidParameterValueException', implode(',', $this->type_opts), 'err.invalid.parameter.eq');
        }
        /**
         * Prepare $filter
         */
        $column = $this->entity['gallery']['alias'] . '.count_' . $this->type_opts[$type];

        if ($eq == 'more' || $eq == 'less' || $eq == 'eq') {
            if (!is_int($count)) {
                return $this->createException('InvalidParameterException', 'Count', 'err.invalid.parameter.date');
            }
            switch ($eq) {
                case 'more':
                    $eq = '>';
                    break;
                case 'less':
                    $eq = '<';
                    break;
                case 'eq':
                    $eq = '=';
                    break;
            }
            $condition = array('column' => $column, 'comparison' => $eq, 'value' => $count);
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
            if (!is_array($count)) {
                return $this->createException('InvalidParameterException', 'Count', 'err.invalid.parameter.date');
            }
            $filter[] = array(
                'glue' => 'and',
                'condition' => array(
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '>', 'value' => $count[0]),
                    ),
                    array(
                        'glue' => 'and',
                        'condition' => array('column' => $column, 'comparison' => '<', 'value' => $count[1]),
                    )
                )
            );
        }
        $response = $this->listGalleries($filter, $sortorder, $limit);
        if (!$response['error']) {
            return $response;
        }
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $response['result']['set'],
                'total_rows' => $response['result']['total_rows'],
                'last_insert_id' => null,
            ),
            'error' => true,
            'code' => 'err.db.entry.notexist',
        );
        return $this->response;
    }


    /**
     * @name            insertGalleryCategory()
     *                  Inserts one gallery category into database.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->insertGalleryCategories()
     *
     * @param           mixed $data Entity or post
     *
     * @return          array           $response
     */
    public function insertGalleryCategory($data)
    {
        $this->resetResponse();
        return $this->insertGalleryCategories(array($data));
    }

    /**
     * @name            insertGalleryCategories()
     *                  Inserts one or more gallery categories into database.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->insertGalleryCategoryLocalization()
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertGalleryCategories($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $countLocalizations = 0;
        $insertedItems = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\GalleryCategory) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $localizations = array();
                $entity = new BundleEntity\GalleryCategory;
                if (!property_exists($data, 'date_added')) {
                    $data->date_added = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
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
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /** Now handle localizations */
        if ($countInserts > 0 && $countLocalizations > 0) {
            $this->insertGalleryCategoryLocalizations($localizations);
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name            insertGalleryCategoryLocalizations()
     *                  Inserts one or more gallery category localizations into database.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertGalleryCategoryLocalizations($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $insertedItems = array();
        foreach ($collection as $item) {
            if ($item instanceof BundleEntity\GalleryCategoryLocalization) {
                $entity = $item;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else {
                foreach ($item['localizations'] as $language => $data) {
                    $entity = new BundleEntity\GalleryCategoryLocalization;
                    $entity->setCategory($item['entity']);
                    $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                    $response = $mlsModel->getLanguage($language, 'iso_code');
                    if (!$response['error']) {
                        $entity->setLanguage($response['result']['set']);
                    } else {
                        break 1;
                    }
                    foreach ($data as $column => $value) {
                        $set = 'set' . $this->translateColumnName($column);
                        $entity->$set($value);
                    }
                    $this->em->persist($entity);
                }
                $insertedItems[] = $entity;
                $countInserts++;
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name            updateGalleryCategory()
     *                  Updates single gallery category. The data must be either a post data (array) or an entity
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->updateGalleryCategories()
     *
     * @param           mixed $data entity or post data
     *
     * @return          mixed           $response
     */
    public function updateGalleryCategory($data)
    {
        return $this->updateGalleryCategories(array($data));
    }

    /**
     * @name            updateGalleryCategories()
     *                  Updates one or more product details in database.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->doesGalleryCategoryExist()
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Product entities or array of entity details.
     *
     * @return          array           $response
     */
    public function updateGalleryCategories($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countUpdates = 0;
        $countLocalizations = 0;
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
                                    $response = $mlsModel->getLanguage($langCode, 'iso_code');
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
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countUpdates > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.update.done',
        );
        return $this->response;
    }

    /**
     * @name            deleteGalleryCategory ()
     *                  Deletes an existing gallery category from database.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->deleteGalleryCategories()
     *
     * @param           mixed           $data           a single value of 'entity', 'id', 'url_key'
     *
     * @return          mixed           $response
     */
    public function deleteGalleryCategory($data){
        return $this->deleteGalleryCategories(array($data));
    }
    /**
     * @name            deleteGalleryCategories()
     *                  Deletes provided gallery categories from database.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->validateAndGetGalleryCategory()
     *
     * @param           array           $collection             Collection consists one of the following: 'entity', 'id', 'sku', 'site', 'type', 'status'
     *
     * @return          array           $response
     */
    public function deleteGalleryCategories($collection){
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidCollection', 'The $collection parameter must be an array collection.', 'msg.error.invalid.collection.array');
        }
        $countDeleted = 0;
        foreach ($collection as $entry){
            $entry = $this->validateAndGetGalleryCategory($entry);
            $this->em->remove($entry);
            $countDeleted++;
        }
        if ($countDeleted < 0) {
            $this->response['error'] = true;
            $this->response['code'] = 'msg.error.db.delete.failed';

            return $this->response;
        }
        $this->em->flush();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => $countDeleted,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.delete',
        );
        return $this->response;
    }

    /**
     * @name            addCategoriesToProduct()
     *                  Associates gallery categories with a given gallery.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->resetResponse()
     * @use             $this->createException()
     * @use             $this->validateAndGetGallery()
     * @use             $this->getGalleryCategory()
     * @use             $this->isCategoryAssociatedWithGallery()
     *
     * @param           array           $set        Collection of attribute and sortorder set
     *                                              Contains an array with two keys: attribute, and sortorder
     * @param           mixed           $gallery    'entity' or 'entity' id or sku.
     *
     * @return          array           $response
     */
    public function addCategoriesToGallery($set, $gallery){
        $this->resetResponse();
        $count = 0;
        /** remove invalid gallery category entries */
        foreach ($set as $attr) {
            if (!is_numeric($attr['category']) && !$attr['category'] instanceof BundleEntity\GalleryCategory) {
                unset($attr[$count]);
            }
            $count++;
        }
        /** issue an error only if there is no valid file entries */
        if (count($set) < 1) {
            return $this->createException('InvalidCategorySet', '', 'msg.error.invalid.parameter.gallery.category.set', false);
        }
        unset($count);
        $gallery = $this->validateAndGetGallery($gallery);

        $count = 0;
        /** Start persisting files */
        foreach ($set as $item) {
            /** If no entity is provided as gallery we need to check if it does exist */
            if (!$item['category'] instanceof BundleEntity\GalleryCategory && is_numeric($item['category'])) {
                $response = $this->getGalleryCategory($item['category'], 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Table: product_category, id: ' . $gallery, 'msg.error.db.product.notfound');
                }
                $item['category'] = $response['result']['set'];
            } else {
                return $this->createException('InvalidParameter', '$set must contain an array of arrays where "category" key holds a value of an integer representing database row id or BiberLtd\\Core\\Bundles\\GalleryBundle\\Entity\\GalleryCategory entity', 'msg.error.invalid.parameter.product.category');
            }
            $aopCollection = array();
            /** Check if association exists */
            if (!$this->isCategoryAssociatedWithGallery($item['category'], $gallery, true)) {
                $aop = new BundleEntity\CategoriesOfGallery();
                $now = new \DateTime('now', new \DateTimezone($this->kernel->getContainer()->getParameter('app_timezone')));
                $aop->setCategory($item['category'])->setGallery($gallery)->setDateAdded($now);
                /** persist entry */
                $this->em->persist($aop);
                $aopCollection[] = $aop;
                $count++;
            }
        }
        /** flush all into database */
        if ($count > 0) {
            $this->em->flush();
        } else {
            $this->response['code'] = 'err.db.insert.failed';
        }

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $aopCollection,
                'total_rows' => $count,
                'last_insert_id' => -1,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        unset($count, $aopCollection);
        return $this->response;
    }

    /**
     * @name            listGalleryCategories()
     *                  List gallery categories from database based on a variety of conditions.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->resetResponse()
     * @use             $this->createException()
     *
     * @param           array $filter Multi-dimensional array
     * @param           array $sortOrder Array
     * @param           array $limit
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listGalleryCategories($filter = null, $sortOrder = null, $limit = null, $queryStr = null)
    {
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }
        /**
         * Add filter checks to below to set join_needed to true.
         */
        /**         * ************************************************** */
        $orderStr = '';
        $whereStr = '';
        $groupStr = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['gallery_category_localization']['alias'] . ', ' . $this->entity['gallery_category']['alias']
                . ' FROM ' . $this->entity['gallery_category_localization']['name'] . ' ' . $this->entity['gallery_category_localization']['alias']
                . ' JOIN ' . $this->entity['gallery_category_localization']['alias'] . '.category ' . $this->entity['gallery_category']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'date_added':
                        $column = $this->entity['gallery_category']['alias'] . '.' . $column;
                        break;
                    case 'name':
                    case 'url_key':
                        $column = $this->entity['gallery_category_localization']['alias'] . '.' . $column;
                        break;
                }
                $orderStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $orderStr = rtrim($orderStr, ', ');
            $orderStr = ' ORDER BY ' . $orderStr . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filterStr = $this->prepareWhere($filter);
            $whereStr .= ' WHERE ' . $filterStr;
        }

        $queryStr .= $whereStr . $groupStr . $orderStr;

        $query = $this->em->createQuery($queryStr);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }
        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();
        $categories = array();
        foreach($result as $gcl){
            $categories[] = $gcl->getCategory();
        }
        $totalRows = count($categories);
        if ($totalRows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $categories,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name            getGalleryCategory()
     *                  Returns details of a gallery category.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->resetResponse()
     * @use             $this->createException()
     * @use             $this->listGalleryCategories()
     *
     * @param           mixed           $category  id, url_key
     * @param           string          $by         id, sku
     *
     * @return          mixed           $response
     */
    public function getGalleryCategory($category, $by = 'id'){
        $this->resetResponse();
        $by_opts = array('id', 'url_key');
        if (!is_string($by)){
            return $this->createException('InvalidParameter', '$by parameter must hold a string value.', 'msg.error.invalid.parameter.by');
        }
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidByOption', 'Accepted values are: '.implode(',', $by_opts).'. You have provided "'.$by.'"', 'msg.error.invalid.option');
        }
        if (!$category instanceof BundleEntity\GalleryCategory && !is_numeric($category) && !is_string($category)) {
            return $this->createException('InvalidParameter', '$category parameter must hold BiberLtd\\Core\\Bundles\\GalleryBundle\\Entity\\GalleryCategory entity, a string representing url_key, or an integer representing database row id.', 'msg.error.invalid.parameter.product.attribute');
        }
        if (is_object($category)) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $category,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }
        switch ($by) {
            case 'url_key':
                $column = $this->entity['gallery_category_localization']['alias'].'.'.$by;
                break;
            default:
                $column = $this->entity['gallery_category']['alias'].'.'.$by;
                break;
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $column, 'comparison' => '=', 'value' => $category),
                )
            )
        );

        $response = $this->listGalleryCategories($filter, null, array('start' => 0, 'count' => 1));
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
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
     * @param           mixed           $category          id, url_key
     * @param           string          $by                 all, entity, id, url_key
     * @param           bool            $bypass             If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesGalleryCategoryExist($category, $by = 'id', $bypass = false){
        $this->resetResponse();
        $exist = false;

        $response = $this->getGalleryCategory($category, $by);

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = true;
        }
        if ($bypass) { return $exist; }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }

    /**
     * @name            isCategoryAssociatedWithGallery()
     *                  Checks if the attribute is already associated with the product category.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @user            $this->resetResponse()
     * @user            $this->createException()
     * @user            $this->getGalleryCategory()
     * @user            $this->getGallery()
     *
     * @param           mixed $category 'entity' or 'entity' id
     * @param           mixed $gallery 'entity' or 'entity' id.
     * @param           bool $bypass true or false
     *
     * @return          mixed               bool or $response
     */
    public function isCategoryAssociatedWithGallery($category, $gallery, $bypass = false)
    {
        $this->resetResponse();
        $gallery = $this->validateAndGetGallery($gallery);
        $category = $this->validateAndGetGalleryCategory($category);

        $found = false;

        $q_str = 'SELECT COUNT(' . $this->entity['categories_of_gallery']['alias'] . ')'
            . ' FROM ' . $this->entity['categories_of_gallery']['name'] . ' ' . $this->entity['categories_of_gallery']['alias']
            . ' WHERE ' . $this->entity['categories_of_gallery']['alias'] . '.category = ' . $category->getId()
            . ' AND ' . $this->entity['categories_of_gallery']['alias'] . '.gallery = ' . $gallery->getId();
        $query = $this->em->createQuery($q_str);

        $result = $query->getSingleScalarResult();

        /** flush all into database */
        if ($result > 0) {
            $found = true;
            $code = 'scc.db.entry.exist';
        } else {
            $code = 'scc.db.entry.noexist';
        }

        if ($bypass) {
            return $found;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $found == true ? $result : $found,
                'total_rows' => count($result),
                'last_insert_id' => null,
            ),
            'error' => $found == true ? false : true,
            'code' => $code,
        );
        return $this->response;
    }

    /**
     * @name            validateAndGetGallery()
     *                  Validates $gallery parameter and returns BiberLtd\Core\Bundles\GalleryBundle\Entity\Gallery if found in database.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->getGallery()
     *
     * @param           mixed           $gallery
     *
     * @return          object          BiberLtd\Core\Bundles\GalleryBundle\Entity\Gallery
     */
    private function validateAndGetGallery($gallery){
        if (!is_numeric($gallery) && !$gallery instanceof BundleEntity\Gallery) {
            return $this->createException('InvalidParameter', '$gallery parameter must hold BiberLtd\\Core\\Bundles\\GalleryBundle\\Entity\\Gallery Entity, string representing url_key or sku, or integer representing database row id', 'msg.error.invalid.parameter.product');
        }
        if ($gallery instanceof BundleEntity\Gallery) {
            return $gallery;
        }
        if (is_numeric($gallery)) {
            $response = $this->getGallery($gallery, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table: gallery, id: ' . $gallery, 'msg.error.db.gallery.notfound');
            }
            $gallery = $response['result']['set'];
        } else if (is_string($gallery)) {
            $response = $this->getGallery($gallery, 'url_key');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : gallery, id / url_key: ' . $gallery, 'msg.error.db.gallery.notfound');
            }
            $gallery = $response['result']['set'];
        }

        return $gallery;
    }

    /**
     * @name            validateAndGetGalleryCategory()
     *                  Validates $category parameter and returns BiberLtd\Core\Bundles\GalleryBundle\Entity\GalleryCategory if found in database.
     *
     * @since           1.0.9
     * @version         1.0.9
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->getGalleryCategory()
     *
     * @param           mixed           $category
     *
     * @return          object          BiberLtd\Core\Bundles\GalleryBundle\Entity\GalleryCategory
     */
    private function validateAndGetGalleryCategory($category){
        if (!is_numeric($category) && !$category instanceof BundleEntity\GalleryCategory) {
            return $this->createException('InvalidParameter', '$category parameter must hold BiberLtd\\Core\\Bundles\\GalleryBundle\\Entity\\GalleryCategory Entity or integer representing database row id', 'msg.error.invalid.parameter.product.attribute');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($category)) {
            $response = $this->getGalleryCategory($category, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : gallery_category, id: ' . $category,  'msg.error.db.gallery.category.notfound');
            }
            $category = $response['result']['set'];
        }
        else if (is_string($category)) {
            $response = $this->getGalleryCategory($category, 'url_key');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : gallery_category, url_key: ' . $category,  'msg.error.db.gallery.category.notfound');
            }
            $category = $response['result']['set'];
        }
        return $category;
    }

    /**
     * @name            listGalleriesOfCategory()
     *
     * @since           1.0.9
     * @version         1.1.0
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->listGalleryCategories()
     *
     * @param           mixed   $category
     * @param           array $filter Multi-dimensional array
     * @param           array $sortOrder Array
     * @param           array $limit
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array
     *
     */
    public function listGalleriesOfCategory($category, $filter = null, $sortOrder = null, $limit = null, $queryStr = null){
        $this->resetResponse();
        $category = $this->validateAndGetGalleryCategory($category);

        $qStr = '';
        $selectStr = 'SELECT ' . $this->entity['categories_of_gallery']['alias'];
        $fromStr = ' FROM ' . $this->entity['categories_of_gallery']['name'] . ' ' . $this->entity['categories_of_gallery']['alias'];
        $joinStr = '';
        $whereStr = ' WHERE ' . $this->entity['categories_of_gallery']['alias'] . '.category = ' . $category->getId();
        $orderStr = '';
        if (!is_null($sortOrder)) {
            foreach ($sortOrder as $column => $value) {
                switch ($column) {
                    case 'date_added':
                    case 'date_updated':
                        $joinStr = ' JOIN ' . $this->entity['categories_of_gallery']['alias'] . '.gallery ' . $this->entity['gallery']['alias'];
                        $column = $this->entity['gallery']['alias'] . '.' . $column;
                        break;
                }
                $orderStr .= $column . ' ' . strtoupper($value) . ', ';
            }
            $orderStr = ' ORDER BY ' . $orderStr;
        }
        $qStr = $selectStr . $fromStr . $joinStr . $whereStr . $orderStr;
        $qStr = rtrim(trim($qStr), ',');
        $query = $this->em->createQuery($qStr);
        if (!is_null($limit)) {
            $query->setFirstResult($limit['start']);
            $query->setMaxResults($limit['count']);
        }
        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows == 0) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $galleries = array();
        foreach ($result as $cog) {
            $galleries[] = $cog->getGallery();
        }
        $totalRows = count($galleries);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $galleries,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            listGalleriesOfCategory()
     *
     * @since           1.0.9
     * @version         1.1.0
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->listGalleryCategories()
     *
     * @param           mixed   $gallery
     * @param           array   $filter Multi-dimensional array
     * @param           array   $sortOrder Array
     * @param           array   $limit
     * @param           string  $queryStr If a custom query string needs to be defined.
     *
     * @return          array
     *
     */
    public function listCategoriesOfGallery($gallery, $filter = null, $sortOrder = null, $limit = null, $queryStr = null){
        $this->resetResponse();
        $gallery = $this->validateAndGetGallery($gallery);

        $qStr = '';
        $selectStr = 'SELECT ' . $this->entity['categories_of_gallery']['alias'];
        $fromStr = ' FROM ' . $this->entity['categories_of_gallery']['name'] . ' ' . $this->entity['categories_of_gallery']['alias'];
        $joinStr = '';
        $whereStr = ' WHERE ' . $this->entity['categories_of_gallery']['alias'] . '.gallery = ' . $gallery->getId();
        $orderStr = '';
        if (!is_null($sortOrder)) {
            foreach ($sortOrder as $column => $value) {
                switch ($column) {
                    case 'date_added':
                    case 'date_updated':
                        $joinStr = ' JOIN ' . $this->entity['categories_of_gallery']['alias'] . '.category ' . $this->entity['gallery_category']['alias'];
                        $column = $this->entity['gallery_category']['alias'] . '.' . $column;
                        break;
                }
                $orderStr .= $column . ' ' . strtoupper($value) . ', ';
            }
            $orderStr = ' ORDER BY ' . $orderStr;
        }
        $qStr = $selectStr . $fromStr . $joinStr . $whereStr . $orderStr;
        $qStr = rtrim(trim($qStr), ',');
        $query = $this->em->createQuery($qStr);
        if (!is_null($limit)) {
            $query->setFirstResult($limit['start']);
            $query->setMaxResults($limit['count']);
        }
        $result = $query->getResult();

        $totalRows = count($result);
        if ($totalRows == 0) {
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => null,
                    'total_rows' => $totalRows,
                    'last_insert_id' => null,
                ),
                'error' => true,
                'code' => 'err.db.entry.notexist',
            );
            return $this->response;
        }
        $cats = array();
        foreach ($result as $cog) {
            $cats[] = $cog->getCategory();
        }
        $totalRows = count($cats);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $cats,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'err.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name        listGalleryCategoriesInLocale()
     *              Lists gallery categories of given language
     *
     * @author      Said İmamoglu
     * @since       1.1.2
     * @version     1.1.2
     *
     * @param       mixed   $locale
     * @param       array   $filter
     * @param       array   $sortOrder
     * @param       array   $limit
     * @param       string  $queryStr
     *
     * @return      array
     *
     */
    public function listGalleryCategoriesInLocales($locale,$filter=array(),$sortOrder = array(),$limit = array() ,$queryStr = null ){
        $mlsModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
        $response = $mlsModel->getLanguage($locale,'iso_code');
        if ($response['error']) {
            return $this->createException('EntityDoesNotExist', 'Table: language, iso_code: ' . $locale, 'msg.error.db.language.notfound');
        }
        $language = $response['result']['set'];
        unset($response);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['gallery_category_localization']['alias'] . '.language', 'comparison' => '=', 'value' =>$language->getId() ),
                )
            )
        );
        return $this->listGalleryCategories($filter,$sortOrder,$limit,$queryStr);
    }
}
/**
 * Change Log
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
 * **************************************
 * v1.0.1                   Said İmamoğlu
 * 14.01.2014
 * **************************************
 * A countDistinctMediaTotal()
 *
 * **************************************
 * v1.0.1                   Said İmamoğlu
 * 27.11.2013
 * **************************************
 * A addFileToGallery()
 * A isFileAssociatedWithGallery()
 * A getMaxSortOrderOfGalleryMedia()
 * A doesGalleryMediaExist()
 * A getGalleryMedia()
 * A listGalleryMedias()
 * A listFilesWithType()
 * A listAllMediaOfGalleryByViewCount()
 * A listAllMediaOfGalleryByViewCountBetween()
 * A listAllMediaOfGalleryByViewCountLessThan()
 * A listAllMediaOfGalleryByViewCountMoreThan()
 * A removeFilesFromGallery()
 *
 * **************************************
 * v1.0.1 Said İmamoğlu
 * 27.11.2013
 * **************************************
 * A listGalleriesAdded()
 * A listGalleryAddedAfter()
 * A listGalleriesAddedBefore()
 * A listGalleriesAddedBetween()
 * A listGalleriesPublished()
 * A listGalleriesPublishedAfter()
 * A listGalleriesPublishedBefore()
 * A listGalleriesPublishedBetween()
 * A listGalleriesUnpublished()
 * A listGalleriesUnpublishedAfter()
 * A listGalleriesUnpublishedBefore()
 * A listGalleriesUnpublishedBetween()
 * A listGalleriesUpdated()
 * A listGalleriesUpdatedAfter()
 * A listGalleriesUpdatedBefore()
 * A listGalleriesUpdatedBetween()
 * A listGalleriesWithDocumentCount()
 * A listGalleriesWithDocumentCountBetween()
 * A listGalleriesWithDocumentCountLessThan()
 * A listGalleriesWithDocumenCounttMoreThan()
 * A listGalleriesWithImageCount()
 * A listGalleriesWithImageCountBetween()
 * A listGalleriesWithImageCountLessThan()
 * A listGalleriesWithImageCountMoreThan()
 * A listGalleriesWithMediaCount()
 * A listGalleriesWithMediaCountBetween()
 * A listGalleriesWithMediaCountLessThan()
 * A listGalleriesWithMediaCountMoreThan()
 * A listGalleriesWithVideoCount()
 * A listGalleriesWithVideoCountBetween()
 * A listGalleriesWithVideoCountLessThan()
 * A listGalleriesWithVideoCountMoreThan()
 * A updateGallery()
 * A updateGalleries()
 * A listGalleriesOfSite()
 * A listAllImagesOfGallery()
 * A listAllVideosOfGallery()
 * A listAllDocumentsOfGallery()
 *
 * **************************************
 * v1.0.1                      Can Berkol
 * 27.11.2013
 * **************************************
 * A deleteGalleries()
 * A deleteGallery()
 * A listAllGalleries()
 * A listImagesOfGallery()
 * A listImagesOfAllGaleries()
 * A listLastImagesOfAllGalleries()
 * A listMediaOfGallery()
 * A listGalleries()
 * A insertGallery()
 * A insertGalleries()
 *
 * **************************************
 * v1.0.0                      Can Berkol
 * 26.11.2013
 * **************************************
 * A __construct()
 * A __destruct()
 *
 */