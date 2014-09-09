<?php
/**
 * @name        CategoriesOfNews
 * @package		BiberLtd\Bundle\CoreBundle\NewsManagementBundle
 *
 * @author		Murat Ünal
 *
 * @version     1.0.0
 * @date        12.09.2013
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Model / Entity class.
 *
 */
namespace BiberLtd\Bundle\NewsManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="categories_of_news",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idx_n_categories_of_news_date_added", columns={"date_added"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_category_of_news", columns={"category","news"})}
 * )
 */
class CategoriesOfNews extends CoreEntity
{
    /** 
     * @ORM\Column(type="integer", length=10, nullable=false)
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_added;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\News")
     * @ORM\JoinColumn(name="news", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $news;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory")
     * @ORM\JoinColumn(name="category", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $news_category;

    /**
     * @name                  setNews ()
     *                                Sets the news property.
     *                                Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $news
     *
     * @return          object                $this
     */
    public function setNews($news) {
        if(!$this->setModified('news', $news)->isModified()) {
            return $this;
        }
		$this->news = $news;
		return $this;
    }

    /**
     * @name            getNews ()
     *                          Returns the value of news property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->news
     */
    public function getNews() {
        return $this->news;
    }

    /**
     * @name                  setNewsCategory ()
     *                                        Sets the news_category property.
     *                                        Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $news_category
     *
     * @return          object                $this
     */
    public function setNewsCategory($news_category) {
        if(!$this->setModified('news_category', $news_category)->isModified()) {
            return $this;
        }
		$this->news_category = $news_category;
		return $this;
    }

    /**
     * @name            getNewsCategory ()
     *                                  Returns the value of news_category property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->news_category
     */
    public function getNewsCategory() {
        return $this->news_category;
    }

    /**
     * @name                  setSortOrder ()
     *                                     Sets the sort_order property.
     *                                     Updates the data only if stored value and value to be set are different.
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
     *                               Returns the value of sort_order property.
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
    /******************************************************************
     * PUBLIC SET AND GET FUNCTIONS                                   *
     ******************************************************************/

}
/**
 * Change Log:
 * **************************************
 * v1.0.0                      Murat Ünal
 * 12.09.2013
 * **************************************
 * A getDateAdded()
 * A getNews()
 * A getNewsCategory()
 * A getSortOrder()
 *
 * A setDateAdded()
 * A setNews()
 * A setNewsCategory()
 * A setSortOrder()
 *
 */