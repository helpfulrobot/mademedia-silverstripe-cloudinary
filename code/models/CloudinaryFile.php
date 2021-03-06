<?php

class CloudinaryFile extends DataObject {

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var array
	 */
	private static $db = array(
		'Title'				=> 'Varchar(200)',
		'FileName'			=> 'Varchar(200)',
		'PublicID'			=> 'Varchar(200)',
		'Version'			=> 'Varchar(200)',
		'Signature'			=> 'Varchar(200)',
		'URL'				=> 'Varchar(500)',
		'SecureURL'			=> 'Varchar(500)',
		'FileType'			=> 'Varchar(100)',
		'FileSize'			=> 'Float',
		'Format'			=> 'Varchar(50)',
		'Caption'			=> 'Varchar(200)',
		'Credit'			=> 'Varchar(200)',
        'Description'       => 'Text',
		'SortOrder'			=> 'Int',

	);

	/**
	 * @var array
	 */
	private static $summary_fields = array(
		'FileName',
		'Date',
		'FileSize'
	);

	/**
	 * @var array
	 */
	private static $searchable_fields = array(
		'FileName'
	);

	/**
	 * SetCloudinaryConfigs
	 *
	 * Check whether the database is ready and update cloudinary
	 * configs from the site configs
	 */
	public static function SetCloudinaryConfigs() {
		$arr = Config::inst()->get('CloudinaryConfigs', 'settings');
		if(isset($arr['CloudName'])
			&& isset($arr['APIKey'])
			&& isset($arr['APISecret'])
			&& !empty($arr['CloudName'])
			&& !empty($arr['APIKey'])
			&& !empty($arr['APISecret'])
		) {
			Cloudinary::config(array(
				"cloud_name"    => $arr['CloudName'],
				"api_key"       => $arr['APIKey'],
				"api_secret"    => $arr['APISecret']
			));
		} else {
			user_error("Cloudinary configs are not defined", E_USER_ERROR);
		}
	}

