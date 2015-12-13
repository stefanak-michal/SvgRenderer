<?php

/**
 * Shapes - drawing by shape type
 *
 * @author Michal Stefanak
 * @todo add support for empty style "stroke" ..default is black color
 * @todo add check mandatory variables, throw exceptions
 */
class Shapes extends Service
{
    
    /**
     * Rectangle <rect>
     * 
     * width, height
     * x, y - origin position (left top is 0,0)
     * 
     * @todo add support for radius corner (rx, ry)
     * @param SimpleXMLElement $element
     * @param Resource $image Image identifier
     */
    public function rect(SimpleXMLElement $element, $image)
    {
        $attributes = $this->attributesToArray($element);
        
        if ( !isset($attributes['x']) ) {
            $attributes['x'] = 0;
        }
        if ( !isset($attributes['y']) ) {
            $attributes['y'] = 0;
        }
        
        if ( !empty($attributes['fill']) ) {
            imagefilledrectangle(
                $image,
                $attributes['x'],
                $attributes['y'],
                $attributes['x'] + $attributes['width'],
                $attributes['y'] + $attributes['height'],
                $this->color->allocate($attributes['fill'], $image, $this->getOpacity($attributes))
            );
        }
        
        if ( !empty($attributes['stroke']) ) {
            if ( empty($attributes['stroke-width']) ) {
                $attributes['stroke-width'] = 1;
            }
            
            imagesetthickness($image, $attributes['stroke-width']);
            imagerectangle(
                $image,
                $attributes['x'],
                $attributes['y'],
                $attributes['x'] + $attributes['width'],
                $attributes['y'] + $attributes['height'],
                $this->color->allocate($attributes['stroke'], $image, $this->getOpacity($attributes, 'stroke'))
            );
        }
    }
    
    /**
     * Circle <circle>
     * 
     * cx, cy - center point
     * r - radius
     * 
     * @param SimpleXMLElement $element
     * @param Resource $image Image identifier
     */
    public function circle(SimpleXMLElement $element, $image)
    {
        $this->ellipse($element, $image);
    }
    
    /**
     * Ellipse <ellipse>
     * 
     * cx, cy - center point
     * rx - horizontal radius
     * ry - vertical radius
     * 
     * @param SimpleXMLElement $element
     * @param Resource $image Image identifier
     */
    public function ellipse(SimpleXMLElement $element, $image)
    {
        $attributes = $this->attributesToArray($element);
        if ( isset($attributes['r']) ) {
            $attributes['rx'] = $attributes['ry'] = $attributes['r'];
        }
        
        if ( !empty($attributes['fill']) ) {
            imagefilledellipse(
                $image,
                $attributes['cx'],
                $attributes['cy'],
                $attributes['rx'] * 2,
                $attributes['ry'] * 2,
                $this->color->allocate($attributes['fill'], $image, $this->getOpacity($attributes))
            );
        }
        
        if ( !empty($attributes['stroke']) ) {
            if ( empty($attributes['stroke-width']) ) {
                $attributes['stroke-width'] = 1;
            }
            
            //bug in PHP doesn't allow use imagesetthickness
            for ( $i = 0; $i <= ceil($attributes['stroke-width']); $i++ ) {
                imageellipse(
                    $image,
                    $attributes['cx'],
                    $attributes['cy'],
                    ($attributes['rx'] * 2) - $i,
                    ($attributes['ry'] * 2) - $i,
                    $this->color->allocate($attributes['stroke'], $image, $this->getOpacity($attributes, 'stroke'))
                );
            }
        }
    }
    
    /**
     * Line <line>
     * 
     * x1, y1 - begin
     * x2, y2 - end
     * 
     * @param SimpleXMLElement $element
     * @param Resource $image Image identifier
     */
    public function line(SimpleXMLElement $element, $image)
    {
        $attributes = $this->attributesToArray($element);
        
        if ( empty($attributes['stroke-width']) ) {
            $attributes['stroke-width'] = 1;
        }

        imagesetthickness($image, $attributes['stroke-width']);
        imageline(
            $image,
            $attributes['x1'],
            $attributes['y1'],
            $attributes['x2'],
            $attributes['y2'],
            $this->color->allocate($attributes['stroke'], $image, $this->getOpacity($attributes, 'stroke'))
        );
    }
    
    /**
     * Polygon <polygon>
     * 
     * points - list of points "200,10 250,190 160,210" .. X and Y separated by comma, points is separated by space
     * 
     * @internal Not support style "fill-rule" http://www.w3.org/TR/SVG/painting.html#FillProperties
     * @param SimpleXMLElement $element
     * @param Resource $image Image identifier
     */
    public function polygon(SimpleXMLElement $element, $image)
    {
        $attributes = $this->attributesToArray($element);
        
        $points = preg_split("/[, ]/", $attributes['points']);
        foreach ( $points AS $key => &$point ) {
            $point = (float) $point;
        }
        
        if ( !empty($attributes['fill']) ) {
            imagefilledpolygon(
                $image, $points, count($points) / 2,
                $this->color->allocate($attributes['fill'], $image, $this->getOpacity($attributes))
            );
        }
        
        if ( !empty($attributes['stroke']) ) {
            if ( empty($attributes['stroke-width']) ) {
                $attributes['stroke-width'] = 1;
            }
            
            imagesetthickness($image, $attributes['stroke-width']);
            imagepolygon(
                $image, $points, count($points) / 2,
                $this->color->allocate($attributes['stroke'], $image, $this->getOpacity($attributes, 'stroke'))
            );
        }
    }
    
