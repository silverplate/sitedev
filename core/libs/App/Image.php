<?php

class App_Image extends Ext_Image
{
    /**
     * @param string $_path
     * @param string $_pathStartsWith
     * @param string $_uriStartsWith
     * @return App_Image
     */
    public static function factory($_path, $_pathStartsWith = null, $_uriStartsWith = null)
    {
        $file = parent::factory($_path, $_pathStartsWith, $_uriStartsWith);

        $appFile = new App_Image(
            $file->getPath(),
            $file->getPathStartsWith(),
            $file->getUriStartsWith()
        );

        $appFile->setSize($file->getSize());
        $appFile->setWidth($file->getWidth());
        $appFile->setHeight($file->getHeight());
        $appFile->setMime($file->getMime());

        return $appFile;
    }

    public function getUrl()
    {
        return SITE_URL . $this->getUri();
    }

    public static function applyXmlImages($_xml, array $_files)
    {
        $dom = getXmlObject($_xml);
        $illus = $dom->getElementsByTagName('illu');
        $i = 0;

        while (true) {
            $illu = $illus->item($i);

            if (!$illu) {
                break;

            } else if ($illu->hasAttribute('alias')) {
                $alias = $illu->getAttribute('alias');
                $image = false;

                foreach ($_files as $file) {
                    if (
                        $file->isImage() && (
                            $file->getFilename() == $alias ||
                            $file->getName() == $alias
                        )
                    ) {
                        $image = $file;
                        break;
                    }
                }

                if ($image) {
                    $illu->removeAttribute('alias');
                    $illu->setAttribute('uri', $image->getUri());
                    $illu->setAttribute('width', $image->getWidth());
                    $illu->setAttribute('height', $image->getHeight());
                    $i++;

                } else {
                    dom_remove($illu);
                }

            } else if ($illu->hasAttribute('uri') && !$illu->hasAttribute('width')) {
                $path = rtrim(DOCUMENT_ROOT, '/') . $illu->getAttribute('uri');
                $i++;

                if (is_file($path)) {
                    $image = new Ext_Image($path, DOCUMENT_ROOT, '/');
                    $illu->setAttribute('width', $image->getWidth());
                    $illu->setAttribute('height', $image->getHeight());
                }

            } else {
                $i++;
            }
        }

        return preg_replace('/^<root>|<\/root>$/', '', $dom->saveXml($dom->documentElement));
    }
}