	/**
	 * @param $strFileName
	 * @return string
	 */
	public static function GetCloudinaryFileForFile($strFileName) {
		$strClass = 'CloudinaryFile';
		$extension = pathinfo($strFileName, PATHINFO_EXTENSION);
		if(in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'tiff', 'ico', 'svg'))){
			$strClass = 'CloudinaryImage';
		}
		else if(in_array($extension, array('mov', 'swf', 'mp4', 'mpeg', 'webm'))){
			$strClass = 'CloudinaryVideo';
		}
		return $strClass;
	}

    /**
     * @param $arguments
     * @param null $content
     * @param null $parser
     * @return string
     *
     * Parse short codes for the cloudinary tags
     */
    static public function cloudinary_files($arguments, $content = null, $parser = null) {
        if(!isset($arguments['id']) || !is_numeric($arguments['id'])) return;

        $file = CloudinaryFile::get()->byID($arguments['id']);
        if($file){

            if($file->ClassName == 'CloudinaryFile') {
                return sprintf('<a href="%s">%s</a>', $file->Link(), $content ? $content : $file->Title);
            } elseif($file->ClassName == 'CloudinaryImage') {
                $strSize = isset($arguments['size']) ? $arguments['size'] : null;
                $arrDefinedSizes = Config::inst()->get('CloudinaryConfigs', 'editor_image_sizes');
                if($strSize && $arrDefinedSizes && isset($arrDefinedSizes[$strSize])){
                    return sprintf('<img src="%s" title="%s">',
                        $file->FillImage($arrDefinedSizes[$strSize]['width'], $arrDefinedSizes[$strSize]['height'])->getSourceURL(),
                        $content ? $content : $file->Title
                    );
                } else {
                    return sprintf('<img src="%s" title="%s">', $file->Link(), $content ? $content : $file->Title);
                }

            } elseif(in_array($file->ClassName, array('VimeoVideo', 'YoutubeVideo','CloudinaryVideo'))) {
                return self::getRelevantHtml($file,$arguments);
            }
        }

    }

	/**
	 * @param $arguments
	 * @param null $content
	 * @param null $parser
	 * @return string
	 *
	 * Parse short codes for the cloudinary tags
	 */
	static public function cloudinary_markdown($arguments, $content = null, $parser = null) {
		if(!isset($arguments['id']) || !is_numeric($arguments['id'])) return;
		$file = CloudinaryFile::get()->byID($arguments['id']);
		if($file){
			if($file->ClassName == 'CloudinaryImage') {
				$alt = "";
				if(isset($arguments['alt']))
					$alt = $arguments['alt'];
				if(isset($arguments['width']) && isset($arguments['height'])){
					return $file->customise(array('width' => $arguments['width'],'height' => $arguments['height'],'alt'=>$alt))->renderWith('MarkDownShortCode');
				} else {
					return $file->customise(array('alt' => $alt))->renderWith('MarkDownShortCode');
				}
			}
		}

	}

    /**
     * @param $file
     * @param $arguments
     * @return string
     *
     * get relevent video tag html
     */
    public static function getRelevantHtml($file,$arguments) {

        $strSize = isset($arguments['size']) ? $arguments['size'] : null;
        $arrDefinedSizes = Config::inst()->get('CloudinaryConfigs', 'editor_video_sizes');
        $width = $height = 0;
        if($strSize && $arrDefinedSizes && isset($arrDefinedSizes[$strSize])){
            $width = $arrDefinedSizes[$strSize]['width'];
            $height = $arrDefinedSizes[$strSize]['height'];

        }
        $arrControls = array(
            'Width' => ($width) ? $width : $arrDefinedSizes['default']['width'],
            'Height' => ($height) ? $height : $arrDefinedSizes['default']['height'],
        );
        $template = 'IframeVideo';
        if(in_array($file->ClassName, array('VimeoVideo', 'YoutubeVideo'))) {
            $arrControls['EmbedURL'] = $file::video_embed_url($file->Link());
        } elseif($file->ClassName == 'CloudinaryVideo'){
            $arrControls['EmbedURL'] = $file->Link();
            $template = 'HTML5Video';
        }
        return $file->customise($arrControls)->renderWith($template);
    }


	/**
	 * @return FieldList
	 * update the CMS fields
	 */
	public function getCMSFields() {
		// Preview
		$previewField = new LiteralField("ImageFull", $this->CMSThumbnail());

		//create the file attributes in a FieldGroup
		$filePreview = CompositeField::create(
			CompositeField::create(
				$previewField
			)->setName("FilePreviewImage")->addExtraClass('cms-file-info-preview'),
			CompositeField::create(
				$fileDataField = CompositeField::create(
                    new ReadonlyField("FileType", _t('AssetTableField.TYPE','File type') . ':'),
					$urlField = new ReadonlyField('ClickableURL', _t('AssetTableField.URL','URL') ,
						sprintf('<a href="%s" target="_blank" download="true">Download the file</a>', $this->Link())
					),
					new DateField_Disabled("Created", _t('AssetTableField.CREATED','First uploaded') . ':'),
					new DateField_Disabled("LastEdited", _t('AssetTableField.LASTEDIT','Last changed') . ':')
				)
			)->setName("FilePreviewData")->addExtraClass('cms-file-info-data')
		)->setName("FilePreview")->addExtraClass('cms-file-info');

		$urlField->dontEscape = true;

		$fields = new FieldList(
			new TabSet('Root',
				new Tab('Main',
					$filePreview,
					new TextField("Title", _t('AssetTableField.TITLE','Title'))
				)
			)
		);

        if($this->ClassName != 'CloudinaryFile'){
            $fields->addFieldsToTab('Root.Main', array(
                new TextField("Caption", _t('AssetTableField.CAPTION','Caption')),
                new TextField("Credit", _t('AssetTableField.CREDIT','Credit'))
            ));
        }else{
            $fields->addFieldToTab('Root.Main', new TextareaField("Description", _t('AssetTableField.DESCRIPTION','Description')));
        }

        if(!in_array($this->ClassName, array('VimeoVideo', 'YoutubeVideo'))){
            $fields->insertAfter(new ReadonlyField("FileName",  _t('AssetTableField.FILENAME','Filename') . ':', $this->FileName), 'FileType');
            $fields->insertAfter(new ReadonlyField("Size", _t('AssetTableField.SIZE','File size') . ':', $this->getSize()), 'FileType');
        }
		
        if($this->ClassName == 'CloudinaryImage') {
			$fields->dataFieldByName('Credit')->setRightTitle(_t('Cloudinary.IMAGECREDITHELP'));
			$fields->dataFieldByName('Caption')->setRightTitle(_t('Cloudinary.IMAGEsCAPTIONHELP'));
		}

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	/**
	 * @param $strField
	 * @param $strValue
	 * @return string
	 */
	public function GetLiteralHTML($strField, $strValue) {
		$str = <<<HTML
<div id='{$strField}' class='field readonly text'>
	<label class='left' for='Form_ItemEditForm_{$strField}'>{$strField}</label>
	<div class='middleColumn'>
	<span id='Form_ItemEditForm_{$strField}' class='readonly text'>
		{$strValue}
	</span>
	</div>
</div>
HTML;
		return $str;
	}

	public function Link() {
		$strLink = "";
		if($this->PublicID){
			$options = $this->options ? $this->options : array(
                'resource_type' => $this->FileType,
                'version'       => $this->Version
            );
			$strLink = Cloudinary::cloudinary_url(
				$this->PublicID . '.' . $this->Format,
				$options
			);
		}elseif($this->URL || $this->SecureURL){
			$strLink = $this->URL ? $this->URL : $this->SecureURL;
		}
		return $strLink;
	}

	/**
	 * @return mixed|null
	 */
	public function getSourceURL() {
		$strSource = '';
		if($this->PublicID){
			$strSource = $this->PublicID . '.' . $this->Format;
		}
        elseif($this->URL || $this->SecureURL){
			$strURL = $this->URL ? $this->URL : $this->SecureURL;
			$strSource = substr($strURL, strrpos($strURL, '/') + 1);
		}

		if($strSource){
			$options = $this->options ? $this->options : array();
			return Cloudinary::cloudinary_url(
				$strSource,
				$options
			);
		}

		return null;
	}


	/**
	 * @param array $options
	 */
	public function CloudinaryURL($options) {
		$strSource = $this->PublicID . '.' . $this->Format;
		Cloudinary::cloudinary_url($strSource, $options);
	}


	/**
	 * @return bool|string
	 */
	public function getSize() {
		return ($this->FileSize) ? File::format_size($this->FileSize) : false;
	}


	/**
	 * @return mixed
	 * get the extension from the file name
	 */
	public function getExtension() {
		return pathinfo($this->FileName, PATHINFO_EXTENSION);
	}


	/**
	 * @return Image_Cached
	 */
	public function StripThumbnail() {
		return new Image_Cached($this->Icon());
	}

	/**
	 * @return Image_Cached
	 */
	public function CMSThumbnail($iWidth = 132, $iHeight = 128, $iQuality = 60) {
		return new Image_Cached($this->Icon());
	}

	/**
	 * @param $iWidth
	 * @param $iHeight
	 * @param int $iQuality
	 * @return CloudinaryImage_Cached
	 */
	public function GetFileImage($iWidth, $iHeight, $iQuality = 70) {
		$clone = $this->duplicate(false);
		$clone->Format = 'jpg';
		return new CloudinaryImage_Cached(array(
			'width'     	=> $iWidth,
			'height'   	 	=> $iHeight,
			'crop'      	=> 'fill',
			'start_offset'	=> 0,
			'resource_type'	=> !in_array($this->FileType, array('youtube', 'vimeo')) ? $this->FileType : 'image',
			'type'			=> in_array($this->FileType, array('youtube', 'vimeo')) ? $this->FileType : '',
			'quality'		=> $iQuality
		), $clone);
	}


	/**
	 * @return mixed|null
	 */
	public function Icon() {
		$ext = strtolower($this->Format);
		if(!Director::fileExists(FRAMEWORK_DIR . "/images/app_icons/{$ext}_32.gif")) {
			$ext = File::get_app_category($ext);
		}
		if(!Director::fileExists(FRAMEWORK_DIR . "/images/app_icons/{$ext}_32.gif")) {
			$ext = "generic";
		}
		return FRAMEWORK_DIR . "/images/app_icons/{$ext}_32.gif";
	}

    /**
     * @return mixed
     */
    public function NameForSummaryField(){
        if(in_array($this->ClassName, array('VimeoVideo', 'YoutubeVideo'))){
            $strName = $this->Title;
        }else{
            $strName = $this->Title;
        }
        return $strName;
    }

} 