    /**
     * Polyline <polyline> is used to create any shape that consists of only straight lines
     * points - list of points "200,10 250,190 160,210" .. X and Y separated by comma, points is separated by space
     * 
     * @todo not support style "fill"
     * @param SimpleXMLElement $element
     * @param Resource $image Image identifier
     */
    public function polyline(SimpleXMLElement $element, $image)
    {
        $attributes = $this->attributesToArray($element);
        
        if ( empty($attributes['stroke-width']) ) {
            $attributes['stroke-width'] = 1;
        }

        imagesetthickness($image, $attributes['stroke-width']);
        $color = $this->color->allocate($attributes['stroke'], $image, $this->getOpacity($attributes, 'stroke'));
        
        $points = explode(' ', $attributes['points']);
        foreach ( $points AS $key => $once ) {
            if ( empty($points[$key + 1]) ) {
                break;
            }
            
            list($x1, $y1) = explode(',', $once, 2);
            list($x2, $y2) = explode(',', $points[$key + 1], 2);
            
            imageline($image, $x1, $y1, $x2, $y2, $color);
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
     * @param SimpleXMLElement $element
     * @param Resource $image Image identifier
     */
    public function path(SimpleXMLElement $element, $image)
    {
        $attributes = $this->attributesToArray($element);
        
        if ( empty($attributes['fill']) ) {
            $attributes['fill'] = 'black';
        }
        $color = $this->color->allocate($attributes['fill'], $image, $this->getOpacity($attributes));
        
        preg_match_all("/[a-zA-Z]\s*(\-?\d+\s*)*/", $attributes['d'], $matches);
        //var_dump($matches);
        
        $coords = [0,0];
        $points = [];
        
        foreach ( $matches[0] AS $row ) {
            $action = substr($row, 0, 1);
            preg_match_all("/\d+/", $row, $numbers);
            
            switch ( $action ) {
                case 'M':
                case 'L':
                    $points[] = $numbers[0][0];
                    $points[] = $numbers[0][1];
                    $coords = [$numbers[0][0], $numbers[0][1]];
                    break;
                
                case 'm':
                case 'l':
                    $points[] = $coords[0] + $numbers[0][0];
                    $points[] = $coords[1] + $numbers[0][1];
                    $coords[0] += $numbers[0][0];
                    $coords[1] += $numbers[0][1];
                    break;
                
                case 'H':
                    $points[] = $numbers[0][0];
                    $points[] = $coords[1];
                    $coords[0] = $numbers[0][0];
                    break;
                case 'h':
                    $points[] = $coords[0] + $numbers[0][0];
                    $points[] = $coords[1];
                    $coords[0] += $numbers[0][0];
                    break;
                
                case 'V':
                    $points[] = $coords[0];
                    $points[] = $numbers[0][0];
                    $coords[1] = $numbers[0][0];
                    break;
                case 'v':
                    $points[] = $coords[1];
                    $points[] = $coords[1] + $numbers[0][0];
                    $coords[1] += $numbers[0][0];
                    break;
                
                case 'C':
                    break;
                case 'c':
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
                    imagefilledpolygon($image, $points, count($points) / 2, $color);
                    
                    if ( !empty($attributes['stroke']) ) {
                        imagesetthickness($image, $attributes['stroke-width']);
                        imagepolygon($image, $points, count($points) / 2, $this->color->allocate($attributes['stroke'], $image, $this->getOpacity($attributes, 'stroke')));
                    }
                    
                    $points = [];
                    break;
            }
        }
        
        //if is not closed path with "Z", render it
        if ( !empty($points) ) {
            imagefilledpolygon($image, $points, count($points) / 2, $color);

            if ( !empty($attributes['stroke']) ) {
                imagesetthickness($image, $attributes['stroke-width']);
                imagepolygon($image, $points, count($points) / 2, $this->color->allocate($attributes['stroke'], $image, $this->getOpacity($attributes, 'stroke')));
            }
        }
    }
    
    /**
     * Resolve opacity defined by style
     * 
     * @param array $attributes
     * @param string $key
     * @return float|boolean
     */
    private function getOpacity($attributes, $key = 'fill')
    {
        $opacity = false;
        if ( isset($attributes[$key . '-opacity']) ) {
            $opacity = $attributes[$key . '-opacity'];
        } elseif ( isset($attributes['opacity']) ) {
            $opacity = $attributes['opacity'];
        }
        
        return $opacity;
    }
    
}
