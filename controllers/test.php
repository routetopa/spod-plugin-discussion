<?php

class SPODDISCUSSION_CTRL_Test extends OW_ActionController
{
    public function index()
    {
        // ADD DATALET DEFINITIONS
        $this->assign('datalet_definition_import', ODE_CLASS_Tools::getInstance()->get_all_datalet_definitions());

        OW::getDocument()->getMasterPage()->setTemplate(OW::getPluginManager()->getPlugin('spodagora')->getRootDir() . 'master_pages/main.html');
        $this->addComponent('discussion', new SPODDISCUSSION_CMP_Discussion(1));

        OW::getDocument()->addOnloadScript('SPODDISCUSSION.init();');
    }
}