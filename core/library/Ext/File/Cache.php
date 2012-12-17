<?php

class Ext_File_Cache
{
    /**
     * @var integer Срок годности неделя.
     */
    const EXPIRE = 604800;

    /**
     * @var array[array]
     */
    private static $_cache;

    /**
     * @return array[array]
     */
    public static function get()
    {
        if (is_null(self::$_cache)) {
            self::$_cache = self::load();
        }

        return self::$_cache;
    }

    protected static function _getDataTable()
    {
        return DB_PREFIX . 'file_cache';
    }

    /**
     * @return array[array]
     */
    public static function load()
    {
        self::cleanExpired();
        $map = array();

        foreach (App_Db::get()->getList('SELECT * FROM ' . self::_getDataTable()) as $item) {
            $map[$item['file_path']] = $item;
        }

        return $map;
    }

    /**
     * @return resource
     */
    public static function clean()
    {
        self::$_cache = null;
        return App_Db::get()->execute('TRUNCATE ' . self::_getDataTable());
    }

    /**
     * @return resource
     */
    public static function cleanExpired()
    {
        self::$_cache = null;

        $tbl = self::_getDataTable();
        $past = time() - self::EXPIRE;

        return App_Db::get()->execute("DELETE FROM `$tbl` WHERE `creation_time` <= $past");
    }

    /**
     * @param string $_path
     * @return resource|false
     */
    public static function delete($_path)
    {
        $instance = self::getCache($_path);

        if ($instance) {
            $tbl = self::_getDataTable();
            $key = $tbl . '_id';

            unset(self::$_cache[$_path]);
            return App_Db::get()->execute("DELETE FROM `$tbl` WHERE `$key` = $instance[$key]");

        } else {
            return false;
        }
    }

    /**
     * @param string $_path
     * @return array|false
     */
    public static function getCache($_path)
    {
        self::get();
        return key_exists($_path, self::$_cache) ? self::$_cache[$_path] : false;
    }

    /**
     * @param string $_filePath
     * @param integer $_size
     * @param integer $_width
     * @param integer $_height
     * @param string $_mime
     * @return array|false
     */
    public static function save($_filePath, $_size, $_width = null, $_height = null, $_mime = null)
    {
        $size = (int) $_size;
        if ($size == 0) return false;

        $instance = array(
            'size' => $_size,
            'file_path' => $_filePath,
            'creation_time' => time()
        );

        $width = (int) $_width;
        $height = (int) $_height;

        if ($width > 0 && $height > 0) {
            $instance['width'] = $width;
            $instance['height'] = $height;
        }

        if ($_mime) {
            $instance['mime'] = $_mime;
        }

        self::delete($instance['file_path']);

        App_Db::get()->execute(
            'INSERT INTO ' . self::_getDataTable() .
            App_Db::get()->getQueryFields($instance, 'insert')
        );

        $instance[self::_getDataTable() . '_id'] = App_Db::get()->getLastInsertedId();
        self::$_cache[$instance['file_path']] = $instance;

        return $instance;
    }

    /**
     * @param Ext_File|Ext_Image $_file
     * @return array|false
     */
    public static function saveFile(Ext_File $_file)
    {
        if ($_file instanceof Ext_Image) {
            return self::save(
                $_file->getPath(),
                $_file->getSize(),
                $_file->getWidth(),
                $_file->getHeight(),
                $_file->getMime()
            );

        } else {
            return self::save($_file->getPath(), $_file->getSize());
        }
    }

    /**
     * @param string $_path
     * @return Ext_File|Ext_Image|false
     */
    public static function getFile($_path)
    {
        $instance = self::getCache($_path);

        if (
            $instance &&
            !empty($instance['file_path']) &&
            is_file($instance['file_path'])
        ) {
            if (Ext_File::isImage($instance['file_path'])) {
                $file = new Ext_Image();

                if (!empty($instance['width']) && !empty($instance['height'])) {
                    $file->setWidth($instance['width']);
                    $file->setHeight($instance['height']);
                }

            } else {
                $file = new Ext_File();
            }

            $file->setPath($instance['file_path']);
            $file->setSize($instance['size']);

            if (!empty($instance['mime'])) {
                $file->setMime($instance['mime']);
            }

            return $file;
        }

        return false;
    }
}
