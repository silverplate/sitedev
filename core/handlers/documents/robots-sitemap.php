<?php

abstract class Core_DocumentRobotsSitemap
extends Core_DocumentHandler
{
    public function output()
    {
        header('Content-type: text/xml; charset=utf-8');
        echo $this->getHtml();
    }

    public function execute()
    {
        parent::execute();
        $this->setTemplate(TEMPLATES . 'robots_sitemap.xsl');

        $type = App_Cms_Document_Navigation::load('robots-sitemap', 'name');
        if ($type && $type->is_published) {
            $documents = App_Cms_Document_Navigation::getDocuments($type->name);
            $handlers = array();
            $handlerKey = App_Cms_Handler::getPri();
            $sitemap = array();
            $sitemapXml = '';

            foreach ($documents as $document) {
                array_push($sitemap, self::getSitemapItemFromDocument($document));

                if ($document->$handlerKey) {
                    if (!isset($handlers[$document->$handlerKey])) {
                        $handlers[$document->$handlerKey] = $document->getHandler();
                    }

                    $handler = $handlers[$document->$handlerKey];
                    if ($handler) {
                        $class = $handler->getClassName();

                        require_once $handler->getFilename();
                        if (method_exists($class, 'getRobotsSitemapItems')) {
                            $list = call_user_func_array(array($class, 'getRobotsSitemapItems'), array());
                            foreach ($list as $item) {
                                array_push($sitemap, self::getSitemapItem($item['uri'], '0.8'));
                            }
                        }
                    }
                }
            }

            foreach ($sitemap as $item) {
                $sitemapXml .= '<url>';

                foreach ($item as $name => $value) {
                    $sitemapXml .= getCdata($name, $value);
                }

                $sitemapXml .= '</url>';
            }

            $this->addContent($sitemapXml);
        }
    }

    private static function getSitemapItem($_uri, $_priority = 1, $_freq = 'always', $_date = null)
    {
        $date = empty($_date) ? date('c') : date('c', $_date);

        return array('loc' => SITE_URL . $_uri,
                     'lastmod' => $date,
                     'changefreq' => $_freq,
                     'priority' => $_priority);
    }

    private static function getSitemapItemFromDocument($_document)
    {
        return self::getSitemapItem($_document->getUri());
    }
}
