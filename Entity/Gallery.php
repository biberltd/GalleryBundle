<?php
/**
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        23.12.2015
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
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_updated;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    private $date_published;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    private $date_unpublished;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_media;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_image;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_video;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_audio;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_document;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default":1})
     * @var int
     */
    private $sort_order;

    /**
     * @ORM\OneToMany(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\GalleryLocalization", mappedBy="gallery")
     * @var array
     */
    protected $localizations;


    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\SiteManagementBundle\Entity\Site")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\SiteManagementBundle\Entity\Site
     */
    private $site;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File")
     * @ORM\JoinColumn(name="preview_file", referencedColumnName="id")
     * @var \BiberLtd\Bundle\FileManagementBundle\Entity\File
     */
    private $preview_file;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\FileUploadFolder")
     * @ORM\JoinColumn(name="folder", referencedColumnName="id")
     * @var \BiberLtd\Bundle\FileManagementBundle\Entity\FileUploadFolder
     */
    private $folder;

	/**
	 * @return mixed
	 */
    public function getId(){
        return $this->id;
    }

	/**
	 * @param int $count_audio
	 *
	 * @return $this
	 */
    public function setCountAudio(\integer $count_audio) {
        if(!$this->setModified('count_audio', $count_audio)->isModified()) {
            return $this;
        }
		$this->count_audio = $count_audio;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getCountAudio() {
        return $this->count_audio;
    }

	/**
	 * @param int $count_document
	 *
	 * @return $this
	 */
    public function setCountDocument(\integer $count_document) {
        if(!$this->setModified('count_document', $count_document)->isModified()) {
            return $this;
        }
		$this->count_document = $count_document;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getCountDocument() {
        return $this->count_document;
    }

	/**
	 * @param int $count_image
	 *
	 * @return $this
	 */
    public function setCountImage(\integer $count_image) {
        if(!$this->setModified('count_image', $count_image)->isModified()) {
            return $this;
        }
		$this->count_image = $count_image;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getCountImage() {
        return $this->count_image;
    }

	/**
	 * @param int $count_media
	 *
	 * @return $this
	 */
    public function setCountMedia(\integer $count_media) {
        if(!$this->setModified('count_media', $count_media)->isModified()) {
            return $this;
        }
		$this->count_media = $count_media;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getCountMedia() {
        return $this->count_media;
    }

	/**
	 * @param int $count_video
	 *
	 * @return $this
	 */
    public function setCountVideo(\integer $count_video) {
        if(!$this->setModified('count_video', $count_video)->isModified()) {
            return $this;
        }
		$this->count_video = $count_video;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getCountVideo() {
        return $this->count_video;
    }

	/**
	 * @param \DateTime $date_published
	 *
	 * @return $this
	 */
    public function setDatePublished(\DateTime $date_published) {
        if(!$this->setModified('date_published', $date_published)->isModified()) {
            return $this;
        }
		$this->date_published = $date_published;
		return $this;
    }

	/**
	 * @return \DateTime
	 */
    public function getDatePublished() {
        return $this->date_published;
    }

	/**
	 * @param \DateTime $date_unpublished
	 *
	 * @return $this
	 */
    public function setDateUnpublished(\DateTime $date_unpublished) {
        if(!$this->setModified('date_unpublished', $date_unpublished)->isModified()) {
            return $this;
        }
		$this->date_unpublished = $date_unpublished;
		return $this;
    }

	/**
	 * @return \DateTime
	 */
    public function getDateUnpublished() {
        return $this->date_unpublished;
    }

	/**
	 * @param \BiberLtd\Bundle\FileManagementBundle\Entity\File $preview_file
	 *
	 * @return $this
	 */
    public function setPreviewFile(\BiberLtd\Bundle\FileManagementBundle\Entity\File $preview_file) {
        if(!$this->setModified('preview_file', $preview_file)->isModified()) {
            return $this;
        }
		$this->preview_file = $preview_file;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\FileManagementBundle\Entity\File
	 */
    public function getPreviewFile() {
        return $this->preview_file;
    }

	/**
	 * @param \BiberLtd\Bundle\SiteManagementBundle\Entity\Site $site
	 *
	 * @return $this
	 */
    public function setSite(\BiberLtd\Bundle\SiteManagementBundle\Entity\Site $site) {
        if(!$this->setModified('site', $site)->isModified()) {
            return $this;
        }
		$this->site = $site;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\SiteManagementBundle\Entity\Site
	 */
    public function getSite() {
        return $this->site;
    }

	/**
	 * @param \BiberLtd\Bundle\FileManagementBundle\Entity\FileUploadFolder $folder
	 *
	 * @return $this
	 */
    public function setFolder(\BiberLtd\Bundle\FileManagementBundle\Entity\FileUploadFolder $folder) {
        if($this->setModified('folder', $folder)->isModified()) {
            $this->folder = $folder;
        }

        return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\FileManagementBundle\Entity\FileUploadFolder
	 */
    public function getFolder() {
        return $this->folder;
    }

	/**
	 * @return int
	 */
	public function getSortOrder() {
		return $this->sort_order;
	}

	/**
	 * @param int $sort_order
	 *
	 * @return $this
	 */
	public function setSortOrder(\integer $sort_order) {
		if (!$this->setModified('sort_order', $sort_order)->isModified()) {
			return $this;
		}
		$this->sort_order = $sort_order;

		return $this;
	}
}