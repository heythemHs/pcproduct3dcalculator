<?php
/**
 * Admin Controller for Uploads Management
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'pcproduct3dcalculator/classes/Pc3dUpload.php';
require_once _PS_MODULE_DIR_ . 'pcproduct3dcalculator/classes/Pc3dMaterial.php';

class AdminPc3dUploadsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'pc3d_upload';
        $this->className = 'Pc3dUpload';
        $this->lang = false;
        $this->list_id = 'pc3d_upload';
        $this->identifier = 'id_pc3d_upload';
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';
        $this->allow_export = true;

        parent::__construct();

        $this->_select = 'm.name AS material_name';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'pc3d_material` m ON (a.material_id = m.id_pc3d_material)';

        $statusOptions = [
            'pending' => $this->l('Pending'),
            'calculated' => $this->l('Calculated'),
            'in_cart' => $this->l('In Cart'),
            'ordered' => $this->l('Ordered'),
            'processing' => $this->l('Processing'),
            'completed' => $this->l('Completed'),
            'cancelled' => $this->l('Cancelled'),
        ];

        $this->fields_list = [
            'id_pc3d_upload' => [
                'title' => $this->l('ID'),
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
            'original_name' => [
                'title' => $this->l('File Name'),
                'callback' => 'displayFileName',
            ],
            'volume_cm3' => [
                'title' => $this->l('Volume (cm³)'),
                'align' => 'right',
                'type' => 'float',
            ],
            'weight_grams' => [
                'title' => $this->l('Weight (g)'),
                'align' => 'right',
                'type' => 'float',
            ],
            'material_name' => [
                'title' => $this->l('Material'),
                'filter_key' => 'm!name',
                'havingFilter' => true,
            ],
            'infill_percent' => [
                'title' => $this->l('Infill %'),
                'align' => 'center',
                'suffix' => '%',
            ],
            'estimated_price' => [
                'title' => $this->l('Price'),
                'align' => 'right',
                'type' => 'price',
                'currency' => true,
            ],
            'status' => [
                'title' => $this->l('Status'),
                'callback' => 'displayStatus',
                'type' => 'select',
                'list' => $statusOptions,
                'filter_key' => 'a!status',
            ],
            'id_order' => [
                'title' => $this->l('Order'),
                'callback' => 'displayOrderLink',
                'align' => 'center',
            ],
            'date_add' => [
                'title' => $this->l('Date'),
                'type' => 'datetime',
                'align' => 'center',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items? This will also remove the uploaded files.'),
                'icon' => 'icon-trash',
            ],
        ];

        $this->addRowAction('view');
        $this->addRowAction('delete');
    }

    public function displayFileName($value, $row)
    {
        $extension = strtoupper(pathinfo($value, PATHINFO_EXTENSION));
        $icon = ($extension === 'STL') ? 'icon-cube' : 'icon-file';

        return '<i class="' . $icon . '"></i> ' . htmlspecialchars($value) . ' <span class="badge">' . $extension . '</span>';
    }

    public function displayStatus($value, $row)
    {
        $statusClasses = [
            'pending' => 'badge-warning',
            'calculated' => 'badge-info',
            'in_cart' => 'badge-primary',
            'ordered' => 'badge-success',
            'processing' => 'badge-info',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger',
        ];

        $class = isset($statusClasses[$value]) ? $statusClasses[$value] : 'badge-default';

        return '<span class="badge ' . $class . '">' . htmlspecialchars(ucfirst($value)) . '</span>';
    }

    public function displayOrderLink($value, $row)
    {
        if (!$value) {
            return '-';
        }

        $link = $this->context->link->getAdminLink('AdminOrders') . '&id_order=' . (int) $value . '&vieworder';

        return '<a href="' . $link . '" target="_blank" class="btn btn-default btn-xs"><i class="icon-shopping-cart"></i> #' . (int) $value . '</a>';
    }

    public function renderView()
    {
        $id = (int) Tools::getValue('id_pc3d_upload');
        $upload = new Pc3dUpload($id);

        if (!Validate::isLoadedObject($upload)) {
            $this->errors[] = $this->l('Upload not found.');
            return parent::renderList();
        }

        $material = null;
        if ($upload->material_id) {
            $material = Pc3dMaterial::getMaterialById($upload->material_id);
        }

        $this->context->smarty->assign([
            'upload' => $upload,
            'material' => $material,
            'file_exists' => $upload->fileExists(),
            'file_size_formatted' => $upload->getFormattedFileSize(),
            'back_url' => self::$currentIndex . '&token=' . $this->token,
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'pcproduct3dcalculator/views/templates/admin/upload-view.tpl');
    }

    public function processDelete()
    {
        $upload = new Pc3dUpload((int) Tools::getValue('id_pc3d_upload'));

        if (Validate::isLoadedObject($upload)) {
            // Delete will also remove the file
            if ($upload->delete()) {
                $this->confirmations[] = $this->l('Upload deleted successfully.');
            } else {
                $this->errors[] = $this->l('An error occurred while deleting the upload.');
            }
        }

        Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
    }

    public function processBulkDelete()
    {
        $ids = Tools::getValue('pc3d_uploadBox');

        if (!is_array($ids) || empty($ids)) {
            $this->errors[] = $this->l('Please select at least one item.');
            return false;
        }

        $deleted = 0;
        foreach ($ids as $id) {
            $upload = new Pc3dUpload((int) $id);
            if (Validate::isLoadedObject($upload)) {
                if ($upload->delete()) {
                    $deleted++;
                }
            }
        }

        $this->confirmations[] = sprintf($this->l('%d upload(s) deleted successfully.'), $deleted);

        return true;
    }

    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['cleanup'] = [
            'href' => self::$currentIndex . '&cleanup=1&token=' . $this->token,
            'desc' => $this->l('Cleanup old uploads'),
            'icon' => 'process-icon-delete',
            'confirm' => $this->l('This will delete all pending uploads older than the configured cleanup period. Continue?'),
        ];

        parent::initPageHeaderToolbar();
    }

    public function postProcess()
    {
        if (Tools::getValue('cleanup')) {
            $days = (int) Configuration::get('PC3D_CLEANUP_DAYS') ?: 7;
            $count = Pc3dUpload::cleanupOldUploads($days);

            $this->confirmations[] = sprintf($this->l('%d old upload(s) cleaned up.'), $count);
        }

        return parent::postProcess();
    }

    public function ajaxProcessDownloadFile()
    {
        $id = (int) Tools::getValue('id_pc3d_upload');
        $upload = new Pc3dUpload($id);

        if (!Validate::isLoadedObject($upload) || !$upload->fileExists()) {
            die(json_encode(['error' => 'File not found']));
        }

        $filePath = $upload->getFilePath();

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $upload->original_name . '"');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
    }
}
