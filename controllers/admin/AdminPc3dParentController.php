<?php
/**
 * Parent Admin Controller for Menu Structure
 * This controller is not directly accessed, just used for menu hierarchy
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminPc3dParentController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        // Redirect to materials by default
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminPc3dMaterials'));
    }
}
