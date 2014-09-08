<?php

/**
 * SlideshowController
 *
 * account/ controller.
 *
 * @package		AdministrationBundle
 * @subpackage	Controller
 * @name	    PageController
 *
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (www.biberltd.com)
 *
 * @version     1.0.0
 *
 */

namespace BiberLtd\Bundle\GalleryBundle\Controller;

use Assetic\Filter\JSMinFilter;
use BiberLtd\Bundle\FileManagementBundle\BiberLtdBundleFileManagementBundle;
use BiberLtd\Core\CoreController;
use Symfony\Component\HttpKernel\Exception,
    Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AjaxController extends CoreController {
    /**
     * @name            deleteFilesAction()
     *                  DOMAIN/{_locale}/manage/gallery/delete/file/{fileId}/{mode}
     *
     *                  Delete gallery files. If mode is set to keep files will remain bot in file system and database.
     *                  But they will be removed from gallery. If mode is set to remove the files will be removed from
     *                  gallery, filesystem and the database.
     *
     * @author          Can Berkol
     * @since           1.0.0
     * @version         1.0.0
     *
     * @param           integer         $fileId
     * @param           integer         $galleryId  -1
     * @param           string          $mdoe       remove|keep
     *
     * @return          \Symfony\Component\HttpFoundation\Response
     */
    public function deleteFilesAction($fileId, $galleryId = -1, $mode = 'remove') {
        /**
         * 1. Get global services and prepare URLs
         */
        $session = $this->get('session');
        $translator = $this->get('translator');
        $av = $this->get('access_validator');
        $sm = $this->get('session_manager');

        $this->setURLs();

        $locale = $session->get('_locale');

        /**
         * 2. Validate Access Rights
         *
         * This controller is managed and only available to non-loggedin users.
         */
        $access_map = array(
            'unmanaged' => false,
            'guest' => false,
            'authenticated' => true,
            'members' => array(),
            'groups' => array('admin', 'support'),
            'status' => array('a')
        );
        if (!$av->has_access(null, $access_map)) {
            $sm->logAction('page.visit.fail.insufficient.rights', 1, array('route' => '/manage/dashboard'));
            /** If already logged-in redirect back to Manage/Dashboad */
            return new RedirectResponse($this->url['base_l'] . '/manage/account/login');
        }

        /** Set up base paths & urls */
        $rootPath = $_SERVER['DOCUMENT_ROOT'];

        $gModel = $this->get('gallery.model');
        $fModel = $this->get('filemanagement.model');
        if($galleryId == -1){
            $response = $fModel->getFile($fileId, 'id');
            if($response['error']){
                return new JsonResponse('file-not-found');
            }
            $fileToDelete = $response['resultt']['set'];

            $filePath = $rootPath.$fileToDelete->getFolder()->getPathAbsolute().$fileToDelete->getSourceOriginal();
            $thumbPath = $rootPath.$fileToDelete->getFolder()->getPathAbsolute().'/thumbs/'.$fileToDelete->getSourceOriginal();

            if(file_exists($filePath)){
                unlink($filePath);
            }
            if(file_exists($thumbPath)){
                unlink($thumbPath);
            }
            $response = $fModel->deleteFile($fileToDelete);
        }
        else{
            $response = $gModel->getGalleryMedia($fileId, $galleryId);
            if($response['error']){
                return new JsonResponse('gallery-media-not-found');
            }
            $gmToDelete = $response['result']['set'];
            $fileToDelete = $gmToDelete->getFile();

            switch($mode){
                case 'keep':
                    /** Only remove file from gallery but keep in bot file system and database */
                    $response = $gModel->removeFilesFromGallery(array($fileToDelete), $galleryId);
                    break;
                case 'remove':
                    /** Delete file from file system and database and remove association */
                    $gModel->removeFilesFromGallery(array($fileToDelete), $galleryId);
                    $filePath = $rootPath.$fileToDelete->getFolder()->getPathAbsolute().$fileToDelete->getSourceOriginal();
                    $thumbPath = $rootPath.$fileToDelete->getFolder()->getPathAbsolute().'/thumbs/'.$fileToDelete->getSourceOriginal();

                    if(file_exists($filePath)){
                        unlink($filePath);
                    }
                    if(file_exists($thumbPath)){
                        unlink($thumbPath);
                    }
                    $response = $fModel->deleteFile($fileToDelete);
                    break;
            }
        }

        if($response['error']){
            return new JsonResponse('error');
        }
        return new JsonResponse('success');
    }
    /**
     * @name            listGalleryImagesAction()
     *                  DOMAIN/{_locale}/manage/gallery/get
     *
     *                  List images of a given gallery and return a json object for use with multi-upload widget.
     *
     * @author          Can Berkol
     * @since           1.0.0
     * @version         1.0.0
     *
     * @param           integer         $galleryId
     * @param           string          $mode           remove|keep
     * @return          \Symfony\Component\HttpFoundation\Response
     */
    public function listGalleryImagesAction($galleryId, $mode = 'remove') {
        /**
         * 1. Get global services and prepare URLs
         */
        $session = $this->get('session');
        $translator = $this->get('translator');
        $av = $this->get('access_validator');
        $sm = $this->get('session_manager');

        $this->setURLs();

        $locale = $session->get('_locale');

        /**
         * 2. Validate Access Rights
         *
         * This controller is managed and only available to non-loggedin users.
         */
        $access_map = array(
            'unmanaged' => false,
            'guest' => false,
            'authenticated' => true,
            'members' => array(),
            'groups' => array('admin', 'support'),
            'status' => array('a')
        );
        if (!$av->has_access(null, $access_map)) {
            $sm->logAction('page.visit.fail.insufficient.rights', 1, array('route' => '/manage/dashboard'));
            /** If already logged-in redirect back to Manage/Dashboard */
            return new RedirectResponse($this->url['base_l'] . '/manage/account/login');
        }

        $currentGalleryMedia = array();
        $gModel = $this->get('gallery.model');
        $response = $gModel->getGallery($galleryId);
        /**  Gallery not found */
        if($response['error']){
            return new JsonResponse($currentGalleryMedia);
        }

        /** Get all images that belong to this gallery */
        $response = $gModel->listImagesOfGallery($galleryId);
        /** No images found */
        if($response['error']){
            return new JsonResponse($currentGalleryMedia);
        }
        $currentGalleryMedia = $response['result']['set'];

        /** Set up base paths & urls */
        $rootUrl = $this->url['domain'];
        $rootPath = $_SERVER['DOCUMENT_ROOT'];

        $result = array();
        $files = array();
        foreach($currentGalleryMedia as $media){
            $image      = $media->getFile();
            $imgPath    = $rootPath.$image->getFolder()->getPathAbsolute().$image->getSourceOriginal();
            $imgURL     = $rootUrl.$image->getFolder()->getPathAbsolute().$image->getSourceOriginal();
            // $tmbPath    = $rootPath.$image->getFolder().'/thumbs/'.$image->getSourceOriginal();
            $tmbUrl     = $rootUrl.$image->getFolder()->getPathAbsolute().'/thumbs/'.$image->getSourceOriginal();
            if(file_exists($imgPath)){
                if($image->getSize() != null){
                    $imgSize = $image->getSize();
                }
                else{
                    $imgSize = filesize($imgPath);
                }
                $file = array(
                    'name'          => $image->getName(),
                    'size'          => $imgSize,
                    'url'           => $imgURL,
                    'thumbnail_url' => $tmbUrl,
                    'delete_url'    => $this->url['base_l'].'/manage/gallery/delete/file/'.$image->getId().'/'.$galleryId.'/'.$mode,
                    'delete_type'   => 'DELETE',
                    'file_id'       => $image->getId(),
                    'sort'          => $media->getSortOrder(),
                    'gallery_id'    => $galleryId,
                );
                $files[] = $file;
            }
        }
        $result['files'] = $files;

        return new JsonResponse($result);
    }
    /**
     * @name            sortFilesAction()
     *                  DOMAIN/{_locale}/manage/gallery/sort/{galleryId}
     *
     *                  Dletes slideshow images
     *
     * @author          Can Berkol
     * @since           1.0.0
     * @version         1.0.0
     *
     * @param           integer         $galleryId
     *
     * @return          \Symfony\Component\HttpFoundation\Response
     */
    public function sortFilesAction($galleryId) {
        /**
         * 1. Get global services and prepare URLs
         */
        $request = $this->get('request');
        $session = $this->get('session');
        $translator = $this->get('translator');
        $av = $this->get('access_validator');
        $sm = $this->get('session_manager');

        $this->setURLs();

        /**
         * 2. Validate Access Rights
         *
         * This controller is managed and only available to non-loggedin users.
         */
        $access_map = array(
            'unmanaged' => false,
            'guest' => false,
            'authenticated' => true,
            'members' => array(),
            'groups' => array('founder', 'support'),
            'status' => array('a')
        );
        if (!$av->has_access(null, $access_map)) {
            $sm->logAction('page.visit.fail.insufficient.rights', 1, array('route' => '/manage/dashboard'));
            /** If already logged-in redirect back to Manage/Dashboad */
            return new RedirectResponse($this->url['base_l'] . '/manage/account/login');
        }
        /**
         * @todo make str_replace search value automatically set
         */
        $fileId = (int) str_replace('sortorder-', '', $request->get('id'));
        $newOrder = (int) $request->get('order');

        $gModel = $this->get('gallery.model');

        $response = $gModel->getGalleryMedia($fileId, $galleryId);

        if($response['error']){
            return new JsonResponse('error');
        }
        $gmEntry = $response['result']['set'];

        $gmEntry->setSortOrder($newOrder);

        $gModel->updateGalleryMedia($gmEntry);

        return new JsonResponse('success');
    }
    /**
     * @name            uploadImagesAction()
     *                  DOMAIN/{_locale}/manage/gallery/upload
     *
     *                  Upload files and adds them to aa given gallery
     *
     * @author          Can Berkol
     * @since           1.0.0
     * @version         1.0.0
     *
     * @param           integer     $galleryId
     * @param           string      $mode           remove|keep
     *
     * @return          \Symfony\Component\HttpFoundation\Response
     */
    public function uploadImagesAction($galleryId, $mode = 'remove') {
        /**
         * 1. Get global services and prepare URLs
         */
        $session = $this->get('session');
        $translator = $this->get('translator');
        $av = $this->get('access_validator');
        $sm = $this->get('session_manager');

        $this->setURLs();

        $locale = $session->get('_locale');

        /**
         * 2. Validate Access Rights
         *
         * This controller is managed and only available to non-loggedin users.
         */
        $access_map = array(
            'unmanaged' => false,
            'guest' => false,
            'authenticated' => true,
            'members' => array(),
            'groups' => array('founder', 'support'),
            'status' => array('a')
        );
        if (!$av->has_access(null, $access_map)) {
            $sm->logAction('page.visit.fail.insufficient.rights', 1, array('route' => '/manage/dashboard'));
            /** If already logged-in redirect back to Manage/Dashboad */
            return new RedirectResponse($this->url['base_l'] . '/manage/account/login');
        }
        /** get models */
        $fModel = $this->get('filemanagement.model');
        $gModel = $this->get('gallery.model');

        $response = $gModel->getGallery($galleryId, 'id');
        if($response['error']){
            return new JsonResponse('gallery-not-found');
        }
        $gallery = $response['result']['set'];
        unset($response);

        $rootUrl = $this->url['base'];
        $rootPath = $_SERVER['DOCUMENT_ROOT'];
        $targetFolderPath = $rootPath.$gallery->getFolder()->getPathAbsolute();
        $targetFolderUrl = $rootUrl.$gallery->getFolder()->getPathAbsolute();
        /** DO UPLOAD */
        /** BEGIN upload file */
        $request = $this->get('request');
        $fileData = false;
        $file = $request->files->get('files');
        if($file instanceof UploadedFile) {
            $clientName = $file->getClientOriginalName();
            $nameArray = explode('.', $clientName);
            $fileType = array_pop($nameArray);
            $fileName = '';
            $fileSize = $file->getClientSize();
            $fileMime = $file->getClientMimeType();
            foreach($nameArray as $namePart){
                $fileName = $this->generateUrlKey($namePart).'.';
            }
            $fileNameExt = $fileName.$fileType;
            $targetPath = $targetFolderPath.$fileNameExt;
            if(file_exists($targetPath)){
                $fileName = rand(0, 1000).'-'.$fileName;
            }
            $fileNameExt = $fileName.$fileType;
            $targetPath = $targetFolderPath.$fileNameExt;
            $uploadedFile = $file->move($targetFolderPath, $fileNameExt);
            if($fileSize != filesize($targetPath)){
                $fileSize = filesize($targetPath);
            }

            $fileData = new \stdClass();
            $fileData->name = rtrim($fileName,'.');
            $fileData->source_original = $fileData->url_key = $fileNameExt;
            $fileData->extension = $fileType;
            $fileData->type = 'i';
            /**
             * @todo site selection should be automatic.
             */
            $fileData->site = 1;
            $fileData->folder = $gallery->getFolder()->getId();
            $fileData->mime_type = $fileMime;
            $fileData->size = $fileSize;
        }
        /** insert file into db */
        $insertedFile = false;
        if($fileData != false){
            $response = $fModel->insertFile($fileData);
            if($response['error']){
                if(file_exists($targetPath)){
                    unlink($targetPath);
                }
                return new JsonResponse('error-db-insert');
            }
            $insertedFile = $response['result']['set'][0];
        }

        if(!$insertedFile){
            return new JsonResponse('error-db-insert');
        }
        /** create thumbnail */

        $iwModel = $this->get('imageworkshop.model');
        $imageLayer = $iwModel->initFromPath($targetPath);
        $imageLayer->resizeByLargestSideInPixel(80, true);
        $imageLayer->save($targetFolderPath.'thumbs/', $fileNameExt, true, null, 80);

        /** add files to gallery */
        $gmEntry = array('sortorder' => $insertedFile->getId(), 'file' => $insertedFile);
        $gModel->addFileToGallery($gmEntry, $gallery);

        /** prepare response */
        $fileUrl  = $targetFolderUrl.$fileNameExt;
        $thumbUrl = $targetFolderUrl.'thumbs/'.$fileNameExt;

        $returned['files'][0]['name']           = rtrim($fileName,'.');
        $returned['files'][0]['size']           = $fileSize;
        $returned['files'][0]['url']            = $fileUrl;
        $returned['files'][0]['thumbnail_url']  = $thumbUrl;
        $returned['files'][0]['delete_url']     = $this->url['base_l'] . '/manage/gallery/delete/'.$insertedFile->getId().'/'.$mode;
        $returned['files'][0]['delete_type']    = 'post';
        $returned['files'][0]['file_id']        = $insertedFile->getId();
        $returned['files'][0]['sortorder']      = $insertedFile->getId();
        $returned['files'][0]['gallery_id']     = $galleryId;

        return new Response(json_encode($returned));
    }
}

/**
 * Change Log:
 * **************************************
 * v1.0.0                      Can Berkol
 * 20.02.2014
 * **************************************
 * A deleteFilesAction()
 * A listGalleryImagesAction()
 * A sortGalleryFilesAction()
 * A uploadImagesAction()
 */
