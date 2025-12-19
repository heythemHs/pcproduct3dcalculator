<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Pcproduct3dcalculator extends Module
{
    public function __construct()
    {
        $this->name = 'pcproduct3dcalculator';
        $this->tab = 'pricing_promotion';
        $this->version = '0.1.0';
        $this->author = 'pcproduct3dcalculator';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('3D Printing Product Calculator');
        $this->description = $this->l('Upload STL/OBJ files, calculate volume/weight, and estimate price with configurable materials and options.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayShoppingCart')
            && $this->installTab()
            && $this->installDatabase();
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && $this->removeDatabase()
            && parent::uninstall();
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminPcproduct3dcalculator';
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        $tab->active = 1;

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('3D Calculator');
        }

        return $tab->add();
    }

    private function uninstallTab()
    {
        $tabId = (int) Tab::getIdFromClassName('AdminPcproduct3dcalculator');

        if ($tabId) {
            $tab = new Tab($tabId);
            return $tab->delete();
        }

        return true;
    }

    private function installDatabase()
    {
        $sql = [];

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pc3d_material` (
            `id_pc3d_material` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(64) NOT NULL,
            `density` DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
            `price_per_gram` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_pc3d_material`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pc3d_upload` (
            `id_pc3d_upload` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_cart` INT(10) UNSIGNED NULL,
            `filename` VARCHAR(255) NOT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `volume_cm3` DECIMAL(20,6) DEFAULT NULL,
            `weight_grams` DECIMAL(20,6) DEFAULT NULL,
            `material_id` INT(11) UNSIGNED NULL,
            `infill_percent` DECIMAL(5,2) DEFAULT 100.00,
            `estimated_price` DECIMAL(20,6) DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_pc3d_upload`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4;';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function removeDatabase()
    {
        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'pc3d_upload`';
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'pc3d_material`';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerStylesheet(
            $this->name.'-style',
            'modules/'.$this->name.'/views/css/pcproduct3dcalculator.css',
            ['media' => 'all', 'priority' => 150]
        );
    }

    public function hookDisplayShoppingCart($params)
    {
        $this->context->smarty->assign([
            'pc3d_uploads' => $this->getCartUploads(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/cart-summary.tpl');
    }

    private function getCartUploads()
    {
        $cartId = (int) $this->context->cart->id;

        if (!$cartId) {
            return [];
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('pc3d_upload');
        $sql->where('id_cart = '.(int) $cartId);

        return Db::getInstance()->executeS($sql);
    }
}
