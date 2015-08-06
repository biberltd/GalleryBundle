<?php
/**
 * @name        GalleryMedia
 * @package		BiberLtd\Bundle\CoreBundle\GalleryBundle
 *
 * @author		Can Berkol
 * @author		Murat Ünal
 *
 * @version     1.0.2
 * @date        06.08.2015
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
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
     */
    private $type;

    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":1})
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_added;

    /** 
     * @ORM\Column(type="integer", nullable=false, options={"default":0})
     */
    private $count_view;

    /**
     * @ORM\Column(type="string", nullable=false, options={"default":"p"})
     */
    private $status;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File")
     * @ORM\JoinColumn(name="file", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $file;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\Gallery")
     * @ORM\JoinColumn(name="gallery", referencedColumnName="id", nullable=false)
     */
    private $gallery;

    /**
     * @name            setCountView ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $count_view
     *
     * @return          object                $this
     */
    public function setCountView($count_view) {
        if(!$this->setModified('count_view', $count_view)->isModified()) {
            return $this;
        }
		$this->count_view = $count_view;
		return $this;
    }

    /**
     * @name            getCountView ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->count_view
     */
    public function getCountView() {
        return $this->count_view;
    }

    /**
     * @name            setFile ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $file
     *
     * @return          object                $this
     */
    public function setFile($file) {
        if(!$this->setModified('file', $file)->isModified()) {
            return $this;
        }
		$this->file = $file;
		return $this;
    }

    /**
     * @name            getFile ()
     *                          Returns the value of file property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->file
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * @name                  setGallery ()
     *                                   Sets the gallery property.
     *                                   Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $gallery
     *
     * @return          object                $this
     */
    public function setGallery($gallery) {
        if(!$this->setModified('gallery', $gallery)->isModified()) {
            return $this;
        }
		$this->gallery = $gallery;
		return $this;
    }

    /**
     * @name            getGallery ()
     *                             Returns the value of gallery property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->gallery
     */
    public function getGallery() {
        return $this->gallery;
    }

    /**
     * @name                  setSortOrder ()
     *                                     Sets the sort_order property.
     *                                     Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $sort_order
     *
     * @return          object                $this
     */
    public function setSortOrder($sort_order) {
        if(!$this->setModified('sort_order', $sort_order)->isModified()) {
            return $this;
        }
		$this->sort_order = $sort_order;
		return $this;
    }

    /**
     * @name            getSortOrder ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->sort_order
     */
    public function getSortOrder() {
        return $this->sort_order;
    }

    /**
     * @name                  setType ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $type
     *
     * @return          object                $this
     */
    public function setType($type) {
        if(!$this->setModified('type', $type)->isModified()) {
            return $this;
        }
		$this->type = $type;
		return $this;
    }

    /**
     * @name            getType ()
     *                          Returns the value of type property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->type
     */
    public function getType() {
        return $this->type;
    }

	/**
	 * @name        getStatus ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.2
	 * @version     1.0.2
	 *
	 * @return      mixed
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @name        setStatus ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.2
	 * @version     1.0.2
	 *
	 * @param       mixed $status
	 *
	 * @return      $this
	 */
	public function setStatus($status) {
		if (!$this->setModified('status', $status)->isModified()) {
			return $this;
		}
		$this->status = $status;

		return $this;
	}

}
/**
 * Change Log:
 * **************************************
 * v1.0.2                      06.08.2015
 * 06.08.2015
 * **************************************
 * FR :: get/setStats methods added.
 *
 * **************************************
 * v1.0.1                      Murat Ünal
 * 09.09.2013
 * **************************************
 * A getCountView()
 * A getDateAdded()
 * A getFile()
 * A getGallery()
 * A getSortOrder()
 * A getType()
 * A setCountView()
 * A setDateAdded()
 * A setFile()
 * A setGallery()
 * A setSortOrder()
 * A setType()
 *
 */