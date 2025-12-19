<?php
/**
 * PC Product 3D Calculator Module
 *
 * Upload STL/OBJ files, calculate volume/weight, and estimate price
 * with configurable materials and options for 3D printing services.
 *
 * @author PCProduct
 * @version 1.0.0
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/Pc3dFileParser.php';
require_once dirname(__FILE__) . '/classes/Pc3dMaterial.php';
require_once dirname(__FILE__) . '/classes/Pc3dUpload.php';

class Pcproduct3dcalculator extends Module
{
    protected $configKeys = [
        'PC3D_MAX_FILE_SIZE' => 10,
        'PC3D_MINIMUM_PRICE' => 5.00,
        'PC3D_SETUP_FEE' => 0.00,
        'PC3D_DEFAULT_INFILL' => 20,
        'PC3D_INFILL_SURCHARGE_ENABLED' => 0,
        'PC3D_INFILL_SURCHARGE_RATE' => 0.02,
        'PC3D_QUOTE_PRODUCT_ID' => 0,
        'PC3D_ENABLED' => 1,
        'PC3D_SHOW_VOLUME' => 1,
        'PC3D_SHOW_WEIGHT' => 1,
        'PC3D_ALLOW_QUOTE_REQUEST' => 1,
        'PC3D_CLEANUP_DAYS' => 7,
    ];

    public function __construct()
    {
        $this->name = 'pcproduct3dcalculator';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'PCProduct';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('3D Printing Product Calculator');
        $this->description = $this->l('Upload STL/OBJ files, calculate volume/weight, and estimate price with configurable materials and options.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall? All uploaded files and data will be deleted.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        // Create upload directory
        $uploadDir = dirname(__FILE__) . '/upload/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Create .htaccess for security
        $htaccess = $uploadDir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
        }

        // Create index.php for security
        $indexFile = $uploadDir . 'index.php';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, "<?php\nheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');\nheader('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');\nheader('Cache-Control: no-store, no-cache, must-revalidate');\nheader('Cache-Control: post-check=0, pre-check=0', false);\nheader('Pragma: no-cache');\nheader('Location: ../../../');\nexit;\n");
        }

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayShoppingCart')
            && $this->registerHook('displayProductAdditionalInfo')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->registerHook('actionCartSave')
            && $this->installTab()
            && $this->installDatabase()
            && $this->installConfiguration();
    }

    public function uninstall()
    {
        // Clean up uploaded files
        $uploadDir = dirname(__FILE__) . '/upload/';
        if (is_dir($uploadDir)) {
            $files = glob($uploadDir . '*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.htaccess' && basename($file) !== 'index.php') {
                    unlink($file);
                }
            }
        }

        return $this->uninstallTab()
            && $this->uninstallConfiguration()
            && $this->removeDatabase()
            && parent::uninstall();
    }

    private function installTab()
    {
        // Main parent tab
        $parentTab = new Tab();
        $parentTab->class_name = 'AdminPc3dParent';
        $parentTab->module = $this->name;
        $parentTab->id_parent = 0;
        $parentTab->icon = 'print';
        $parentTab->active = 1;

        foreach (Language::getLanguages(true) as $lang) {
            $parentTab->name[$lang['id_lang']] = '3D Calculator';
        }

        if (!$parentTab->add()) {
            return false;
        }

        // Materials tab
        $materialsTab = new Tab();
        $materialsTab->class_name = 'AdminPc3dMaterials';
        $materialsTab->module = $this->name;
        $materialsTab->id_parent = $parentTab->id;
        $materialsTab->active = 1;

        foreach (Language::getLanguages(true) as $lang) {
            $materialsTab->name[$lang['id_lang']] = 'Materials';
        }

        if (!$materialsTab->add()) {
            return false;
        }

        // Uploads tab
        $uploadsTab = new Tab();
        $uploadsTab->class_name = 'AdminPc3dUploads';
        $uploadsTab->module = $this->name;
        $uploadsTab->id_parent = $parentTab->id;
        $uploadsTab->active = 1;

        foreach (Language::getLanguages(true) as $lang) {
            $uploadsTab->name[$lang['id_lang']] = 'Uploads';
        }

        if (!$uploadsTab->add()) {
            return false;
        }

        // Settings tab
        $settingsTab = new Tab();
        $settingsTab->class_name = 'AdminPc3dSettings';
        $settingsTab->module = $this->name;
        $settingsTab->id_parent = $parentTab->id;
        $settingsTab->active = 1;

        foreach (Language::getLanguages(true) as $lang) {
            $settingsTab->name[$lang['id_lang']] = 'Settings';
        }

        return $settingsTab->add();
    }

    private function uninstallTab()
    {
        $tabs = ['AdminPc3dSettings', 'AdminPc3dUploads', 'AdminPc3dMaterials', 'AdminPc3dParent'];

        foreach ($tabs as $className) {
            $tabId = (int) Tab::getIdFromClassName($className);
            if ($tabId) {
                $tab = new Tab($tabId);
                $tab->delete();
            }
        }

        // Also remove old tab if exists
        $oldTabId = (int) Tab::getIdFromClassName('AdminPcproduct3dcalculator');
        if ($oldTabId) {
            $tab = new Tab($oldTabId);
            $tab->delete();
        }

        return true;
    }

    private function installDatabase()
    {
        $sql = [];

        // Materials table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pc3d_material` (
            `id_pc3d_material` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(64) NOT NULL,
            `density` DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
            `price_per_gram` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
            `color` VARCHAR(32) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `position` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_pc3d_material`),
            KEY `active` (`active`),
            KEY `position` (`position`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        // Uploads table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pc3d_upload` (
            `id_pc3d_upload` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_cart` INT(10) UNSIGNED DEFAULT NULL,
            `id_order` INT(10) UNSIGNED DEFAULT NULL,
            `id_customer` INT(10) UNSIGNED DEFAULT NULL,
            `filename` VARCHAR(255) NOT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `file_size` INT(11) UNSIGNED DEFAULT NULL,
            `volume_cm3` DECIMAL(20,6) DEFAULT NULL,
            `weight_grams` DECIMAL(20,6) DEFAULT NULL,
            `material_id` INT(11) UNSIGNED DEFAULT NULL,
            `infill_percent` DECIMAL(5,2) DEFAULT 20.00,
            `estimated_price` DECIMAL(20,6) DEFAULT NULL,
            `status` VARCHAR(32) DEFAULT "pending",
            `notes` TEXT DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_pc3d_upload`),
            KEY `id_cart` (`id_cart`),
            KEY `id_order` (`id_order`),
            KEY `id_customer` (`id_customer`),
            KEY `material_id` (`material_id`),
            KEY `status` (`status`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        // Install default materials
        return Pc3dMaterial::installDefaults();
    }

    private function removeDatabase()
    {
        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pc3d_upload`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pc3d_material`';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function installConfiguration()
    {
        foreach ($this->configKeys as $key => $default) {
            if (!Configuration::updateValue($key, $default)) {
                return false;
            }
        }

        return true;
    }

    private function uninstallConfiguration()
    {
        foreach (array_keys($this->configKeys) as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        $output = '';

        // Redirect to settings controller
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminPc3dSettings'));

        return $output;
    }

    /**
     * Hook: Add CSS/JS to header
     */
    public function hookDisplayHeader()
    {
        if (!Configuration::get('PC3D_ENABLED')) {
            return;
        }

        $this->context->controller->registerStylesheet(
            $this->name . '-style',
            'modules/' . $this->name . '/views/css/pcproduct3dcalculator.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerJavascript(
            $this->name . '-script',
            'modules/' . $this->name . '/views/js/pcproduct3dcalculator.js',
            ['position' => 'bottom', 'priority' => 150]
        );

        // Pass configuration to JavaScript
        Media::addJsDef([
            'pc3d_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax', [], true),
            'pc3d_config' => [
                'max_file_size' => (int) Configuration::get('PC3D_MAX_FILE_SIZE'),
                'default_infill' => (int) Configuration::get('PC3D_DEFAULT_INFILL'),
                'show_volume' => (bool) Configuration::get('PC3D_SHOW_VOLUME'),
                'show_weight' => (bool) Configuration::get('PC3D_SHOW_WEIGHT'),
                'currency_sign' => $this->context->currency->sign,
                'currency_iso' => $this->context->currency->iso_code,
            ],
            'pc3d_translations' => [
                'uploading' => $this->l('Uploading...'),
                'calculating' => $this->l('Calculating...'),
                'error' => $this->l('Error'),
                'success' => $this->l('Success'),
                'file_too_large' => $this->l('File is too large'),
                'invalid_file' => $this->l('Invalid file type'),
                'select_material' => $this->l('Please select a material'),
                'upload_first' => $this->l('Please upload a file first'),
                'added_to_cart' => $this->l('Added to cart'),
            ],
        ]);
    }

    /**
     * Hook: Display in shopping cart
     */
    public function hookDisplayShoppingCart($params)
    {
        if (!Configuration::get('PC3D_ENABLED')) {
            return '';
        }

        $uploads = $this->getCartUploads();

        if (empty($uploads)) {
            return '';
        }

        $this->context->smarty->assign([
            'pc3d_uploads' => $uploads,
            'pc3d_currency' => $this->context->currency,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/cart-summary.tpl');
    }

    /**
     * Hook: Display on product page
     */
    public function hookDisplayProductAdditionalInfo($params)
    {
        if (!Configuration::get('PC3D_ENABLED')) {
            return '';
        }

        $quoteProductId = (int) Configuration::get('PC3D_QUOTE_PRODUCT_ID');

        // Only show on quote product page, or if no specific product is set
        if ($quoteProductId > 0 && (int) $params['product']['id_product'] !== $quoteProductId) {
            return '';
        }

        $materials = Pc3dMaterial::getActiveMaterials();

        $this->context->smarty->assign([
            'pc3d_materials' => $materials,
            'pc3d_max_file_size' => (int) Configuration::get('PC3D_MAX_FILE_SIZE'),
            'pc3d_default_infill' => (int) Configuration::get('PC3D_DEFAULT_INFILL'),
            'pc3d_show_volume' => (bool) Configuration::get('PC3D_SHOW_VOLUME'),
            'pc3d_show_weight' => (bool) Configuration::get('PC3D_SHOW_WEIGHT'),
            'pc3d_allow_quote' => (bool) Configuration::get('PC3D_ALLOW_QUOTE_REQUEST'),
            'pc3d_product_id' => (int) $params['product']['id_product'],
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product-calculator.tpl');
    }

    /**
     * Hook: Order status update
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        $orderId = (int) $params['id_order'];
        $newStatus = (int) $params['newOrderStatus']->id;

        // Get cancelled status ID
        $cancelledStates = [
            Configuration::get('PS_OS_CANCELED'),
            Configuration::get('PS_OS_ERROR'),
        ];

        if (in_array($newStatus, $cancelledStates)) {
            // Mark uploads as cancelled
            Db::getInstance()->update(
                'pc3d_upload',
                ['status' => Pc3dUpload::STATUS_CANCELLED],
                'id_order = ' . $orderId
            );
        }
    }

    /**
     * Hook: Cart save - link uploads when cart is created
     */
    public function hookActionCartSave($params)
    {
        // Reserved for future use
    }

    /**
     * Get uploads for current cart
     */
    private function getCartUploads()
    {
        $cartId = (int) $this->context->cart->id;

        if (!$cartId) {
            return [];
        }

        return Pc3dUpload::getByCartId($cartId);
    }

    /**
     * Get module upload directory
     */
    public function getUploadDir()
    {
        return dirname(__FILE__) . '/upload/';
    }

    /**
     * Generate secure filename
     */
    public function generateSecureFilename($originalName)
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $hash = md5(uniqid(mt_rand(), true) . $originalName);

        return $hash . '.' . $extension;
    }

    /**
     * Process file upload
     *
     * @param array $file $_FILES element
     * @param int $materialId
     * @param float $infillPercent
     * @return array
     */
    public function processUpload($file, $materialId, $infillPercent)
    {
        $maxSize = (int) Configuration::get('PC3D_MAX_FILE_SIZE');

        // Validate file
        $validation = Pc3dFileParser::validateUpload($file, $maxSize);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        // Generate secure filename and move file
        $secureFilename = $this->generateSecureFilename($file['name']);
        $uploadPath = $this->getUploadDir() . $secureFilename;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => false, 'error' => $this->l('Failed to save uploaded file')];
        }

        // Parse file and calculate volume
        try {
            $parseResult = Pc3dFileParser::calculateVolume($uploadPath);
        } catch (Exception $e) {
            unlink($uploadPath);
            return ['success' => false, 'error' => $this->l('Failed to parse 3D file: ') . $e->getMessage()];
        }

        // Create upload record
        $upload = new Pc3dUpload();
        $upload->id_cart = (int) $this->context->cart->id ?: null;
        $upload->id_customer = (int) $this->context->customer->id ?: null;
        $upload->filename = $secureFilename;
        $upload->original_name = $file['name'];
        $upload->file_size = $file['size'];
        $upload->volume_cm3 = $parseResult['volume_cm3'];
        $upload->date_add = date('Y-m-d H:i:s');

        if (!$upload->add(false)) {
            unlink($uploadPath);
            return ['success' => false, 'error' => $this->l('Failed to save upload record')];
        }

        // Calculate price if material specified
        if ($materialId > 0) {
            $material = new Pc3dMaterial($materialId);
            if (Validate::isLoadedObject($material)) {
                $upload->calculateAndSave($material, $infillPercent);
            }
        }

        return [
            'success' => true,
            'upload_id' => $upload->id,
            'filename' => $file['name'],
            'volume_cm3' => round($parseResult['volume_cm3'], 4),
            'triangles' => $parseResult['triangles'],
            'weight_grams' => $upload->weight_grams,
            'estimated_price' => $upload->estimated_price,
        ];
    }

    /**
     * Cron job: Clean up old pending uploads
     */
    public function cleanupOldUploads()
    {
        $days = (int) Configuration::get('PC3D_CLEANUP_DAYS') ?: 7;

        return Pc3dUpload::cleanupOldUploads($days);
    }
}
