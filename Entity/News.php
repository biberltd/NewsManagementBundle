<?php
/**
 * @author		Can Berkol
 * @author		Murat Ünal
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        25.12.2015
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
    private $date_published;

    /** 
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $date_unpublished;

    /** 
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"p"})
     * @var string
     */
    private $status;

    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":1})
     * @var int
     */
    private $sort_order;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=1, nullable=false, options={"default":"n"})
     * @var string
     */
    private $popup;

    /** 
     * @ORM\OneToMany(targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\NewsLocalization", mappedBy="news")
     * @var array
     */
    protected $localizations;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\SiteManagementBundle\Entity\Site")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\SiteManagementBundle\Entity\Site
     */
    private $site;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MemberManagementBundle\Entity\Member")
     * @ORM\JoinColumn(name="author", referencedColumnName="id")
     * @var \BiberLtd\Bundle\MemberManagementBundle\Entity\Member
     */
    private $author;

	/**
	 * @return mixed
	 */
    public function getId(){
        return $this->id;
    }

	/**
	 * @param \DateTime $date_published
	 *
	 * @return $this
	 */
    public function setDatePublished(\DateTime $date_published) {
        if(!$this->setModified('date_published', $date_published)->isModified()) {
            return $this;
        }
		$this->date_published = $date_published;
		return $this;
    }

	/**
	 * @return \DateTime
	 */
    public function getDatePublished() {
        return $this->date_published;
    }

	/**
	 * @param \DateTime $date_unpublished
	 *
	 * @return $this
	 */
    public function setDateUnpublished(\DateTime $date_unpublished) {
        if(!$this->setModified('date_unpublished', $date_unpublished)->isModified()) {
            return $this;
        }
		$this->date_unpublished = $date_unpublished;
		return $this;
    }

	/**
	 * @return \DateTime
	 */
    public function getDateUnpublished() {
        return $this->date_unpublished;
    }

	/**
	 * @param \BiberLtd\Bundle\SiteManagementBundle\Entity\Site $site
	 *
	 * @return $this
	 */
    public function setSite(\BiberLtd\Bundle\SiteManagementBundle\Entity\Site $site) {
        if(!$this->setModified('site', $site)->isModified()) {
            return $this;
        }
		$this->site = $site;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\SiteManagementBundle\Entity\Site
	 */
    public function getSite() {
        return $this->site;
    }

	/**
	 * @param int $sort_order
	 *
	 * @return $this
	 */
    public function setSortOrder(int $sort_order) {
        if(!$this->setModified('sort_order', $sort_order)->isModified()) {
            return $this;
        }
		$this->sort_order = $sort_order;
		return $this;
    }

	/**
	 * @return int
	 */
    public function getSortOrder() {
        return $this->sort_order;
    }

	/**
	 * @param string $status
	 *
	 * @return $this
	 */
    public function setStatus(string $status) {
        if(!$this->setModified('status', $status)->isModified()) {
            return $this;
        }
		$this->status = $status;
		return $this;
    }

	/**
	 * @return string
	 */
    public function getStatus() {
        return $this->status;
    }

	/**
	 * @return \BiberLtd\Bundle\MemberManagementBundle\Entity\Member
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * @param \BiberLtd\Bundle\MemberManagementBundle\Entity\Member $author
	 *
	 * @return $this
	 */
	public function setAuthor(\BiberLtd\Bundle\MemberManagementBundle\Entity\Member $author) {
		if (!$this->setModified('author', $author)->isModified()) {
			return $this;
		}
		$this->author = $author;

		return $this;
	}

	/**
	 * @param string $url
	 *
	 * @return $this
	 */
	public function setUrl(string $url) {
		if(!$this->setModified('url', $url)->isModified()) {
			return $this;
		}
		$this->url = $url;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}
	/**
	 * @param string $status
	 *
	 * @return $this
	 */
	public function setPopup(string $status) {
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