<?php

abstract class Core_Cms_Document_Controller_RobotsSitemap extends App_Cms_Document_Controller_Common
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
            $controllers = array();
            $controllerKey = App_Cms_Controller::getPri();
            $sitemap = array();
            $sitemapXml = '';

            foreach ($documents as $document) {
                array_push($sitemap, self::getSitemapItemFromDocument($document));

                if ($document->$controllerKey) {
                    if (!isset($controllers[$document->$controllerKey])) {
                        $controllers[$document->$controllerKey] = $document->getController();
                    }

                    $controller = $controllers[$document->$controllerKey];
                    if ($controller) {
                        $class = $controller->getClassName();
                        require_once $controller->getFilename();

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
