<?php

class       DocumentDataSubpageNavigation
extends     DocumentDataHandler
{
    /**
     * Expected content:
     * <except folder="send" />
     * <append filename="small" />
     */
    public function execute()
    {
        $content = $this->getContent();
        $except = array();
        $append = array();

        if ($content) {
            $pref = getXmlObject($content);

            foreach ($pref->getElementsByTagName('except') as $item) {
                foreach ($item->attributes as $attr) {
                    if (!isset($except[$attr->name])) {
                        $except[$attr->name] = array();
                    }

                    array_push($except[$attr->name], $attr->value);
                }
            }

            foreach ($pref->getElementsByTagName('append') as $item) {
                foreach ($item->attributes as $attr) {
                    if (!isset($append[$attr->name])) {
                        $append[$attr->name] = array();
                    }

                    array_push($append[$attr->name], $attr->value);
                }
            }
        }

        $rowConds = array();
        $conds = array('parent_id' => $this->_dataDocument->getId(),
                       'is_published' => 1);

        foreach ($except as $attr => $value) {
            array_push($rowConds, $attr . ' != ' . get_db_data($value));
        }

        $children = Document::getList($conds, null, $rowConds);
        $xml = '';

        foreach ($children as $item) {
            $link = $item->getAttribute('link')
                  ? $item->getAttribute('link')
                  : $item->getUri();

            $itemXml = getCdata('title', $item->getTitle());
            $itemAttrs = array('uri' => $item->getUri(), 'link' => $link);

            if (count($append) > 0) {
                foreach ($append as $type => $values) {
                    switch ($type) {
                        case 'filename':
                            foreach ($values as $value) {
                                $file = $item->getFileByName($value);
                                if ($file) {
                                    if (File::isImageExtension($file->getExtension())) {
                                        $file = new Image($file->getPath(),
                                                          $file->getPathStartsWith(),
                                                          $file->getUriStartsWith());
                                    }

                                    $itemXml .= $file->getXml();
                                }
                            }
                            break;
                    }
                }
            }

            $xml .= getNode('item', $itemXml, $itemAttrs);
        }

        $this->setContent($xml);
    }
}
