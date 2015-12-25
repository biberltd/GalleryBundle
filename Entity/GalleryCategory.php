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
use BiberLtd\Bundle\CoreBundle\CoreLocalizableEntity;
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
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    public $date_removed;

    /**
     * @ORM\OneToMany(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory", mappedBy="parent")
     * @var array
     */
    private $children;

    /**
     * @ORM\OneToMany(
     *     targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategoryLocalization",
     *     mappedBy="category"
     * )
     * @var array
     */
    protected $localizations;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id")
     * @var \BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory
     */
    private $parent;

	/**
	 * @return int
	 */
    public function getId() {
        return $this->id;
    }

	/**
	 * @param \BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory $parent
	 *
	 * @return $this
	 */
    public function setParent(\BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory $parent) {
        if($this->setModified('parent', $parent)->isModified()) {
            $this->parent = $parent;
        }

        return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory
	 */
    public function getParent() {
        return $this->parent;
    }

	/**
	 * @param array $children
	 *
	 * @return $this
	 */
    public function setChildren(array $children) {
        if($this->setModified('children', $children)->isModified()) {
            $this->children = $children;
        }

        return $this;
    }

	/**
	 * @return array
	 */
    public function getChildren() {
        return $this->children;
    }
}