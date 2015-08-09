<?php
/**
 * @name        Gallery
 * @package		BiberLtd\Bundle\CoreBundle\GalleryBundle
 *
 * @author      Can Berkol
 * @author		Murat Ünal
 *
 * @version     1.0.4
 * @date        09.08.2015
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\GalleryBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreLocalizableEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="gallery",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idx_n_gallery_date_published", columns={"date_published"}),
 *         @ORM\Index(name="idx_n_gallery_date_added", columns={"date_added"}),
 *         @ORM\Index(name="idx_n_gallery_date_updated", columns={"date_updated"}),
 *         @ORM\Index(name="idx_n_gallery_date_unpublished", columns={"date_unpublished"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_gallery_id", columns={"id"})}
 * )
 */
class Gallery extends CoreLocalizableEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_added;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_updated;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $date_published;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $date_unpublished;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     */
    private $count_media;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     */
    private $count_image;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     */
    private $count_video;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     */
    private $count_audio;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     */
    private $count_document;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default":1})
     */
    private $sort_order;

    /**
     * @ORM\OneToMany(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\GalleryLocalization", mappedBy="gallery")
     */
    protected $localizations;


    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\SiteManagementBundle\Entity\Site")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", onDelete="CASCADE")
     */
    private $site;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File")
     * @ORM\JoinColumn(name="preview_file", referencedColumnName="id")
     */
    private $preview_file;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\FileUploadFolder")
     * @ORM\JoinColumn(name="folder", referencedColumnName="id")
     */
    private $folder;

    /******************************************************************
     * PUBLIC SET AND GET FUNCTIONS                                   *
     ******************************************************************/

    /**
     * @name            getId()
     *                  Gets $id property.
     * .
     * @author          Murat Ünal
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          string          $this->id
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @name                  setCountAudio ()
     *                                      Sets the count_audio property.
     *                                      Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $count_audio
     *
     * @return          object                $this
     */
    public function setCountAudio($count_audio) {
        if(!$this->setModified('count_audio', $count_audio)->isModified()) {
            return $this;
        }
		$this->count_audio = $count_audio;
		return $this;
    }

    /**
     * @name            getCountAudio ()
     *                                Returns the value of count_audio property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->count_audio
     */
    public function getCountAudio() {
        return $this->count_audio;
    }

    /**
     * @name                  setCountDocument ()
     *                                         Sets the count_document property.
     *                                         Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $count_document
     *
     * @return          object                $this
     */
    public function setCountDocument($count_document) {
        if(!$this->setModified('count_document', $count_document)->isModified()) {
            return $this;
        }
		$this->count_document = $count_document;
		return $this;
    }

    /**
     * @name            getCountDocument ()
     *                                   Returns the value of count_document property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->count_document
     */
    public function getCountDocument() {
        return $this->count_document;
    }

    /**
     * @name                  setCount İmage()
     *                                 Sets the count_image property.
     *                                 Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $count_image
     *
     * @return          object                $this
     */
    public function setCountImage($count_image) {
        if(!$this->setModified('count_image', $count_image)->isModified()) {
            return $this;
        }
		$this->count_image = $count_image;
		return $this;
    }

    /**
     * @name            getCount İmage()
     *                           Returns the value of count_image property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->count_image
     */
    public function getCountImage() {
        return $this->count_image;
    }

    /**
     * @name                  setCountMedia ()
     *                                      Sets the count_media property.
     *                                      Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $count_media
     *
     * @return          object                $this
     */
    public function setCountMedia($count_media) {
        if(!$this->setModified('count_media', $count_media)->isModified()) {
            return $this;
        }
		$this->count_media = $count_media;
		return $this;
    }

    /**
     * @name            getCountMedia ()
     *                                Returns the value of count_media property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->count_media
     */
    public function getCountMedia() {
        return $this->count_media;
    }

    /**
     * @name                  setCountVideo ()
     *                                      Sets the count_video property.
     *                                      Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $count_video
     *
     * @return          object                $this
     */
    public function setCountVideo($count_video) {
        if(!$this->setModified('count_video', $count_video)->isModified()) {
            return $this;
        }
		$this->count_video = $count_video;
		return $this;
    }

    /**
     * @name            getCountVideo ()
     *                                Returns the value of count_video property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->count_video
     */
    public function getCountVideo() {
        return $this->count_video;
    }

    /**
     * @name                  setDatePublished ()
     *                                         Sets the date_published property.
     *                                         Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $date_published
     *
     * @return          object                $this
     */
    public function setDatePublished($date_published) {
        if(!$this->setModified('date_published', $date_published)->isModified()) {
            return $this;
        }
		$this->date_published = $date_published;
		return $this;
    }

    /**
     * @name            getDatePublished ()
     *                                   Returns the value of date_published property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->date_published
     */
    public function getDatePublished() {
        return $this->date_published;
    }

    /**
     * @name                  setDateUnpublished ()
     *                                           Sets the date_unpublished property.
     *                                           Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $date_unpublished
     *
     * @return          object                $this
     */
    public function setDateUnpublished($date_unpublished) {
        if(!$this->setModified('date_unpublished', $date_unpublished)->isModified()) {
            return $this;
        }
		$this->date_unpublished = $date_unpublished;
		return $this;
    }

    /**
     * @name            getDateUnpublished ()
     *                                     Returns the value of date_unpublished property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->date_unpublished
     */
    public function getDateUnpublished() {
        return $this->date_unpublished;
    }

    /**
     * @name           setPreviewFile ()
     *                 Sets the preview_file property.
     *                 Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $preview_file
     *
     * @return          object                $this
     */
    public function setPreviewFile($preview_file) {
        if(!$this->setModified('preview_file', $preview_file)->isModified()) {
            return $this;
        }
		$this->preview_file = $preview_file;
		return $this;
    }

    /**
     * @name            getPreviewFile ()
     *                                 Returns the value of preview_file property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->preview_file
     */
    public function getPreviewFile() {
        return $this->preview_file;
    }

    /**
     * @name                  setSite ()
     *                                Sets the site property.
     *                                Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $site
     *
     * @return          object                $this
     */
    public function setSite($site) {
        if(!$this->setModified('site', $site)->isModified()) {
            return $this;
        }
		$this->site = $site;
		return $this;
    }

    /**
     * @name            getSite ()
     *                          Returns the value of site property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->site
     */
    public function getSite() {
        return $this->site;
    }

    /**
     * @name            setFolder ()
     *                  Sets the folder property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $folder
     *
     * @return          object                $this
     */
    public function setFolder($folder) {
        if($this->setModified('folder', $folder)->isModified()) {
            $this->folder = $folder;
        }

        return $this;
    }

    /**
     * @name            getFolder ()
     *                  Returns the value of folder property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->folder
     */
    public function getFolder() {
        return $this->folder;
    }

	/**
	 * @name        getSortOrder ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.4
	 * @version     1.0.4
	 *
	 * @return      mixed
	 */
	public function getSortOrder() {
		return $this->sort_order;
	}

	/**
	 * @name       setSortOrder ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.4
	 * @version     1.0.4
	 *
	 * @param       mixed $sort_order
	 *
	 * @return      $this
	 */
	public function setSortOrder($sort_order) {
		if (!$this->setModified('sort_order', $sort_order)->isModified()) {
			return $this;
		}
		$this->sort_order = $sort_order;

		return $this;
	}
}
/**
 * Change Log:
 * **************************************
 * v1.0.4                      09.08.2015
 * Can Berkol
 * **************************************
 * FR :: added sort_order property.
 *
 * **************************************
 * v1.0.3                      Can Berkol
 * 28.11.2013
 * **************************************
 * A preview_file
 * A getPreviewFile()
 * A setPreviewFile()
 *
 * **************************************
 * v1.0.2                      Murat Ünal
 * 10.10.2013
 * **************************************
 * A getCountAudio()
 * A getCountDocument()
 * A getCountImage()
 * A getCountMedia()
 * A getCountVideo()
 * A getDateAdded()
 * A getDatePublished()
 * A getDateUnpublished()
 * A getDateUpdated()
 * A getGalleryMedia()
 * A getId()
 * A getLocalizations()
 * A getSite()
 *
 * A setCountAudio()
 * A setCountDocument()
 * A setCountImage()
 * A setCountMedia()
 * A setCountVideo()
 * A setDateAdded()
 * A setDatePublished()
 * A setDateUnpublished()
 * A setDateUpdated()
 * A setGalleryMedia()
 * A setLocalizations()
 * A setSite()
 *
 */