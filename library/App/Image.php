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
}
