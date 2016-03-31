<?php
/**
 * @name        News
 * @package		BiberLtd\Bundle\CoreBundle\NewsManagementBundle
 *
 * @author		Can Berkol
 * @author		Murat Ünal
 *
 * @version     1.0.4
 * @date        15.06.2015
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\NewsManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreLocalizableEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="news",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idxNDateAdded", columns={"date_added"}),
 *         @ORM\Index(name="idxNDatePublished", columns={"date_published"}),
 *         @ORM\Index(name="idxNDateUnpublished", columns={"date_unpublished"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUNewsId", columns={"id"})}
 * )
 */
class News extends CoreLocalizableEntity
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
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
    private $date_published;

    /** 
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_unpublished;

    /** 
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"p"})
     */
    private $status;

    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":1})
     */
    private $sort_order;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"n"})
     */
    private $popup;

	/**
	 * @ORM\OneToMany(targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\NewsLocalization", mappedBy="news", cascade={"persist"})
	 */
    protected $localizations;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\SiteManagementBundle\Entity\Site")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", onDelete="CASCADE")
     */
    private $site;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MemberManagementBundle\Entity\Member")
     * @ORM\JoinColumn(name="author", referencedColumnName="id")
     */
    private $author;
    /******************************************************************
     * PUBLIC SET AND GET FUNCTIONS                                   *
     ******************************************************************/

    /**
     * @name            getId()
     *                  Gets $id property.
     * .
     * @author          Murat Ünal
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          integer          $this->id
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @name            setDatePublished ()
	 *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $date_published
     *
     * @return          object                $this
     */
    public function setDatePublished($date_published) {
        if(!$this->setModified('date_published', $date_published)->isModified()) {
            return $this;
        }
		$this->date_published = $date_published;
		return $this;
    }

    /**
     * @name            getDatePublished ()
	 *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->date_published
     */
    public function getDatePublished() {
        return $this->date_published;
    }

    /**
     * @name            setDateUnpublished ()
	 *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $date_unpublished
     *
     * @return          object                $this
     */
    public function setDateUnpublished($date_unpublished) {
        if(!$this->setModified('date_unpublished', $date_unpublished)->isModified()) {
            return $this;
        }
		$this->date_unpublished = $date_unpublished;
		return $this;
    }

    /**
     * @name            getDateUnpublished ()
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->date_unpublished
     */
    public function getDateUnpublished() {
        return $this->date_unpublished;
    }

    /**
     * @name            setSite ()
	 *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $site
     *
     * @return          object                $this
     */
    public function setSite($site) {
        if(!$this->setModified('site', $site)->isModified()) {
            return $this;
        }
		$this->site = $site;
		return $this;
    }

    /**
     * @name            getSite ()
	 *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->site
     */
    public function getSite() {
        return $this->site;
    }

    /**
     * @name            setSortOrder ()
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
     * @name            setStatus ()
	 *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $status
     *
     * @return          object                $this
     */
    public function setStatus($status) {
        if(!$this->setModified('status', $status)->isModified()) {
            return $this;
        }
		$this->status = $status;
		return $this;
    }

    /**
     * @name            getStatus ()
	 *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->status
     */
    public function getStatus() {
        return $this->status;
    }

	/**
	 * @name        getAuthor ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.4
	 * @version     1.0.4
	 *
	 * @return      mixed
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * @name        setAuthor ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.4
	 * @version     1.0.4
	 *
	 * @param       mixed $author
	 *
	 * @return      $this
	 */
	public function setAuthor($author) {
		if (!$this->setModified('author', $author)->isModified()) {
			return $this;
		}
		$this->author = $author;

		return $this;
	}

	/**
	 * @name            setStatus ()
	 *
	 * @author          Can Berkol
	 *
	 * @since           1.0.0
	 * @version         1.0.0
	 *
	 * @use             $this->setModified()
	 *
	 * @param           mixed $status
	 *
	 * @return          object                $this
	 */
	public function setUrl($url) {
		if(!$this->setModified('url', $url)->isModified()) {
			return $this;
		}
		$this->url = $url;
		return $this;
	}

	/**
	 * @name            getStatus ()
	 *
	 * @author          Can Berkol
	 *
	 * @since           1.0.0
	 * @version         1.0.0
	 *
	 * @return          mixed           $this->status
	 */
	public function getUrl() {
		return $this->url;
	}
	/**
	 * @param string $status
	 *
	 * @return $this
	 */
	public function setPopup($status) {
		if(!$this->setModified('popup', $status)->isModified()) {
			return $this;
		}
		$this->popup = $status;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPopup() {
		return $this->popup;
	}
}
/**
 * Change Log:
 * **************************************
 * v1.0.4                      15.06.2015
 * Can Berkol
 * **************************************
 * CR :: author property and related get/set methods added.
 *
 * **************************************
 * v1.0.3                      13.06.2015
 * Can Berkol
 * **************************************
 * CR :: , cascade={"persist"} added to localizations property.
 *
 * **************************************
 * v1.0.2                      03.05.2015
 * Can Berkol
 * **************************************
 * CR :: ORM updates.
 *
 * * ************************************
 * v1.0.1                      Murat Ünal
 * 11.10.2013
 * **************************************
 * D get_files_of_news()
 * D set_files_of_news()
 *
 * **************************************
 * v1.0.0                      Murat Ünal
 * 12.09.2013
 * **************************************
 * A get_categories_of_news()
 * A getDateAdded()
 * A getDatePublished()
 * A getDateUnpublished()
 * A get_files_of_news()
 * A getId()
 * A getLocalizations()
 * A getSite()
 * A getSortOrder()
 * A getStatus()
 * A getUrl()
 *
 * A set_categories_of_news()
 * A setDateAdded()
 * A setDatePublished()
 * A setDateUnpublished()
 * A set_files_of_news()
 * A setLocalizations()
 * A setSite()
 * A setSortOrder()
 * A setStatus()
 * A setUrl()
 *
 */