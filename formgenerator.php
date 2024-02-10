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

use Module\FormGenerator\Database\FormInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require_once __DIR__.'/vendor/autoload.php';
}

class FormGenerator extends Module
{
    public function __construct()
    {
        $this->name = 'formgenerator';
        $this->author = 'G-SCHNEYDER';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.7.7', 'max' => '8.99.99'];

        parent::__construct();

        $this->displayName = $this->l('Form Generator');
        $this->description = $this->l('Create and display contact forms quickly and easily');
    }

    public function install()
    {
        return $this->installTables() && parent::install() && $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        return $this->removeTables() && parent::uninstall();
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminFormGeneratorForm')
        );
    }

    public function hookDisplayHome()
    {
        $repository = $this->get('prestashop.module.FormGenerator.repository.form_repository');
        $langId = $this->context->language->id;
        $forms = $repository->getRandom($langId, 3);

        $this->smarty->assign(['forms' => $forms]);

        return $this->fetch('module:FormGenerator/views/templates/front/home.tpl');
    }

    /**
     * @return bool
     */
    private function installTables()
    {
        /** @var FormInstaller $installer */
        $installer = $this->getInstaller();
        $errors = $installer->createTables();

        return empty($errors);
    }

    /**
     * @return bool
     */
    private function removeTables()
    {
        /** @var FormInstaller $installer */
        $installer = $this->getInstaller();
        $errors = $installer->dropTables();

        return empty($errors);
    }

    /**
     * @return FormInstaller
     */
    private function getInstaller()
    {
        try {
            $installer = $this->get('prestashop.module.FormGenerator.forms.install');
        } catch (Exception $e) {
            // Catch exception in case container is not available, or service is not available
            $installer = null;
        }

        // During install process the modules's service is not available yet so we build it manually
        if (!$installer) {
            $installer = new FormInstaller(
                $this->get('doctrine.dbal.default_connection'),
                $this->getContainer()->getParameter('database_prefix')
            );
        }

        return $installer;
    }
}