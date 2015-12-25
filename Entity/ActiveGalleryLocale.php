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
 *     name="active_gallery_locale",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_active_gallery_locale", columns={"gallery","language"})}
 * )
 */
class ActiveGalleryLocale extends CoreEntity
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\Gallery")
     * @ORM\JoinColumn(name="gallery", referencedColumnName="id", nullable=false)
     * @var \BiberLtd\Bundle\GalleryBundle\Entity\Gallery
     */
    private $gallery;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", nullable=false)
     * @var \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
     */
    private $language;

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
}