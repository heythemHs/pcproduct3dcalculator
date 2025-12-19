<?php
/**
 * Front Controller for Quote Page
 * Displays the 3D file upload form and calculator
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'pcproduct3dcalculator/classes/Pc3dMaterial.php';
require_once _PS_MODULE_DIR_ . 'pcproduct3dcalculator/classes/Pc3dUpload.php';

class Pcproduct3dcalculatorQuoteModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;

    public function initContent()
    {
        parent::initContent();

        if (!Configuration::get('PC3D_ENABLED')) {
            Tools::redirect('index.php');
            return;
        }

        $materials = Pc3dMaterial::getActiveMaterials();

        // Get existing uploads for this cart/customer
        $uploads = [];
        if ($this->context->cart->id) {
            $uploads = Pc3dUpload::getByCartId($this->context->cart->id);
        }

        $this->context->smarty->assign([
            'pc3d_materials' => $materials,
            'pc3d_uploads' => $uploads,
            'pc3d_max_file_size' => (int) Configuration::get('PC3D_MAX_FILE_SIZE'),
            'pc3d_default_infill' => (int) Configuration::get('PC3D_DEFAULT_INFILL'),
            'pc3d_show_volume' => (bool) Configuration::get('PC3D_SHOW_VOLUME'),
            'pc3d_show_weight' => (bool) Configuration::get('PC3D_SHOW_WEIGHT'),
            'pc3d_allow_quote' => (bool) Configuration::get('PC3D_ALLOW_QUOTE_REQUEST'),
            'pc3d_ajax_url' => $this->context->link->getModuleLink($this->module->name, 'ajax', [], true),
            'pc3d_currency' => $this->context->currency,
        ]);

        $this->setTemplate('module:pcproduct3dcalculator/views/templates/front/quote.tpl');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => $this->module->l('3D Print Quote', 'quote'),
            'url' => $this->context->link->getModuleLink('pcproduct3dcalculator', 'quote'),
        ];

        return $breadcrumb;
    }

    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = $this->module->l('3D Print Quote Calculator', 'quote');
        $page['meta']['description'] = $this->module->l('Upload your 3D file and get an instant quote for 3D printing', 'quote');

        return $page;
    }
}
