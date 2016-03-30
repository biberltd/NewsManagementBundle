<?php
/**
 * @author		Can Berkol
 * @author		Said İmamoğlu
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        25.12.2015
 */
namespace BiberLtd\Bundle\NewsManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="files_of_news",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idxUFilesOfNews", columns={"news","file"})}
 * )
 */
class FilesOfNews extends CoreEntity
{
    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $count_view;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":1})
     * @var int
     */
    private $sort_order;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\NewsManagementBundle\Entity\News")
     * @ORM\JoinColumn(name="news", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\NewsManagementBundle\Entity\News
     */
    private $news;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\FileManagementBundle\Entity\File")
     * @ORM\JoinColumn(name="file", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\FileManagementBundle\Entity\File
     */
    private $file;

    /**
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
     * @ORM\JoinColumn(name="language", referencedColumnName="id", onDelete="CASCADE")
     * @var \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
     */
    private $language;

    /**
     * @param \BiberLtd\Bundle\FileManagementBundle\Entity\File $file
     *
     * @return $this
     */
    public function setFile(\BiberLtd\Bundle\FileManagementBundle\Entity\File $file) {
        if(!$this->setModified('file', $file)->isModified()) {
            return $this;
        }
        $this->file = $file;
        return $this;
    }

    /**
     * @return \BiberLtd\Bundle\FileManagementBundle\Entity\File
     */
    public function getFile() {
        return $this->file;
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
     * @return int
     */
    public function getCountView(){
        return $this->count_view;
    }

    /**
     * @param int $count_view
     *
     * @return $this
     */
    public function setCountView(int $count_view){
        if(!$this->setModified('count_view', $count_view)->isModified()){
            return $this;
        }
        $this->count_view = $count_view;

        return $this;
    }
}