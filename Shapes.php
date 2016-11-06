<?php

include "BezierCurve.php";

/**
 * Shapes - drawing by shape type
 *
 * @author Michal Stefanak
 * @todo add support for empty style "stroke" ..default is black color
 * @todo add check mandatory variables, throw exceptions
 */
class Shapes extends Service
{
    
    private $debug = true;
    
    /**
     * Working SVG element
     * 
     * @var SimpleXMLElement
     */
    private $element;
    /**
     * Working image
     *
     * @var Resource
     */
    private $image;
    /**
     * Element attributes
     *
     * @var array
     */
    private $attributes;
    
    /**
     * Set working svg element
     * 
     * @param SimpleXMLElement $element
     * @return Shapes
     */
    public function setElement(SimpleXMLElement $element)
    {
        $this->element = $element;
        $this->attributes = $this->attributesToArray($element);
        return $this;
    }
    
    /**
     * Set working image for rendering
     * 
     * @param Resource $image Image identifier
     * @return Shapes
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Group <g>
     */
    public function g()
    {
        $element = $this->element;
        $mainAttributes = $this->attributes;
        
        foreach ( $element AS $shape => $values ) {
            $shape = strtolower($shape);
            
            if ( method_exists($this, $shape) ) {
                if ( !empty($mainAttributes) ) {
                    foreach ( $mainAttributes AS $key => $attribute )
                    {
                        $valueAttributes = (array) $values->attributes();
                        if ( !in_array($key, $valueAttributes) || empty($valueAttributes[$key]) ) {
                            $values->addAttribute($key, $attribute);
                        }
                    }
                }
                
                $this->setElement($values);
                $this->{$shape}();
            }
        }
    }
    
    /**
     * Rectangle <rect>
     * 
     * width, height
     * x, y - origin position (left top is 0,0)
     * 
     * @todo add support for radius corner (rx, ry)
     */
    public function rect()
    {
        if ( !isset($this->attributes['x']) ) {
            $this->attributes['x'] = 0;
        }
        if ( !isset($this->attributes['y']) ) {
            $this->attributes['y'] = 0;
        }
        
        if ( !empty($this->attributes['fill']) ) {
            imagefilledrectangle(
                $this->image,
                $this->attributes['x'],
                $this->attributes['y'],
                $this->attributes['x'] + $this->attributes['width'],
                $this->attributes['y'] + $this->attributes['height'],
                $this->color->allocate($this->attributes['fill'], $this->image, $this->getOpacity())
            );
        }
        
        if ( !empty($this->attributes['stroke']) ) {
            if ( empty($this->attributes['stroke-width']) ) {
                $this->attributes['stroke-width'] = 1;
            }
            
            imagesetthickness($this->image, $this->attributes['stroke-width']);
            imagerectangle(
                $this->image,
                $this->attributes['x'],
                $this->attributes['y'],
                $this->attributes['x'] + $this->attributes['width'],
                $this->attributes['y'] + $this->attributes['height'],
                $this->color->allocate($this->attributes['stroke'], $this->image, $this->getOpacity('stroke'))
            );
        }
    }
    
    /**
     * Circle <circle>
     * 
     * cx, cy - center point
     * r - radius
     */
    public function circle()
    {
        $this->ellipse();
    }
    
    /**
     * Ellipse <ellipse>
     * 
     * cx, cy - center point
     * rx - horizontal radius
     * ry - vertical radius
     */
    public function ellipse()
    {
        if ( isset($this->attributes['r']) ) {
            $this->attributes['rx'] = $this->attributes['ry'] = $this->attributes['r'];
        }
        
        if ( !empty($this->attributes['fill']) ) {
            imagefilledellipse(
                $this->image,
                $this->attributes['cx'],
                $this->attributes['cy'],
                $this->attributes['rx'] * 2,
                $this->attributes['ry'] * 2,
                $this->color->allocate($this->attributes['fill'], $this->image, $this->getOpacity())
            );
        }
        
        if ( !empty($this->attributes['stroke']) ) {
            if ( empty($this->attributes['stroke-width']) ) {
                $this->attributes['stroke-width'] = 1;
            }
            
            //bug in PHP doesn't allow use imagesetthickness
            for ( $i = 0; $i <= ceil($this->attributes['stroke-width']); $i++ ) {
                imageellipse(
                    $this->image,
                    $this->attributes['cx'],
                    $this->attributes['cy'],
                    ($this->attributes['rx'] * 2) - $i,
                    ($this->attributes['ry'] * 2) - $i,
                    $this->color->allocate($this->attributes['stroke'], $this->image, $this->getOpacity('stroke'))
                );
            }
        }
    }
    
