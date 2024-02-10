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

namespace Module\FormGenerator\Form;

use Module\FormGenerator\Entity\Form;
use Module\FormGenerator\Entity\FormLang;
use Module\FormGenerator\Repository\FormRepository;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataProvider\FormDataProviderInterface;

class FormFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var FormRepository
     */
    private $repository;

    /**
     * @param FormRepository $repository
     */
    public function __construct(FormRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($formId)
    {
        $form = $this->repository->findOneById($formId);

        $formData = [
            'author' => $form->getAuthor(),
        ];
        foreach ($form->getFormLangs() as $formLang) {
            $formData['content'][$formLang->getLang()->getId()] = $formLang->getContent();
        }

        return $formData;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultData()
    {
        return [
            'author' => '',
            'content' => [],
        ];
    }
}
