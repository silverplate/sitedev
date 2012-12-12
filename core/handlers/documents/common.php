<?php

abstract class Core_DocumentCommon
extends Core_DocumentHandler
{
    public function execute()
    {
        parent::execute();

        // Шаблон

        $template = $this->Document->getTemplate();
        if ($template->getFile()) {
            $this->setTemplate($template->getFile()->getPath());

        } else {
            $this->setTemplate(TEMPLATES . 'fo.xsl');
        }

        // Site navigation

        $navigationXml = '';
        $navigation = DocumentNavigation::getList(
            array('is_published' => 1),
            null,
            array('name != "robots-sitemap"')
        );

        foreach ($navigation as $i) {
            $navigationXml .= DocumentNavigation::getNavigationXml(
                $i->getAttribute('name'),
                $i->getAttribute('type')
            );
        }

        if ($navigationXml != '') {
            $this->addSystem('<navigation>' . $navigationXml . '</navigation>');
        }
    }
}
