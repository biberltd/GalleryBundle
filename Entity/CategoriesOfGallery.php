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
use BiberLtd\Bundle\CoreBundle\CoreEntity;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="categories_of_gallery",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb","temporary":false},
 *     indexes={
 *         @ORM\Index(name="idx_n_categories_of_gallery_date_added", columns={"date_added"}),
 *         @ORM\Index(name="idx_n_categories_of_gallery_date_updated", columns={"date_updated"}),
 *         @ORM\Index(name="idx_n_categories_of_gallery_date_removed", columns={"date_removed"})
 *     }
 * )
 */
class CategoriesOfGallery extends CoreEntity
{
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
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    public $date_removed;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\Gallery")
     * @ORM\JoinColumn(name="gallery", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     * @var \BiberLtd\Bundle\GalleryBundle\Entity\Gallery
     */
    private $gallery;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory")
     * @ORM\JoinColumn(name="category", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     * @var \BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory
     */
    private $category;

	/**
	 * @param \BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory $category
	 *
	 * @return $this
	 */
    public function setCategory(\BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory $category) {
        if($this->setModified('category', $category)->isModified()) {
            $this->category = $category;
        }

        return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory
	 */
    public function getCategory() {
        return $this->category;
    }

	/**
	 * @param \BiberLtd\Bundle\GalleryBundle\Entity\Gallery $gallery
	 *
	 * @return $this
	 */
    public function setGallery(\BiberLtd\Bundle\GalleryBundle\Entity\Gallery $gallery) {
        if($this->setModified('gallery', $gallery)->isModified()) {
            $this->gallery = $gallery;
        }

        return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\GalleryBundle\Entity\Gallery
	 */
    public function getGallery() {
        return $this->gallery;
    }

}