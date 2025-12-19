<?php
/**
 * Admin Controller for Materials Management
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'pcproduct3dcalculator/classes/Pc3dMaterial.php';

class AdminPc3dMaterialsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'pc3d_material';
        $this->className = 'Pc3dMaterial';
        $this->lang = false;
        $this->list_id = 'pc3d_material';
        $this->identifier = 'id_pc3d_material';
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
        $this->position_identifier = 'id_pc3d_material';

        parent::__construct();

        $this->fields_list = [
            'id_pc3d_material' => [
                'title' => $this->l('ID'),
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
            'name' => [
                'title' => $this->l('Name'),
                'filter_key' => 'a!name',
            ],
            'color' => [
                'title' => $this->l('Color'),
                'callback' => 'displayColor',
                'orderby' => false,
                'search' => false,
            ],
            'density' => [
                'title' => $this->l('Density (g/cm³)'),
                'align' => 'right',
                'type' => 'float',
            ],
            'price_per_gram' => [
                'title' => $this->l('Price/gram'),
                'align' => 'right',
                'type' => 'price',
                'currency' => true,
            ],
            'active' => [
                'title' => $this->l('Active'),
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
            'position' => [
                'title' => $this->l('Position'),
                'position' => 'position',
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ],
            'enableSelection' => [
                'text' => $this->l('Enable selection'),
                'icon' => 'icon-power-off text-success',
            ],
            'disableSelection' => [
                'text' => $this->l('Disable selection'),
                'icon' => 'icon-power-off text-danger',
            ],
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function displayColor($value, $row)
    {
        if (!$value) {
            return '-';
        }

        return '<span style="display:inline-block;width:20px;height:20px;background-color:' . htmlspecialchars($value) . ';border:1px solid #ccc;border-radius:3px;vertical-align:middle;"></span> ' . htmlspecialchars($value);
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Material'),
                'icon' => 'icon-cube',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => true,
                    'class' => 'fixed-width-xxl',
                    'hint' => $this->l('Material name (e.g., PLA, ABS, PETG)'),
                ],
                [
                    'type' => 'color',
                    'label' => $this->l('Color'),
                    'name' => 'color',
                    'hint' => $this->l('Display color for this material'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Density'),
                    'name' => 'density',
                    'required' => true,
                    'class' => 'fixed-width-md',
                    'suffix' => 'g/cm³',
                    'hint' => $this->l('Material density in grams per cubic centimeter'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Price per gram'),
                    'name' => 'price_per_gram',
                    'required' => true,
                    'class' => 'fixed-width-md',
                    'prefix' => $this->context->currency->sign,
                    'hint' => $this->l('Price per gram of material'),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Description'),
                    'name' => 'description',
                    'autoload_rte' => false,
                    'rows' => 3,
                    'hint' => $this->l('Optional description of material properties'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        return parent::renderForm();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_material'] = [
                'href' => self::$currentIndex . '&addpc3d_material&token=' . $this->token,
                'desc' => $this->l('Add new material'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function ajaxProcessUpdatePositions()
    {
        $way = (int) Tools::getValue('way');
        $id = (int) Tools::getValue('id');
        $positions = Tools::getValue('pc3d_material');

        if (!is_array($positions)) {
            die(json_encode(['error' => 'Invalid positions data']));
        }

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);
            $materialId = (int) $pos[2];

            Db::getInstance()->update(
                'pc3d_material',
                ['position' => (int) $position],
                'id_pc3d_material = ' . $materialId
            );
        }

        die(json_encode(['success' => true]));
    }

    public function processDelete()
    {
        $material = new Pc3dMaterial((int) Tools::getValue('id_pc3d_material'));

        if (Validate::isLoadedObject($material)) {
            if ($material->delete()) {
                $this->confirmations[] = $this->l('Material deleted successfully.');
            } else {
                $this->errors[] = $this->l('An error occurred while deleting the material.');
            }
        }

        return parent::processDelete();
    }

    public function processBulkEnableSelection()
    {
        return $this->processBulkStatusSelection(1);
    }

    public function processBulkDisableSelection()
    {
        return $this->processBulkStatusSelection(0);
    }

    protected function processBulkStatusSelection($status)
    {
        $ids = Tools::getValue('pc3d_materialBox');

        if (!is_array($ids) || empty($ids)) {
            $this->errors[] = $this->l('Please select at least one item.');
            return false;
        }

        foreach ($ids as $id) {
            $material = new Pc3dMaterial((int) $id);
            if (Validate::isLoadedObject($material)) {
                $material->active = (int) $status;
                $material->update();
            }
        }

        $this->confirmations[] = $this->l('Selection updated successfully.');

        return true;
    }
}
