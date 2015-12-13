<?php

include 'Color.php';

/**
 * Abstract Service
 *
 * @author Michal Stefanak
 */
abstract class Service
{
    
    /**
     * @var Color
     */
    protected $color;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->color = new Color();
    }
    
    /**
     * Parse node attributes to array, also parse "style"
     * 
     * @param SimpleXMLElement $simpleXMLElement
     * @return array
     */
    protected function attributesToArray(SimpleXMLElement $simpleXMLElement)
    {
        $output = [];
        
        foreach ( $simpleXMLElement->attributes() AS $key => $attribute) {
            $attribute = (string) $attribute;
            $key = strtolower($key);
            
            if ( $key == 'style' ) {
                $styles = explode(';', $attribute);
                foreach ( $styles AS $style ) {
                    if ( empty($style) || strpos($style, ':') === false ) {
                        continue;
                    }
                    
                    list($name, $value) = explode(':', $style, 2);
                    $value = trim($value);
                    
                    if ( is_numeric($value) ) {
                        $value = (float) $value;
                    } elseif ( preg_match("/^([\d\.]+)(px|cm|mm|in|pt|pc)$/", $value, $matches) ) {
                        $matches[1] = (float) $matches[1];
                        switch ( $matches[2] ) {
                            case 'cm':
                                $matches[1] *= 37.795276;
                                break;
                            case 'mm':
                                $matches[1] *= 3.7795276;
                                break;
                            case 'in':
                                $matches[1] *= 96;
                                break;
                            case 'pt':
                                $matches[1] *= 1.333;
                                break;
                            case 'pc':
                                $matches[1] *= 12;
                                break;
                        }
                        
                        $value = (float) $matches[1];
                    }
                    
                    $output[trim($name)] = $value;
                }
            } else {
                $output[$key] = is_numeric($attribute) ? (float) $attribute : (string) $attribute;
            }
        }
        
        return $output;
    }
    
}
