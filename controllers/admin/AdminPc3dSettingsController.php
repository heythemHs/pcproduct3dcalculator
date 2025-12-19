<?php
/**
 * Admin Controller for Module Settings
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminPc3dSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();
    }

    public function renderView()
    {
        return $this->renderConfigurationForm();
    }

    protected function renderConfigurationForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPc3dSettings';
        $helper->currentIndex = self::$currentIndex;
        $helper->token = $this->token;

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        // Get products for dropdown
        $products = Product::getProducts($this->context->language->id, 0, 0, 'id_product', 'ASC');
        $productOptions = [
            ['id_product' => 0, 'name' => $this->l('-- Show on all products --')],
        ];
        foreach ($products as $product) {
            $productOptions[] = [
                'id_product' => $product['id_product'],
                'name' => $product['name'] . ' (ID: ' . $product['id_product'] . ')',
            ];
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('3D Calculator Settings'),
                    'icon' => 'icon-cogs',
                ],
                'tabs' => [
                    'general' => $this->l('General'),
                    'pricing' => $this->l('Pricing'),
                    'display' => $this->l('Display'),
                    'advanced' => $this->l('Advanced'),
                ],
                'input' => [
                    // General Tab
                    [
                        'tab' => 'general',
                        'type' => 'switch',
                        'label' => $this->l('Enable Module'),
                        'name' => 'PC3D_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->l('Enable or disable the 3D calculator functionality'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'tab' => 'general',
                        'type' => 'text',
                        'label' => $this->l('Maximum File Size'),
                        'name' => 'PC3D_MAX_FILE_SIZE',
                        'class' => 'fixed-width-sm',
                        'suffix' => 'MB',
                        'desc' => $this->l('Maximum allowed file size for STL/OBJ uploads'),
                    ],
                    [
                        'tab' => 'general',
                        'type' => 'text',
                        'label' => $this->l('Default Infill Percentage'),
                        'name' => 'PC3D_DEFAULT_INFILL',
                        'class' => 'fixed-width-sm',
                        'suffix' => '%',
                        'desc' => $this->l('Default infill percentage for new quotes'),
                    ],
                    [
                        'tab' => 'general',
                        'type' => 'select',
                        'label' => $this->l('Quote Product'),
                        'name' => 'PC3D_QUOTE_PRODUCT_ID',
                        'desc' => $this->l('Product page where the 3D calculator should appear. Select "Show on all products" to display everywhere.'),
                        'options' => [
                            'query' => $productOptions,
                            'id' => 'id_product',
                            'name' => 'name',
                        ],
                    ],

                    // Pricing Tab
                    [
                        'tab' => 'pricing',
                        'type' => 'text',
                        'label' => $this->l('Minimum Price'),
                        'name' => 'PC3D_MINIMUM_PRICE',
                        'class' => 'fixed-width-md',
                        'prefix' => $this->context->currency->sign,
                        'desc' => $this->l('Minimum price for any quote (will override calculated price if lower)'),
                    ],
                    [
                        'tab' => 'pricing',
                        'type' => 'text',
                        'label' => $this->l('Setup Fee'),
                        'name' => 'PC3D_SETUP_FEE',
                        'class' => 'fixed-width-md',
                        'prefix' => $this->context->currency->sign,
                        'desc' => $this->l('Fixed setup fee added to every quote'),
                    ],
                    [
                        'tab' => 'pricing',
                        'type' => 'switch',
                        'label' => $this->l('Enable Infill Surcharge'),
                        'name' => 'PC3D_INFILL_SURCHARGE_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->l('Add extra charge for infill above 20%'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'tab' => 'pricing',
                        'type' => 'text',
                        'label' => $this->l('Infill Surcharge Rate'),
                        'name' => 'PC3D_INFILL_SURCHARGE_RATE',
                        'class' => 'fixed-width-md',
                        'prefix' => $this->context->currency->sign,
                        'suffix' => $this->l('per % above 20%'),
                        'desc' => $this->l('Amount to charge per percentage point above 20% infill'),
                    ],

                    // Display Tab
                    [
                        'tab' => 'display',
                        'type' => 'switch',
                        'label' => $this->l('Show Volume'),
                        'name' => 'PC3D_SHOW_VOLUME',
                        'is_bool' => true,
                        'desc' => $this->l('Display calculated volume to customers'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'tab' => 'display',
                        'type' => 'switch',
                        'label' => $this->l('Show Weight'),
                        'name' => 'PC3D_SHOW_WEIGHT',
                        'is_bool' => true,
                        'desc' => $this->l('Display estimated weight to customers'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'tab' => 'display',
                        'type' => 'switch',
                        'label' => $this->l('Allow Quote Requests'),
                        'name' => 'PC3D_ALLOW_QUOTE_REQUEST',
                        'is_bool' => true,
                        'desc' => $this->l('Allow customers to request a quote instead of immediate purchase'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],

                    // Advanced Tab
                    [
                        'tab' => 'advanced',
                        'type' => 'text',
                        'label' => $this->l('Cleanup Period'),
                        'name' => 'PC3D_CLEANUP_DAYS',
                        'class' => 'fixed-width-sm',
                        'suffix' => $this->l('days'),
                        'desc' => $this->l('Delete pending uploads older than this many days'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        return [
            'PC3D_ENABLED' => Configuration::get('PC3D_ENABLED'),
            'PC3D_MAX_FILE_SIZE' => Configuration::get('PC3D_MAX_FILE_SIZE'),
            'PC3D_DEFAULT_INFILL' => Configuration::get('PC3D_DEFAULT_INFILL'),
            'PC3D_QUOTE_PRODUCT_ID' => Configuration::get('PC3D_QUOTE_PRODUCT_ID'),
            'PC3D_MINIMUM_PRICE' => Configuration::get('PC3D_MINIMUM_PRICE'),
            'PC3D_SETUP_FEE' => Configuration::get('PC3D_SETUP_FEE'),
            'PC3D_INFILL_SURCHARGE_ENABLED' => Configuration::get('PC3D_INFILL_SURCHARGE_ENABLED'),
            'PC3D_INFILL_SURCHARGE_RATE' => Configuration::get('PC3D_INFILL_SURCHARGE_RATE'),
            'PC3D_SHOW_VOLUME' => Configuration::get('PC3D_SHOW_VOLUME'),
            'PC3D_SHOW_WEIGHT' => Configuration::get('PC3D_SHOW_WEIGHT'),
            'PC3D_ALLOW_QUOTE_REQUEST' => Configuration::get('PC3D_ALLOW_QUOTE_REQUEST'),
            'PC3D_CLEANUP_DAYS' => Configuration::get('PC3D_CLEANUP_DAYS'),
        ];
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitPc3dSettings')) {
            $this->processConfigurationForm();
        }

        return parent::postProcess();
    }

    protected function processConfigurationForm()
    {
        $configKeys = [
            'PC3D_ENABLED' => 'int',
            'PC3D_MAX_FILE_SIZE' => 'int',
            'PC3D_DEFAULT_INFILL' => 'int',
            'PC3D_QUOTE_PRODUCT_ID' => 'int',
            'PC3D_MINIMUM_PRICE' => 'float',
            'PC3D_SETUP_FEE' => 'float',
            'PC3D_INFILL_SURCHARGE_ENABLED' => 'int',
            'PC3D_INFILL_SURCHARGE_RATE' => 'float',
            'PC3D_SHOW_VOLUME' => 'int',
            'PC3D_SHOW_WEIGHT' => 'int',
            'PC3D_ALLOW_QUOTE_REQUEST' => 'int',
            'PC3D_CLEANUP_DAYS' => 'int',
        ];

        $errors = [];

        foreach ($configKeys as $key => $type) {
            $value = Tools::getValue($key);

            switch ($type) {
                case 'int':
                    $value = (int) $value;
                    break;
                case 'float':
                    $value = (float) str_replace(',', '.', $value);
                    break;
            }

            // Validation
            if ($key === 'PC3D_MAX_FILE_SIZE' && $value < 1) {
                $errors[] = $this->l('Maximum file size must be at least 1 MB');
            }

            if ($key === 'PC3D_DEFAULT_INFILL' && ($value < 0 || $value > 100)) {
                $errors[] = $this->l('Default infill must be between 0 and 100');
            }

            if ($key === 'PC3D_CLEANUP_DAYS' && $value < 1) {
                $errors[] = $this->l('Cleanup period must be at least 1 day');
            }

            if (!Configuration::updateValue($key, $value)) {
                $errors[] = sprintf($this->l('Failed to update %s'), $key);
            }
        }

        if (!empty($errors)) {
            $this->errors = array_merge($this->errors, $errors);
        } else {
            $this->confirmations[] = $this->l('Settings saved successfully.');
        }
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        // Remove back button
        unset($this->page_header_toolbar_btn['back']);
    }
}
