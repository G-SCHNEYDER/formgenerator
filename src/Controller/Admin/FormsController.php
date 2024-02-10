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

namespace Module\DemoDoctrine\Controller\Admin;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Module\DemoDoctrine\Entity\Form;
use Module\DemoDoctrine\Entity\FormLang;
use Module\DemoDoctrine\Grid\Definition\Factory\FormGridDefinitionFactory;
use Module\DemoDoctrine\Grid\Filters\FormFilters;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Entity\Lang;
use PrestaShopBundle\Entity\Repository\LangRepository;
use PrestaShopBundle\Service\Grid\ResponseBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormsController extends FrameworkBundleAdminController
{
    /**
     * List forms
     *
     * @param FormFilters $filters
     *
     * @return Response
     */
    public function indexAction(FormFilters $filters)
    {
        $formGridFactory = $this->get('prestashop.module.demodoctrine.grid.factory.forms');
        $formGrid = $formGridFactory->getGrid($filters);

        return $this->render(
            '@Modules/demodoctrine/views/templates/admin/index.html.twig',
            [
                'enableSidebar' => true,
                'layoutTitle' => $this->trans('Forms', 'Modules.Demodoctrine.Admin'),
                'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
                'formGrid' => $this->presentGrid($formGrid),
            ]
        );
    }

    /**
     * Provides filters functionality.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function searchAction(Request $request)
    {
        /** @var ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('prestashop.bundle.grid.response_builder');

        return $responseBuilder->buildSearchResponse(
            $this->get('prestashop.module.demodoctrine.grid.definition.factory.forms'),
            $request,
            FormGridDefinitionFactory::GRID_ID,
            'ps_demodoctrine_form_index'
        );
    }

    /**
     * List forms
     *
     * @param Request $request
     *
     * @return Response
     */
    public function generateAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            $generator = $this->get('prestashop.module.demodoctrine.forms.generator');
            $generator->generateForms();
            $this->addFlash('success', $this->trans('Forms were successfully generated.', 'Modules.Demodoctrine.Admin'));

            return $this->redirectToRoute('ps_demodoctrine_form_index');
        }

        return $this->render(
            '@Modules/demodoctrine/views/templates/admin/generate.html.twig',
            [
                'enableSidebar' => true,
                'layoutTitle' => $this->trans('Forms', 'Modules.Demodoctrine.Admin'),
                'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            ]
        );
    }

    /**
     * Create form
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createAction(Request $request)
    {
        $formFormBuilder = $this->get('prestashop.module.demodoctrine.form.identifiable_object.builder.form_form_builder');
        $formForm = $formFormBuilder->getForm();
        $formForm->handleRequest($request);

        $formFormHandler = $this->get('prestashop.module.demodoctrine.form.identifiable_object.handler.form_form_handler');
        $result = $formFormHandler->handle($formForm);

        if (null !== $result->getIdentifiableObjectId()) {
            $this->addFlash(
                'success',
                $this->trans('Successful creation.', 'Admin.Notifications.Success')
            );

            return $this->redirectToRoute('ps_demodoctrine_form_index');
        }

        return $this->render('@Modules/demodoctrine/views/templates/admin/create.html.twig', [
            'formForm' => $formForm->createView(),
        ]);
    }

    /**
     * Edit form
     *
     * @param Request $request
     * @param int $formId
     *
     * @return Response
     */
    public function editAction(Request $request, $formId)
    {
        $formFormBuilder = $this->get('prestashop.module.demodoctrine.form.identifiable_object.builder.form_form_builder');
        $formForm = $formFormBuilder->getFormFor((int) $formId);
        $formForm->handleRequest($request);

        $formFormHandler = $this->get('prestashop.module.demodoctrine.form.identifiable_object.handler.form_form_handler');
        $result = $formFormHandler->handleFor((int) $formId, $formForm);

        if ($result->isSubmitted() && $result->isValid()) {
            $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

            return $this->redirectToRoute('ps_demodoctrine_form_index');
        }

        return $this->render('@Modules/demodoctrine/views/templates/admin/edit.html.twig', [
            'formForm' => $formForm->createView(),
        ]);
    }

    /**
     * Delete form
     *
     * @param int $formId
     *
     * @return Response
     */
    public function deleteAction($formId)
    {
        $repository = $this->get('prestashop.module.demodoctrine.repository.form_repository');
        try {
            $form = $repository->findOneById($formId);
        } catch (EntityNotFoundException $e) {
            $form = null;
        }

        if (null !== $form) {
            /** @var EntityManagerInterface $em */
            $em = $this->get('doctrine.orm.entity_manager');
            $em->remove($form);
            $em->flush();

            $this->addFlash(
                'success',
                $this->trans('Successful deletion.', 'Admin.Notifications.Success')
            );
        } else {
            $this->addFlash(
                'error',
                $this->trans(
                    'Cannot find form %form%',
                    'Modules.Demodoctrine.Admin',
                    ['%form%' => $formId]
                )
            );
        }

        return $this->redirectToRoute('ps_demodoctrine_form_index');
    }

    /**
     * Delete bulk forms
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deleteBulkAction(Request $request)
    {
        $formIds = $request->request->get('form_bulk');
        $repository = $this->get('prestashop.module.demodoctrine.repository.form_repository');
        try {
            $forms = $repository->findById($formIds);
        } catch (EntityNotFoundException $e) {
            $forms = null;
        }
        if (!empty($forms)) {
            /** @var EntityManagerInterface $em */
            $em = $this->get('doctrine.orm.entity_manager');
            foreach ($forms as $form) {
                $em->remove($form);
            }
            $em->flush();

            $this->addFlash(
                'success',
                $this->trans('The selection has been successfully deleted.', 'Admin.Notifications.Success')
            );
        }

        return $this->redirectToRoute('ps_demodoctrine_form_index');
    }

    /**
     * @return array[]
     */
    private function getToolbarButtons()
    {
        return [
            'add' => [
                'desc' => $this->trans('Add new form', 'Modules.Demodoctrine.Admin'),
                'icon' => 'add_circle_outline',
                'href' => $this->generateUrl('ps_demodoctrine_form_create'),
            ],
            'generate' => [
                'desc' => $this->trans('Generate forms', 'Modules.Demodoctrine.Admin'),
                'icon' => 'add_circle_outline',
                'href' => $this->generateUrl('ps_demodoctrine_form_generate'),
            ],
        ];
    }
}
