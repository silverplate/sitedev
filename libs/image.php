<?php

class Image {
	private $Path;
	private $Uri;
	private $FileName;
	private $Name;
	private $Extension;
	private $Width;
	private $Height;
	private $Text;

	private $PathStartsWith;
	private $UriStartsWith;

	public function Image($_path, $_path_starts_with = null, $_uri_starts_with = null) {
		$this->SetPathStartsWith($_path_starts_with);
		$this->SetUriStartsWith($_uri_starts_with);
		$this->SetPath($_path);
	}

	public function SetPathStartsWith($_path) {
		$this->PathStartsWith = $_path;
	}

	public function GetPathStartsWith() {
		return $this->PathStartsWith;
	}

	public function ComputeUri() {
		$this->Uri = ($this->GetPathStartsWith())
			? str_replace($this->GetPathStartsWith(), $this->GetUriStartsWith(), $this->GetPath())
			: $this->GetPath();
	}

	public function SetUriStartsWith($_uri) {
		$this->UriStartsWith = $_uri;
	}

	public function GetUriStartsWith() {
		return $this->UriStartsWith;
	}

	public function SetPath($_path) {
		$info = pathinfo($_path);
		$size = getimagesize($_path);

		$this->Path = $_path;
		$this->FileName = $info['basename'];
		$this->Name = substr($info['basename'], 0, strlen($info['basename']) - strlen($info['extension']) - 1);
		$this->Extension = $info['extension'];
		$this->Width = $size[0];
		$this->Height = $size[1];

		$this->ComputeUri();
	}

	public function Delete() {
		if (is_file($this->GetPath())) {
			unlink($this->GetPath());
		}
	}

	public function GetXml($_type_attribute = null) {
		$xml  = '<image' . ((!is_null($_type_attribute) && $_type_attribute != '') ? ' type="' . $_type_attribute . '"' : '') . ' width="' . $this->GetWidth() . '" height="' . $this->GetHeight() . '" uri="' . $this->GetUri() . '"';
		$xml .= ' path="' . $this->GetPath() . '" filename="' . $this->GetFileName() . '" name="' . $this->GetName() . '" extension="' . $this->GetExtension() . '"';
		$xml .= '>';

		if ($this->GetText()) {
			$xml .= '<![CDATA[' . $this->GetText() . ']]>';
		}

		return $xml . '</image>';
	}

	/**
	 * @param DOMDocument $dom
	 * @param string $_type_attribute
	 * @return DOMElement
	 */
	public function GetNode(DOMDocument $dom, $_type_attribute = null) {
		$node = $dom->createElement('image');

		if (!empty($_type_attribute)) {
			$node->setAttribute('type', $_type_attribute);
		}

		$node->setAttribute('width', $this->GetWidth());
		$node->setAttribute('height', $this->GetHeight());
		$node->setAttribute('uri', $this->GetUri());
		$node->setAttribute('path', $this->GetPath());
		$node->setAttribute('filename', $this->GetFileName());
		$node->setAttribute('name', $this->GetName());
		$node->setAttribute('extension', $this->GetExtension());

		if ($this->GetText()) {
			$node->appendChild(
				$dom->createCDATASection($this->GetText())
			);
		}

		return $node;
	}

	public function GetPath() {
		return $this->Path;
	}

	public function GetUri() {
		return $this->Uri;
	}

	public function GetFileName() {
		return $this->FileName;
	}

	public function GetName() {
		return $this->Name;
	}

	public function GetExtension() {
		return $this->Extension;
	}

	public function GetWidth() {
		return (int) $this->Width;
	}

	public function GetHeight() {
		return (int) $this->Height;
	}

	public function SetText($_value) {
		$this->Text = $_value;
	}

	public function GetText() {
		return $this->Text;
	}

	public static function IsImageExtension($_extension) {
		return in_array(strtolower($_extension), array('gif', 'jpeg', 'jpg', 'png'));
	}

	public static function resize($_image, $_width = null, $_height = null, $_new = null)
	{
	    if (empty($_width) && empty($_height)) {
	        return false;
	    }

		switch (strtolower(get_file_extension($_image))) {
			case 'gif':
				$image = imagecreatefromgif($_image);
				break;
			case 'jpg':
			case 'jpeg':
				$image = imagecreatefromjpeg($_image);
				break;
			case 'png':
				$image = imagecreatefrompng($_image);
				break;
		}

		if (isset($image)) {
			$newPath = pathinfo(!empty($_new) ? $_new : $_image);
			$newImage = rtrim($newPath['dirname'], '/') . '/' . get_file_name($newPath['basename']) . '.jpg';

			list($nowWidth, $nowHeight) = getimagesize($_image);
            $width = empty($_width) ? $_height / $nowHeight * $nowWidth : $_width;
            $height = empty($_height) ? $_width / $nowWidth * $nowHeight : $_height;
            $resized = imagecreatetruecolor($width, $height);

			imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $nowWidth, $nowHeight);
			imagejpeg($resized, $newImage, 100);
			chmod($newImage, 0777);

			return true;
		}

		return false;
	}

    public static function getByFileName(array $_files, $_filename)
    {
        foreach ($_files as $file) {
            if (
                self::isImageExtension($file->getExtension()) &&
                $file->getFileName() == $_filename
            ) {
                if ($file instanceof Image) {
                    return $file;

                } else {
                    return new Image($file->getPath(),
                                     $file->getPathStartsWith(),
                                     $file->getUriStartsWith());
                }
            }
        }

        return false;
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
                $image = Image::getByFileName($_files, $illu->getAttribute('alias'));

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
                    $image = new Image($path, DOCUMENT_ROOT, '/');
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

?>
