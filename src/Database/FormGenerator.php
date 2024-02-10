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

namespace Module\FormGenerator\Database;

use Doctrine\ORM\EntityManagerInterface;
use Module\FormGenerator\Entity\Form;
use Module\FormGenerator\Entity\FormLang;
use Module\FormGenerator\Repository\FormRepository;
use PrestaShopBundle\Entity\Lang;
use PrestaShopBundle\Entity\Repository\LangRepository;

class FormGenerator
{
    /**
     * @var FormRepository
     */
    private $FormRepository;

    /**
     * @var LangRepository
     */
    private $langRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param FormRepository $FormRepository
     * @param LangRepository $langRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        FormRepository $FormRepository,
        LangRepository $langRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->FormRepository = $FormRepository;
        $this->langRepository = $langRepository;
        $this->entityManager = $entityManager;
    }

    public function generateForms()
    {
        $this->removeAllForms();
        $this->insertForms();
    }

    private function removeAllForms()
    {
        $Forms = $this->FormRepository->findAll();
        foreach ($Forms as $Form) {
            $this->entityManager->remove($Form);
        }
        $this->entityManager->flush();
    }

    private function insertForms()
    {
        $languages = $this->langRepository->findAll();

        $FormsDataFile = __DIR__ . '/../../Resources/data/Forms.json';
        $FormsData = json_decode(file_get_contents($FormsDataFile), true);
        foreach ($FormsData as $FormData) {
            $Form = new Form();
            $Form->setAuthor($FormData['author']);
            /** @var Lang $language */
            foreach ($languages as $language) {
                $FormLang = new FormLang();
                $FormLang->setLang($language);
                if (isset($FormData['Forms'][$language->getIsoCode()])) {
                    $FormLang->setContent($FormData['Forms'][$language->getIsoCode()]);
                } else {
                    $FormLang->setContent($FormData['Forms']['en']);
                }
                $Form->addFormLang($FormLang);
            }
            $this->entityManager->persist($Form);
        }

        $this->entityManager->flush();
    }
}
