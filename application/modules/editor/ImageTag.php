<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * Klasse für die Erstellung der Image Tags
 */
abstract class editor_ImageTag {
    const IMAGE_PADDING_WIDTH = 15;
    const IMAGE_PADDING_HEIGHT = 4;
    const TEXT_PADDING_RIGHT = 5;

    /**
     * Enthält die Image-Texte aller bereits von $this->save der aktuellen Objektinstanz gespeicherten oder auf Existenz
     * geprüften Images als Schlüssel
     * @var array
     */
    protected $_existingImages = array();
    /**
     * Imagick Main Instance
     * @var Imagick
     */
    protected $image;
    /**
     * Imagick Color / Font Settings Instance
     * @var ImagickDraw
     */
    protected $draw;
    /**
     * @var integer
     */
    protected $widthImage;
    protected $widthText;
    protected $heightText;
    protected $heightImage;
    /**
     * @var string
     * Unterhalb diesen Pfades speichert die save methode die Grafiken
     * - wird im Konstruktor mit $session->runtimeOptions->dir->tagImagesBasePath
     *   befüllt
     */
    protected $basePath;
    /**
     * @var Zend_Session_Namespace
     */
    protected $_session;
    /**
     * Tagdefinitionen aus der application.ini
     * @var Zend_Config_Ini
     */
    protected $_tagDef;
    /**
     * @var string
     */
    protected $_filename;
    /**
     * @var array enthält alle images, die mit dem aktuellen Objekt erzeugt wurden als Values
     */
    public $_imagesInObject = array();

    public function __construct() {
        $this->existsGd();
        $this->_session = new Zend_Session_Namespace();
        $this->_tagDef = $this->_session->runtimeOptions->imageTag;
        $parts = explode('/', $this->_session->runtimeOptions->dir->tagImagesBasePath);
        $path = array(APPLICATION_PATH, '..', 'public');
        $path = array_merge($path, $parts);
        $path = join(DIRECTORY_SEPARATOR, $path);
        $this->setSaveBasePath($path);
    }

    /**
     * erzeugt und speichert einen Image-Tag, falls noch nicht im SavePath vorhanden
     *
     * @param string $text Text auf dem Tag
     * @param string $hash md5-hash von $text
     * @return self
     */
    public function createAndSaveIfNotExists($text, $hash) {
        $this->_filename = $hash;
        if (!isset($this->_existingImages[$text])) {
            $filepath = $this->generateFilepath($this->_filename);
            $this->_imagesInObject[] = basename($filepath);
            if (!file_exists($filepath)) {
                $this->create($text);
                $this->save();
            }
        }
        $this->_existingImages[$text] = '';
        return $this;
    }

    /**
     * creates the tag with the given tag, saves in memory
     * @param string $text
     * @return self
     */
    public function create($text) {
        $this->text = $text;
        $this->computeMetricsAndInitImage();
        $this->putTextOnImage();
        return $this;
    }

    protected function computeMetricsAndInitImage() {
        $box = $this->calculateTextBox();
        $imageWidth = $box['width'] + $this->_tagDef->horizStart + $this->_tagDef->paddingRight;
        $this->image = imagecreate($imageWidth, $this->_tagDef->height);
    }
    /*
     * Gibt Maße der durch den aktuell in $this->text gesetzten Text erzeugten Box zurück
     *
     * @return array array(
            "left" => integer,
            "top" => integer,
            "width" => integer,
            "height" => integer,
            "box" => integer
        )
     */
    protected function calculateTextBox() {
        $rect = imagettfbbox(
                        $this->_tagDef->fontSize,
                        0,
                        $this->_tagDef->fontFilePath,
                        $this->text
        );
        $minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
        $maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));

        return array(
            "left" => abs($minX) - 1,
            "top" => abs($minY) - 1,
            "width" => $maxX - $minX,
            "height" => $maxY - $minY,
            "box" => $rect
        );
    }

    protected function putTextOnImage() {
        ImageColorAllocate(
                $this->image,
                $this->_tagDef->backColor->R,
                $this->_tagDef->backColor->G,
                $this->_tagDef->backColor->B
        );
        $col = ImageColorAllocate(
                        $this->image,
                        $this->_tagDef->fontColor->R,
                        $this->_tagDef->fontColor->G,
                        $this->_tagDef->fontColor->B
        );
        ImageTTFText(
                $this->image,
                $this->_tagDef->fontSize,
                0,
                $this->_tagDef->horizStart,
                $this->_tagDef->vertStart,
                $col,
                $this->_tagDef->fontFilePath,
                $this->text
        );
    }

    /**
     * renders the tag to the browser
     */
    public function directOutput() {
        /* Output the image with headers */
        header('Content-type: image/png');
        imagepng($this->image, NULL, 9);
    }

    /**
     * setzt den basepath unter welchem die generierten Grafiken gespeichert werden
     * @param string $path
     */
    public function setSaveBasePath($path) {
        $this->basePath = $path;
    }

    /**
     * speichert die generierte Grafik unterhalb von $this->basePath
     * PNG Suffix wird automatisch an den übergebenen Dateinamen angehängt
     * Wird der Dateiname weggelassen, wird md5($text) als Dateinamen genommen
     * @param string $filename [optional]
     * @return self
     */
    public function save($filename = null) {
        $this->checkBasePath();
        if (!empty($this->_filename)) {
            $filename = $this->_filename;
        } elseif (empty($filename)) {
            $filename = md5($this->text);
        }
        imagepng($this->image, $this->generateFilepath($filename), 9);
        return $this;
    }

    protected function checkBasePath() {
        if (empty($this->basePath)) {
            throw new Zend_Exception('ImageTag::$basePath is not set; Path: ' . $this->basePath);
        }
        if (!is_dir($this->basePath)) {
            throw new Zend_Exception('ImageTag::$basePath is no directory; Path: ' . $this->basePath);
        }
    }

    /**
     * genereates filepath from basepath and given filename, adds png suffix
     * @param string $filename
     * @return string
     */
    protected function generateFilepath($filename) {
        return $this->basePath . DIRECTORY_SEPARATOR . $filename . '.png';
    }

    /**
     * @throws Zend_Exception
     */
    protected function existsGd() {
        if (!function_exists('gd_info')) {
            throw new Zend_Exception('GD ist nicht installiert');
        }
    }

}