    /**
     * Line <line>
     * 
     * x1, y1 - begin
     * x2, y2 - end
     */
    public function line()
    {
        if ( empty($this->attributes['stroke-width']) ) {
            $this->attributes['stroke-width'] = 1;
        }
        
        $color = $this->color->allocate($this->attributes['stroke'], $this->image, $this->getOpacity('stroke'));

        switch ( !empty($this->attributes['stroke-linecap']) ? $this->attributes['stroke-linecap'] : 'butt' ) {
            case 'round':
                imagesetthickness($this->image, 0);
                imagefilledellipse($this->image, $this->attributes['x1'], $this->attributes['y1'], $this->attributes['stroke-width'], $this->attributes['stroke-width'], $color);
                imagefilledellipse($this->image, $this->attributes['x2'], $this->attributes['y2'], $this->attributes['stroke-width'], $this->attributes['stroke-width'], $color);
                break;
            
            case 'square':
                imagesetthickness($this->image, 0);
                $half = $this->attributes['stroke-width'] / 2;
                imagefilledrectangle($this->image, $this->attributes['x1'] - $half, $this->attributes['y1'] - $half, $this->attributes['x1'] + $half, $this->attributes['y1'] + $half, $color);
                imagefilledrectangle($this->image, $this->attributes['x2'] - $half, $this->attributes['y2'] - $half, $this->attributes['x2'] + $half, $this->attributes['y2'] + $half, $color);
                break;
        }

        //always draw line with linecap butt
        imagesetthickness($this->image, $this->attributes['stroke-width']);
        imageline(
            $this->image,
            $this->attributes['x1'],
            $this->attributes['y1'],
            $this->attributes['x2'],
            $this->attributes['y2'],
            $this->color->allocate($this->attributes['stroke'], $this->image, $this->getOpacity('stroke'))
        );
        
        if ( $this->debug ) {
            $color = $this->color->allocate('black', $this->image, false);
            imagesetpixel($this->image, $this->attributes['x1'], $this->attributes['y1'], $color);
            imagesetpixel($this->image, $this->attributes['x2'], $this->attributes['y2'], $color);
        }
    }
    
    /**
     * Polygon <polygon>
     * 
     * points - list of points "200,10 250,190 160,210" .. X and Y separated by comma, points is separated by space
     * 
     * @internal Not support style "fill-rule" http://www.w3.org/TR/SVG/painting.html#FillProperties
     */
    public function polygon()
    {
        $points = preg_split("/[, ]/", $this->attributes['points']);
        foreach ( $points AS $key => &$point ) {
            $point = (float) $point;
        }
        
        if ( !empty($this->attributes['fill']) ) {
            imagefilledpolygon(
                $this->image, $points, count($points) / 2,
                $this->color->allocate($this->attributes['fill'], $this->image, $this->getOpacity())
            );
        }
        
        if ( !empty($this->attributes['stroke']) ) {
            if ( empty($this->attributes['stroke-width']) ) {
                $this->attributes['stroke-width'] = 1;
            }
            
            imagesetthickness($this->image, $this->attributes['stroke-width']);
            imagepolygon(
                $this->image, $points, count($points) / 2,
                $this->color->allocate($this->attributes['stroke'], $this->image, $this->getOpacity('stroke'))
            );
        }
    }
    
    /**
     * Polyline <polyline> is used to create any shape that consists of only straight lines
     * points - list of points "200,10 250,190 160,210" .. X and Y separated by comma, points is separated by space
     * 
     * @todo not support style "fill"
     */
    public function polyline()
    {
        if ( empty($this->attributes['stroke-width']) ) {
            $this->attributes['stroke-width'] = 1;
        }

        imagesetthickness($this->image, $this->attributes['stroke-width']);
        $color = $this->color->allocate($this->attributes['stroke'], $this->image, $this->getOpacity('stroke'));
        
        $points = explode(' ', $this->attributes['points']);
        foreach ( $points AS $key => $once ) {
            if ( empty($points[$key + 1]) ) {
                break;
            }
            
            list($x1, $y1) = explode(',', $once, 2);
            list($x2, $y2) = explode(',', $points[$key + 1], 2);
            
            imageline($this->image, $x1, $y1, $x2, $y2, $color);
        }
    }
    
