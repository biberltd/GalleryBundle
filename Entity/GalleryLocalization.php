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
     * @var string
     */
    private $title;

    /** 
     * @ORM\Column(type="string", length=155, nullable=false)
     * @var string
     */
    private $url_key;

    /** 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $keywords;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\GalleryBundle\Entity\Gallery", inversedBy="localizations")
     * @ORM\JoinColumn(name="gallery", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\GalleryBundle\Entity\Gallery
     */
    private $gallery;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
     */
    private $language;

	/**
	 * @param string $description
	 *
	 * @return $this
	 */
    public function setDescription(\string $description) {
        if(!$this->setModified('description', $description)->isModified()) {
            return $this;
        }
		$this->description = $description;
		return $this;
    }

	/**
	 * @return string
	 */
    public function getDescription() {
        return $this->description;
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
	 * @param \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language
	 *
	 * @return $this
	 */
    public function setLanguage(\BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language) {
        if(!$this->setModified('language', $language)->isModified()) {
            return $this;
        }
		$this->language = $language;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
	 */
    public function getLanguage() {
        return $this->language;
    }

	/**
	 * @param string $title
	 *
	 * @return $this
	 */
    public function setTitle(\string $title) {
        if(!$this->setModified('title', $title)->isModified()) {
            return $this;
        }
		$this->title = $title;
		return $this;
    }

	/**
	 * @return string
	 */
    public function getTitle() {
        return $this->title;
    }

	/**
	 * @param string $url_key
	 *
	 * @return $this
	 */
    public function setUrlKey(\string $url_key) {
        if(!$this->setModified('url_key', $url_key)->isModified()) {
            return $this;
        }
		$this->url_key = $url_key;
		return $this;
    }

	/**
	 * @return string
	 */
    public function getUrlKey() {
        return $this->url_key;
    }

	/**
	 * @return string
	 */
    public function getKeywords(){
        return $this->keywords;
    }

	/**
	 * @param string
	 */
    public function setKeywords(\string $keywords){
        if(!$this->setModified('keywords', $keywords)->isModified()){
            return $this;
        }
        $this->keywords = $keywords;

        return $this;
    }
}