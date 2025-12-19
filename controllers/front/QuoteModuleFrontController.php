<?php
class Pcproduct3dcalculatorQuoteModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'upload_action' => $this->context->link->getModuleLink($this->module->name, 'quote', [], true),
            'max_file_size_mb' => 10,
            'allowed_extensions' => ['stl', 'obj'],
        ]);

        $this->setTemplate('module:pcproduct3dcalculator/views/templates/front/quote.tpl');
    }
}
