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
use BiberLtd\Bundle\CoreBundle\CoreEntity;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="gallery_media",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idxNGalleryMediaDateAdded", columns={"date_added"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUGalleryMedia", columns={"gallery","file"})}
 * )
 */
class GalleryMedia extends CoreEntity
{
    /** 
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"i"})
     * @var string
     */
    private $type;

    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":1})
     * @var int
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /** 
     * @ORM\Column(type="integer", nullable=false, options={"default":0})
     * @var int
     */
    private $count_view;

    /**
     * @ORM\Column(type="string", nullable=false, options={"default":"p"})
     * @var string
     */
    private $status;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File")
     * @ORM\JoinColumn(name="file", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\FileManagementBundle\Entity\File
     */
    private $file;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\Gallery")
     * @ORM\JoinColumn(name="gallery", referencedColumnName="id", nullable=false)
     * @var \BiberLtd\Bundle\GalleryBundle\Entity\Gallery
     */
    private $gallery;

	/**
	 * @param int $count_view
	 *
	 * @return $this
	 */
    public function setCountView(int $count_view) {
        if(!$this->setModified('count_view', $count_view)->isModified()) {
            return $this;
        }
		$this->count_view = $count_view;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getCountView() {
        return $this->count_view;
    }

	/**
	 * @param \BiberLtd\Bundle\FileManagementBundle\Entity\File $file
	 *
	 * @return $this
	 */
    public function setFile(\BiberLtd\Bundle\FileManagementBundle\Entity\File $file) {
        if(!$this->setModified('file', $file)->isModified()) {
            return $this;
        }
		$this->file = $file;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\FileManagementBundle\Entity\File
	 */
    public function getFile() {
        return $this->file;
    }

	/**
	 * @param \BiberLtd\Bundle\GalleryBundle\Entity\Gallery $gallery
	 *
	 * @return $this
	 */
    public function setGallery(\BiberLtd\Bundle\GalleryBundle\Entity\Gallery $gallery) {
        if(!$this->setModified('gallery', $gallery)->isModified()) {
            return $this;
        }
		$this->gallery = $gallery;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\GalleryBundle\Entity\Gallery
	 */
    public function getGallery() {
        return $this->gallery;
    }

	/**
	 * @param int $sort_order
	 *
	 * @return $this
	 */
    public function setSortOrder(int $sort_order) {
        if(!$this->setModified('sort_order', $sort_order)->isModified()) {
            return $this;
        }
		$this->sort_order = $sort_order;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getSortOrder() {
        return $this->sort_order;
    }

	/**
	 * @param string $type
	 *
	 * @return $this
	 */
    public function setType(string $type) {
        if(!$this->setModified('type', $type)->isModified()) {
            return $this;
        }
		$this->type = $type;
		return $this;
    }

	/**
	 * @return string
	 */
    public function getType() {
        return $this->type;
    }

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param string $status
	 *
	 * @return $this
	 */
	public function setStatus(string $status) {
		if (!$this->setModified('status', $status)->isModified()) {
			return $this;
		}
		$this->status = $status;

		return $this;
	}
}