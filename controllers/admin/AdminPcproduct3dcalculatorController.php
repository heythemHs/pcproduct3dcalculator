<?php
class AdminPcproduct3dcalculatorController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'pc3d_material';
        $this->className = 'stdClass';
        $this->lang = false;
        $this->list_id = 'pc3d_material';
        $this->identifier = 'id_pc3d_material';

        parent::__construct();

        $this->fields_list = [
            'id_pc3d_material' => ['title' => $this->l('ID'), 'class' => 'fixed-width-xs'],
            'name' => ['title' => $this->l('Name')],
            'density' => ['title' => $this->l('Density (g/cm³)')],
            'price_per_gram' => ['title' => $this->l('Price per gram')],
            'active' => ['title' => $this->l('Active'), 'type' => 'bool'],
        ];

        $this->bulk_actions = false;
    }

    public function renderList()
    {
        $this->_select = 'm.*';
        $this->_join = ' AS m';
        $this->_defaultOrderBy = 'id_pc3d_material';
        $this->_defaultOrderWay = 'DESC';

        return parent::renderList();
    }
}
