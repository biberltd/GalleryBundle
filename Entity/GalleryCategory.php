<?php
namespace BiberLtd\Core\Bundles\GalleryBundle\Entity;
use BiberLtd\Core\CoreLocalizableEntity;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="gallery_category",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idx_n_gallery_category_date_added", columns={"date_added"}),
 *         @ORM\Index(name="idx_n_gallery_category_date_updated", columns={"date_updated"}),
 *         @ORM\Index(name="idx_n_gallery_category_date_removed", columns={"date_removed"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_gallery_category_id", columns={"id"})}
 * )
 */
class GalleryCategory extends CoreLocalizableEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=5)
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
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $date_removed;

    /**
     * @ORM\OneToMany(targetEntity="BiberLtd\Core\Bundles\GalleryBundle\Entity\GalleryCategory", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\OneToMany(
     *     targetEntity="BiberLtd\Core\Bundles\GalleryBundle\Entity\GalleryCategoryLocalization",
     *     mappedBy="category"
     * )
     */
    protected $localizations;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Core\Bundles\GalleryBundle\Entity\GalleryCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id")
     */
    private $parent;
    /**
     * @name            get İd()
     *                      Returns the value of id property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @name                  setParent ()
     *                                  Sets the parent property.
     *                                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $parent
     *
     * @return          object                $this
     */
    public function setParent($parent) {
        if($this->setModified('parent', $parent)->isModified()) {
            $this->parent = $parent;
        }

        return $this;
    }

    /**
     * @name            getParent ()
     *                            Returns the value of parent property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->parent
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * @name                  setChildren ()
     *                                    Sets the children property.
     *                                    Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $children
     *
     * @return          object                $this
     */
    public function setChildren($children) {
        if($this->setModified('children', $children)->isModified()) {
            $this->children = $children;
        }

        return $this;
    }

    /**
     * @name            getChildren ()
     *                              Returns the value of children property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->children
     */
    public function getChildren() {
        return $this->children;
    }


}