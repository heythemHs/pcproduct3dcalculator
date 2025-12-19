<?php
/**
 * Ajax Controller for 3D Calculator
 * Handles file uploads and price calculations
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'pcproduct3dcalculator/classes/Pc3dFileParser.php';
require_once _PS_MODULE_DIR_ . 'pcproduct3dcalculator/classes/Pc3dMaterial.php';
require_once _PS_MODULE_DIR_ . 'pcproduct3dcalculator/classes/Pc3dUpload.php';

class Pcproduct3dcalculatorAjaxModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function init()
    {
        parent::init();

        // Check if module is enabled
        if (!Configuration::get('PC3D_ENABLED')) {
            $this->ajaxResponse(['success' => false, 'error' => 'Module is disabled']);
        }
    }

    public function postProcess()
    {
        $action = Tools::getValue('action');

        switch ($action) {
            case 'upload':
                $this->processUpload();
                break;
            case 'calculate':
                $this->processCalculate();
                break;
            case 'getMaterials':
                $this->processGetMaterials();
                break;
            case 'deleteUpload':
                $this->processDeleteUpload();
                break;
            case 'addToCart':
                $this->processAddToCart();
                break;
            default:
                $this->ajaxResponse(['success' => false, 'error' => 'Invalid action']);
        }
    }

    /**
     * Process file upload
     */
    protected function processUpload()
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('No file uploaded or upload error occurred', 'ajax'),
            ]);
        }

        $materialId = (int) Tools::getValue('material_id', 0);
        $infillPercent = (float) Tools::getValue('infill_percent', Configuration::get('PC3D_DEFAULT_INFILL'));

        // Validate infill
        $infillPercent = max(0, min(100, $infillPercent));

        $result = $this->module->processUpload($_FILES['file'], $materialId, $infillPercent);

        $this->ajaxResponse($result);
    }

    /**
     * Process price calculation for existing upload
     */
    protected function processCalculate()
    {
        $uploadId = (int) Tools::getValue('upload_id');
        $materialId = (int) Tools::getValue('material_id');
        $infillPercent = (float) Tools::getValue('infill_percent', 20);

        if (!$uploadId || !$materialId) {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('Missing upload or material ID', 'ajax'),
            ]);
        }

        $upload = new Pc3dUpload($uploadId);
        if (!Validate::isLoadedObject($upload)) {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('Upload not found', 'ajax'),
            ]);
        }

        $material = new Pc3dMaterial($materialId);
        if (!Validate::isLoadedObject($material) || !$material->active) {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('Material not found or inactive', 'ajax'),
            ]);
        }

        // Validate infill
        $infillPercent = max(0, min(100, $infillPercent));

        // Calculate and save
        $price = $upload->calculateAndSave($material, $infillPercent);

        $this->ajaxResponse([
            'success' => true,
            'upload_id' => $upload->id,
            'volume_cm3' => round($upload->volume_cm3, 4),
            'weight_grams' => round($upload->weight_grams, 2),
            'estimated_price' => round($price, 2),
            'material_name' => $material->name,
            'infill_percent' => $infillPercent,
            'formatted_price' => Tools::displayPrice($price, $this->context->currency),
        ]);
    }

    /**
     * Get available materials
     */
    protected function processGetMaterials()
    {
        $materials = Pc3dMaterial::getActiveMaterials();

        $formattedMaterials = [];
        foreach ($materials as $material) {
            $formattedMaterials[] = [
                'id' => (int) $material['id_pc3d_material'],
                'name' => $material['name'],
                'density' => (float) $material['density'],
                'price_per_gram' => (float) $material['price_per_gram'],
                'color' => $material['color'],
                'description' => $material['description'],
            ];
        }

        $this->ajaxResponse([
            'success' => true,
            'materials' => $formattedMaterials,
        ]);
    }

    /**
     * Delete an upload
     */
    protected function processDeleteUpload()
    {
        $uploadId = (int) Tools::getValue('upload_id');

        if (!$uploadId) {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('Missing upload ID', 'ajax'),
            ]);
        }

        $upload = new Pc3dUpload($uploadId);
        if (!Validate::isLoadedObject($upload)) {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('Upload not found', 'ajax'),
            ]);
        }

        // Security: Only allow deletion of own uploads
        $cartId = (int) $this->context->cart->id;
        $customerId = (int) $this->context->customer->id;

        if ($upload->id_cart != $cartId && $upload->id_customer != $customerId) {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('Permission denied', 'ajax'),
            ]);
        }

        if ($upload->delete()) {
            $this->ajaxResponse([
                'success' => true,
                'message' => $this->module->l('Upload deleted successfully', 'ajax'),
            ]);
        } else {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('Failed to delete upload', 'ajax'),
            ]);
        }
    }

    /**
     * Add upload to cart
     */
    protected function processAddToCart()
    {
        $uploadId = (int) Tools::getValue('upload_id');
        $productId = (int) Tools::getValue('product_id');

        if (!$uploadId) {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('Missing upload ID', 'ajax'),
            ]);
        }

        $upload = new Pc3dUpload($uploadId);
        if (!Validate::isLoadedObject($upload)) {
            $this->ajaxResponse([
                'success' => false,
                'error' => $this->module->l('Upload not found', 'ajax'),
            ]);
        }

        // Ensure cart exists
        if (!$this->context->cart->id) {
            $this->context->cart->add();
            $this->context->cookie->id_cart = (int) $this->context->cart->id;
        }

        // Link upload to cart
        $upload->id_cart = (int) $this->context->cart->id;
        $upload->status = Pc3dUpload::STATUS_IN_CART;
        $upload->update();

        // Optionally add the quote product to cart
        if ($productId > 0) {
            $product = new Product($productId);
            if (Validate::isLoadedObject($product) && $product->active) {
                $this->context->cart->updateQty(
                    1,
                    $productId,
                    null,
                    false,
                    'up',
                    0,
                    null,
                    true
                );
            }
        }

        $this->ajaxResponse([
            'success' => true,
            'message' => $this->module->l('Added to cart successfully', 'ajax'),
            'cart_url' => $this->context->link->getPageLink('cart'),
            'cart_count' => $this->context->cart->nbProducts(),
        ]);
    }

    /**
     * Send JSON response and exit
     */
    protected function ajaxResponse($data)
    {
        header('Content-Type: application/json');
        die(json_encode($data));
    }
}
