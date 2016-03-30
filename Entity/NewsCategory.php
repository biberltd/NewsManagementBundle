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
 *     name="news_category",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUNewsCategoryId", columns={"id"})}
 * )
 */
class NewsCategory extends CoreLocalizableEntity
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer", length=10, options={"default":" "})
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_view;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_news;

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
     * @ORM\OneToMany(
     *     targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategoryLocalization",
     *     mappedBy="category"
     * )
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
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory
     */
    private $parent;

	/**
	 * @return mixed
	 */
    public function getId(){
        return $this->id;
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
	 * @return int
	 */
	public function getCountView() {
		return $this->count_view;
	}

	/**
	 * @param int $count_view
	 *
	 * @return $this
	 */
	public function setCountView(int $count_view) {
		if (!$this->setModified('count_view', $count_view)->isModified()) {
			return $this;
		}
		$this->count_view = $count_view;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCountNews() {
		return $this->count_news;
	}

	/**
	 * @param int $count_news
	 *
	 * @return $this
	 */
	public function setCountNews(int $count_news) {
		if (!$this->setModified('count_news', $count_news)->isModified()) {
			return $this;
		}
		$this->count_news = $count_news;

		return $this;
	}

	/**
	 * @return \BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * @param \BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory $parent
	 *
	 * @return $this
	 */
	public function setParent(\BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory $parent) {
		if (!$this->setModified('parent', $parent)->isModified()) {
			return $this;
		}
		$this->parent = $parent;

		return $this;
	}

}