<?php

include 'Service.php';
include 'Shapes.php';

/**
 * SvgRenderer
 * 
 * @author Michal Stefanak
 * @todo add support for percentage width/height
 * @todo update to take preserveAspectRatio attribute
 * @link http://php.net/manual/en/ref.image.php
 */
class SvgRenderer extends Service
{
    
    private $svg;
    private $format = 'png';
    private $file;
    private $size;
    private $image;
    private $backgroundColor = 'transparent';
    
    /**
     * @var Shapes
     */
    private $shapes;
    
    public function __construct()
    {
        parent::__construct();
        $this->shapes = new Shapes();
    }
    
    public function load($svg)
    {
        $this->svg = file_exists($svg) ? simplexml_load_file($svg) : simplexml_load_string($svg);
        if ( $this->svg === false ) {
            throw new Exception('Invalid XML');
        }

        return $this;
    }

    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }
    
    public function setSize($width, $height)
    {
        $this->size = [$width, $height];
        return $this;
    }
    
    public function setFile($file = null)
    {
        $this->file = $file;
        return $this;
    }

    public function render()
    {
        $attributes = $this->attributesToArray($this->svg);
        $viewbox = [];
        
        if ( empty($this->size) && !empty($attributes['width']) && !empty($attributes['width']) ) {
            $this->setSize($attributes['width'], $attributes['height']);
        }

        $time = microtime(true);
        
        if ( isset($attributes['viewbox']) ) {
            $viewbox = explode(' ', $attributes['viewbox'], 4);
            $this->image = imagecreatetruecolor($viewbox[2] + $viewbox[0], $viewbox[3] + $viewbox[1]);
            
            if ( empty($this->size) ) {
                $this->setSize($viewbox[2], $viewbox[3]);
            }
        } else {
            $this->image = imagecreatetruecolor($attributes['width'], $attributes['height']);
        }
        
        $this->addBackground($this->image);
        $this->shapes->setImage($this->image);

        foreach ( $this->svg AS $shape => $values ) {
            $shape = strtolower($shape);
            if ( method_exists($this->shapes, $shape) ) {
                $this->shapes->setElement($values);
                $this->shapes->{$shape}();
            }
        }
        
        $this->resize($viewbox);
        header('X-Render-Time: ' . (microtime(true) - $time));
        
        //@todo add formats
        //@todo add store to file by $this->file
        switch ( $this->format ) {
            case 'png':
                header('Content-Type: image/png');
                imagepng($this->image);
                break;
        }
        
        imagedestroy($this->image);
    }
    
    /**
     * Resize image
     * 
     * @param array $viewbox
     */
    private function resize($viewbox)
    {
        if ( !empty($viewbox[0]) || !empty($viewbox[1]) ) {
            if ( function_exists('imagecrop') ) {
                $this->image = imagecrop($this->image, $viewbox);
            } else {
                $cropped = imagecreatetruecolor($viewbox[2], $viewbox[3]);
                $this->addBackground($cropped);
                
                imagecopy($cropped, $this->image, 0, 0, $viewbox[0], $viewbox[1], $viewbox[2], $viewbox[3]);
                imagedestroy($this->image);
                $this->image = $cropped;
            }
        }
        
        if ( $this->size[0] != imagesx($this->image) || $this->size[1] != imagesy($this->image) ) {
            $newImage = imagecreatetruecolor($this->size[0], $this->size[1]);
            $this->addBackground($newImage);
            imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $this->size[0], $this->size[1], imagesx($this->image), imagesy($this->image));
            imagedestroy($this->image);
            $this->image = $newImage;
        }
    }
    
    /**
     * @todo add support for background color
     * @param Resource $image Image identifier
     */
    private function addBackground($image)
    {
        if ( $this->backgroundColor == 'transparent' ) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), imagecolorallocatealpha($image, 255, 255, 255, 127));
        }
    }
    
}
