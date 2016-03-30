<?php
/**
 * @author		Can Berkol
 * @author		Said İmamoğlu
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        23.12.2015
 */
namespace BiberLtd\Bundle\NewsManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="categories_of_news",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={@ORM\Index(name="idxNDateNewsCategoryAdded", columns={"date_added"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUCategoriesOfNews", columns={"category","news"})}
 * )
 */
class CategoriesOfNews extends CoreEntity
{
    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":1})
     * @var int
     */
    private $sort_order;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\News")
     * @ORM\JoinColumn(name="news", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\NewsManagementBundle\Entity\News
     */
    private $news;


	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory")
	 * @ORM\JoinColumn(name="category", referencedColumnName="id", nullable=false, onDelete="CASCADE")
	 * @var \BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory
	 */
	private $category;

	/**
	 * @param \BiberLtd\Bundle\NewsManagementBundle\Entity\News $news
	 *
	 * @return $this
	 */
    public function setNews(\BiberLtd\Bundle\NewsManagementBundle\Entity\News $news) {
        if(!$this->setModified('news', $news)->isModified()) {
            return $this;
        }
		$this->news = $news;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\NewsManagementBundle\Entity\News
	 */
    public function getNews() {
        return $this->news;
    }

	/**
	 * @param \BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory $category
	 *
	 * @return $this
	 */
    public function setCategory(\BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory $category) {
        if(!$this->setModified('category', $category)->isModified()) {
            return $this;
        }
		$this->category = $category;
		return $this;
    }

	/**
	 * @return \BiberLtd\Bundle\NewsManagementBundle\Entity\NewsCategory
	 */
    public function getCategory() {
        return $this->category;
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
}