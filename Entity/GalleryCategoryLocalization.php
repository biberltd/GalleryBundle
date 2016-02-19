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
 *     name="gallery_category_localization",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idx_u_gallery_category_url_key", columns={"url_key"})}
 * )
 */
class GalleryCategoryLocalization extends CoreEntity
{
    /**
     * @ORM\Column(type="string", length=155, nullable=false)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @var string
     */
    private $url_key;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     * @var \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
     */
    private $language;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\GalleryCategory", inversedBy="localizations")
     * @ORM\JoinColumn(name="category", referencedColumnName="id")
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
	 * @param \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language
	 *
	 * @return $this
	 */
    public function setLanguage(\BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language) {
        if($this->setModified('language', $language)->isModified()) {
            $this->language = $language;
        }

        return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
	 */
    public function getLanguage() {
        return $this->language;
    }

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
    public function setName(string $name) {
        if($this->setModified('name', $name)->isModified()) {
            $this->name = $name;
        }

        return $this;
    }

	/**
	 * @return string
	 */
    public function getName() {
        return $this->name;
    }

	/**
	 * @param string $url_key
	 *
	 * @return $this
	 */
    public function setUrlKey(string $url_key) {
        if($this->setModified('url_key', $url_key)->isModified()) {
            $this->url_key = $url_key;
        }

        return $this;
    }

	/**
	 * @return string
	 */
    public function getUrlKey() {
        return $this->url_key;
    }
}