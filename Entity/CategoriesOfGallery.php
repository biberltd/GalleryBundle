<?php
namespace BiberLtd\Core\Bundles\GalleryBundle\Entity;
use BiberLtd\Core\CoreEntity;
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
     */
    public $date_added;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_updated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $date_removed;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Core\Bundles\GalleryBundle\Entity\Gallery")
     * @ORM\JoinColumn(name="gallery", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    private $gallery;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Core\Bundles\GalleryBundle\Entity\GalleryCategory")
     * @ORM\JoinColumn(name="category", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    private $category;

    /**
     * @name                  setCategory ()
     *                                    Sets the category property.
     *                                    Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $category
     *
     * @return          object                $this
     */
    public function setCategory($category) {
        if($this->setModified('category', $category)->isModified()) {
            $this->category = $category;
        }

        return $this;
    }

    /**
     * @name            getCategory ()
     *                              Returns the value of category property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->category
     */
    public function getCategory() {
        return $this->category;
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
        if($this->setModified('gallery', $gallery)->isModified()) {
            $this->gallery = $gallery;
        }

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

}