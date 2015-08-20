<?php
/**
 * @name        GalleryLocalization
 * @package		BiberLtd\Bundle\CoreBundle\GalleryBundle
 *
 * @author		Murat Ünal
 * @version     1.0.2
 * @date        20.08.2015
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
 *     name="gallery_localization",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idx_u_gallery_localization_url_key", columns={"gallery","language","url_key"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_gallery_localization", columns={"gallery","language"})}
 * )
 */
class GalleryLocalization extends CoreEntity
{
    /** 
     * @ORM\Column(type="string", length=55, nullable=false)
     */
    private $title;

    /** 
     * @ORM\Column(type="string", length=155, nullable=false)
     */
    private $url_key;

    /** 
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $keywords;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\Gallery", inversedBy="localizations")
     * @ORM\JoinColumn(name="gallery", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $gallery;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $language;

    /**
     * @name                  setDescription ()
     *                                       Sets the description property.
     *                                       Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $description
     *
     * @return          object                $this
     */
    public function setDescription($description) {
        if(!$this->setModified('description', $description)->isModified()) {
            return $this;
        }
		$this->description = $description;
		return $this;
    }

    /**
     * @name            getDescription ()
     *                                 Returns the value of description property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->description
     */
    public function getDescription() {
        return $this->description;
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
     * @name                  setLanguage ()
     *                                    Sets the language property.
     *                                    Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $language
     *
     * @return          object                $this
     */
    public function setLanguage($language) {
        if(!$this->setModified('language', $language)->isModified()) {
            return $this;
        }
		$this->language = $language;
		return $this;
    }

    /**
     * @name            getLanguage ()
     *                              Returns the value of language property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->language
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @name                  setTitle ()
     *                                 Sets the title property.
     *                                 Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $title
     *
     * @return          object                $this
     */
    public function setTitle($title) {
        if(!$this->setModified('title', $title)->isModified()) {
            return $this;
        }
		$this->title = $title;
		return $this;
    }

    /**
     * @name            getTitle ()
     *                           Returns the value of title property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->title
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @name                  setUrlKey ()
     *                                  Sets the url_key property.
     *                                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $url_key
     *
     * @return          object                $this
     */
    public function setUrlKey($url_key) {
        if(!$this->setModified('url_key', $url_key)->isModified()) {
            return $this;
        }
		$this->url_key = $url_key;
		return $this;
    }

    /**
     * @name            getUrlKey ()
     *                            Returns the value of url_key property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->url_key
     */
    public function getUrlKey() {
        return $this->url_key;
    }

    /**
     * @name        getKeywords ()
     *
     * @author      Can Berkol
     *
     * @since       1.0.0
     * @version     1.0.0
     *
     * @return      mixed
     */
    public function getKeywords(){
        return $this->keywords;
    }

    /**
     * @name        setKeywords ()
     *
     * @author      Can Berkol
     *
     * @since       1.0.0
     * @version     1.0.0
     *
     * @param       mixed $keywords
     *
     * @return      $this
     */
    public function setKeywords($keywords){
        if(!$this->setModified('keywords', $keywords)->isModified()){
            return $this;
        }
        $this->keywords = $keywords;

        return $this;
    }
}
/**
 * Change Log:
 * **************************************
 * v1.0.2                      20.08.2015
 * Can Berkol
 * **************************************
 * FR :: keywords property and get&set methods added.
 *
 * **************************************
 * v1.0.1                      Murat Ünal
 * 09.09.2013
 * **************************************
 * A getDescription()
 * A getGallery()
 * A getLanguage()
 * A getTitle()
 * A getUrlKey()
 * A setDescription()
 * A getGallery()
 * A getLanguage()
 * A getTitle()
 * A getUrlKey()
 *
 */