<?php
/**
 * 2007-2020 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */
declare(strict_types=1);

namespace Module\FormGenerator\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Module\FormGenerator\Repository\FormRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Form
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_form", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=255)
     */
    private $author;

    /**
     * @ORM\OneToMany(targetEntity="Module\FormGenerator\Entity\FormLang", cascade={"persist", "remove"}, mappedBy="form")
     */
    private $formLangs;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_add", type="datetime")
     */
    private $dateAdd;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_upd", type="datetime")
     */
    private $dateUpd;

    public function __construct()
    {
        $this->formLangs = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getFormLangs()
    {
        return $this->formLangs;
    }

    /**
     * @param int $langId
     * @return FormLang|null
     */
    public function getFormLangByLangId(int $langId)
    {
        foreach ($this->formLangs as $formLang) {
            if ($langId === $formLang->getLang()->getId()) {
                return $formLang;
            }
        }

        return null;
    }

    /**
     * @param FormLang $formLang
     * @return $this
     */
    public function addFormLang(FormLang $formLang)
    {
        $formLang->setForm($this);
        $this->formLangs->add($formLang);

        return $this;
    }

    /**
     * @return string
     */
    public function getFormContent()
    {
        if ($this->formLangs->count() <= 0) {
            return '';
        }

        $formLang = $this->formLangs->first();

        return $formLang->getContent();
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     * @return $this
     */
    public function setAuthor(string $author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Set dateAdd.
     *
     * @param DateTime $dateAdd
     *
     * @return $this
     */
    public function setDateAdd(DateTime $dateAdd)
    {
        $this->dateAdd = $dateAdd;

        return $this;
    }

    /**
     * Get dateAdd.
     *
     * @return DateTime
     */
    public function getDateAdd()
    {
        return $this->dateAdd;
    }

    /**
     * Set dateUpd.
     *
     * @param DateTime $dateUpd
     *
     * @return $this
     */
    public function setDateUpd(DateTime $dateUpd)
    {
        $this->dateUpd = $dateUpd;

        return $this;
    }

    /**
     * Get dateUpd.
     *
     * @return DateTime
     */
    public function getDateUpd()
    {
        return $this->dateUpd;
    }

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setDateUpd(new DateTime());

        if ($this->getDateAdd() == null) {
            $this->setDateAdd(new DateTime());
        }
    }
}
