<?php
/**
 * @name        NewsCategory
 * @package		BiberLtd\Bundle\CoreBundle\NewsManagementBundle
 *
 * @author		Can Berkol
 * @author		Murat Ünal
 *
 * @version     1.0.2
 * @date        01.05.2015
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
     */
    private $id;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     */
    private $count_view;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     */
    private $count_news;

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
     * @ORM\OneToMany(
     *     targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategoryLocalization",
     *     mappedBy="category"
     * )
     */
    protected $localizations;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\SiteManagementBundle\Entity\Site")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", onDelete="CASCADE")
     */
    private $site;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;
    /******************************************************************
     * PUBLIC SET AND GET FUNCTIONS                                   *
     ******************************************************************/

    /**
     * @name            getId()
     *                  Gets $id property.
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
	 * @name        getCountView ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.0
	 * @version     1.0.0
	 *
	 * @return      mixed
	 */
	public function getCountView() {
		return $this->count_view;
	}

	/**
	 * @name        setCountView ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.0
	 * @version     1.0.0
	 *
	 * @param       mixed $count_view
	 *
	 * @return      $this
	 */
	public function setCountView($count_view) {
		if (!$this->setModified('count_view', $count_view)->isModified()) {
			return $this;
		}
		$this->count_view = $count_view;

		return $this;
	}

	/**
	 * @name        getCountNews ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.0
	 * @version     1.0.0
	 *
	 * @return      mixed
	 */
	public function getCountNews() {
		return $this->count_news;
	}

	/**
	 * @name        setCountNews ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.0
	 * @version     1.0.0
	 *
	 * @param       mixed $count_news
	 *
	 * @return      $this
	 */
	public function setCountNews($count_news) {
		if (!$this->setModified('count_news', $count_news)->isModified()) {
			return $this;
		}
		$this->count_news = $count_news;

		return $this;
	}

	/**
	 * @name        getParent ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.0
	 * @version     1.0.0
	 *
	 * @return      mixed
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * @name        setParent ()
	 *
	 * @author      Can Berkol
	 *
	 * @since       1.0.0
	 * @version     1.0.0
	 *
	 * @param       mixed $parent
	 *
	 * @return      $this
	 */
	public function setParent($parent) {
		if (!$this->setModified('parent', $parent)->isModified()) {
			return $this;
		}
		$this->parent = $parent;

		return $this;
	}

}
/**
 * Change Log:
 * **************************************
 * v1.0.2                      03.05.2015
 * Can Berkol
 * **************************************
 * CR :: ORM updates.
 *
 * **************************************
 * v1.0.1                      Murat Ünal
 * 11.10.2013
 * **************************************
 * D get_categories_of_news()
 * D set_categories_of_news()
 * **************************************
 * v1.0.0                      Murat Ünal
 * 12.09.2013
 * **************************************
 * A getCountNews()
 * A getCountView()
 * A getId()
 * A getLocalizations()
 * A getSite()
 *
 * A setCountNews()
 * A setCountView()
 * A setLocalizations()
 * A setSite()
 *
 */