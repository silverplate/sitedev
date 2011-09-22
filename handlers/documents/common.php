<?php

class       DocumentCommon
extends     DocumentHandler
implements  DocumentHandlerInterface
{
    public function execute()
    {
        parent::execute();

        $template = $this->Document->getTemplate();
        if ($template->getFile()) {
            $this->setTemplate($template->getFile()->getPath());

        } else {
            $this->setTemplate(TEMPLATES . 'fo.xsl');
        }


        /*
        * Site navigation
        */
        $navigationXml = '';
        $navigation = DocumentNavigation::getList(array('is_published' => 1),
                                                  null,
                                                  array('name != "robots_sitemap"'));

        foreach ($navigation as $i) {
            $navigationXml .= DocumentNavigation::getNavigationXml($i->getAttribute('name'),
                                                                   $i->getAttribute('type'));
        }

        if ($navigationXml != '') {
            $this->addSystem('<navigation>' . $navigationXml . '</navigation>');
        }
    }
}
