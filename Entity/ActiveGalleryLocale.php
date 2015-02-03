<?php
/**
 * @name        ActiveGalleryLocale
 * @package		BiberLtd\Core\GalleryBundle
 *
 * @author      Can Berkol
 *
 * @version     1.0.0
 * @date        21.08.2014
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\GalleryBundle\Entity;
use BiberLtd\Bundle\CoreBundle\CoreEntity;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     schema="innodb",
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
     */
    private $gallery;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", nullable=false)
     */
    private $language;

    /**
     * @name            setGallery()
     *                  Sets the gallery property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed                   $gallery
     *
     * @return          object                  $this
     */
    public function setGallery($gallery) {
        if($this->setModified('gallery', $gallery)->isModified()) {
            $this->gallery = $gallery;
        }

        return $this;
    }

    /**
     * @name            getGallery()
     *                  Returns the value of gallery property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->folder
     */
    public function getGallery() {
        return $this->gallery;
    }

    /**
     * @name            setLanguage()
     *                  Sets the language property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed                   $language
     *
     * @return          object                  $this
     */
    public function setLanguage($language) {
        if($this->setModified('language', $language)->isModified()) {
            $this->language = $language;
        }

        return $this;
    }

    /**
     * @name            getLanguage()
     *                  Returns the value of language property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->folder
     */
    public function getLanguage() {
        return $this->language;
    }
}
/**
 * Change Log:
 * **************************************
 * v1.0.0                      Can Berkol
 * 21.08.2014
 * **************************************
 * A getGallery()
 * A getLanguage()
 * A setGallery()
 * A setLanguage()
 */