    /**
     * Path <path>
     * d - path coords .. d="M150 0 L75 200 L225 200 Z"
     *
     * M = moveto
     * L = lineto
     * H = horizontal lineto
     * V = vertical lineto
     * C = curveto
     * S = smooth curveto
     * Q = quadratic Bézier curve
     * T = smooth quadratic Bézier curveto
     * A = elliptical Arc
     * Z = closepath
     * Lower case character means relative coords
     * 
     * @link http://www.w3schools.com/svg/svg_path.asp
     * @link http://www.w3.org/TR/SVG/paths.html#PathData
     * @link https://developer.mozilla.org/en-US/docs/Web/SVG/Tutorial/Paths
     * @link https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/d
     */
    public function path()
    {
        if ( empty($this->attributes['fill']) ) {
            $this->attributes['fill'] = 'black';
        }
        $color = $this->color->allocate($this->attributes['fill'], $this->image, $this->getOpacity());
        
        //preg_match_all("/([a-zA-Z])\s*(\-?\d+\s*)*/", $attributes['d'], $matches);
        preg_match_all("/[a-zA-Z]\s*((\-?[\d\.]+)\s*,?\s*)+/", $this->attributes['d'], $matches);
        //var_dump($matches); exit;
        
        $coords = [0,0]; //hold actual coordinates for relative actions
        $points = []; //collection of points to draw
        
        foreach ( $matches[0] AS $row ) {
            $action = substr($row, 0, 1);
            preg_match_all("/\-?[\d\.]+/", $row, $numbers);
            $numbers = reset($numbers);
            
            switch ( $action ) {
                case 'M':
                    $this->drawPolygon($points, $color);
                case 'L':
                    $points[] = $numbers[0];
                    $points[] = $numbers[1];
                    $coords = [$numbers[0], $numbers[1]];
                    break;
                
                case 'm':
                    $this->drawPolygon($points, $color);
                case 'l':
                    $points[] = $coords[0] + $numbers[0];
                    $points[] = $coords[1] + $numbers[1];
                    $coords[0] += $numbers[0];
                    $coords[1] += $numbers[1];
                    break;
                
                case 'H':
                    $points[] = $numbers[0];
                    $points[] = $coords[1];
                    $coords[0] = $numbers[0];
                    break;
                case 'h':
                    $points[] = $coords[0] + $numbers[0];
                    $points[] = $coords[1];
                    $coords[0] += $numbers[0];
                    break;
                
                case 'V':
                    $points[] = $coords[0];
                    $points[] = $numbers[0];
                    $coords[1] = $numbers[0];
                    break;
                case 'v':
                    $points[] = $coords[0];
                    $points[] = $coords[1] + $numbers[0];
                    $coords[1] += $numbers[0];
                    break;
                
                case 'C':
                    $points = array_merge($points, BezierCurve::drawfilled($this->image, [$coords[0], $coords[1]], [$numbers[0], $numbers[1]], [$numbers[2], $numbers[3]], [$numbers[4], $numbers[5]], $color));
                    $coords = [$numbers[4], $numbers[5]];
                    break;
                case 'c':
                    $points = array_merge($points, BezierCurve::drawfilled($this->image, [$coords[0], $coords[1]], 
                        [$coords[0] + $numbers[0], $coords[1] + $numbers[1]], 
                        [$coords[0] + $numbers[2], $coords[1] + $numbers[3]], 
                        [$coords[0] + $numbers[4], $coords[1] + $numbers[5]], $color));
                    $coords[0] += $numbers[4];
                    $coords[1] += $numbers[5];
                    break;
                
                case 'S':
                    break;
                case 's':
                    break;
                
                case 'Q':
                    break;
                case 'q':
                    break;
                
                case 'T':
                    break;
                case 't':
                    break;
                
                case 'A':
                    break;
                case 'a':
                    break;
                
                case 'Z':
                case 'z':
                    $this->drawPolygon($points, $color);
                    break;
            }
        }
        
        //if is not closed path with "Z", render it
        $this->drawPolygon($points, $color);
    }
    
    /**
     * Draw polygon
     * 
     * @todo add support for open polygon - path without "z" at the end
     * @param array $points
     * @param Resource $color
     */
    private function drawPolygon(&$points, $color)
    {
        if ( !empty($points) ) {
            if ( count($points) == 4 ) {
                $tmpElement = clone $this->element;
                $this->element->addAttribute('x1', array_shift($points));
                $this->element->addAttribute('y1', array_shift($points));
                $this->element->addAttribute('x2', array_shift($points));
                $this->element->addAttribute('y2', array_shift($points));
                $this->setElement($this->element);
                $this->line();
                $this->setElement($tmpElement);
            } else {
                imagefilledpolygon($this->image, $points, count($points) / 2, $color);

                if ( !empty($this->attributes['stroke']) ) {
                    imagesetthickness($this->image, $this->attributes['stroke-width']);
                    imagepolygon($this->image, $points, count($points) / 2, $this->color->allocate($this->attributes['stroke'], $this->image, $this->getOpacity('stroke')));
                }
                
                if ( $this->debug ) {
                    $color = $this->color->allocate('black', $this->image, false);
                    for ($i = 0; $i < count($points); $i + 2) {
                        imagesetpixel($this->image, array_shift($points), array_shift($points), $color);
                    }
                }
            }
            
            $points = [];
        }
    }
    
    /**
     * Resolve opacity defined by style
     * 
     * @param string $key
     * @return float|boolean
     */
    private function getOpacity($key = 'fill')
    {
        $opacity = false;
        if ( isset($this->attributes[$key . '-opacity']) ) {
            $opacity = $this->attributes[$key . '-opacity'];
        } elseif ( isset($this->attributes['opacity']) ) {
            $opacity = $this->attributes['opacity'];
        }
        
        return $opacity;
    }
    
}
