<?php

namespace RangelReale\aliasbehavior;

use yii\base\Object;

/**
 * Class AliasAttribute
 * @property string $value
 */
class AliasAttribute extends Object
{
    /**
     * @var AliasBehavior
     */    
    public $behavior;
    
    /**
     * @var string
     */
    public $originalAttribute;    
    
    /**
     * @var string|array
     */    
    public $format = 'raw';
    
    /**
     * Format used for source. If empty, uses [[format]]
     * @var string|array
     */    
    public $sourceFormat;
    
    /**
     * Format used for output. If empty, uses [[format]]
     * @var string|array
     */    
    public $outputFormat;
    
    /**
     * @var string
     */    
    public $nullValue;
    
    /**
     * Value that was set (only if error)
     * @var string
     */    
    protected $_value;
    
    /**
     * @var string
     */    
    private $_error;
    
    public function init()
    {
        if (is_null($this->sourceFormat))
            $this->sourceFormat = $this->format;
        if (is_null($this->outputFormat))
            $this->outputFormat = $this->format;
    }
    
    function __toString()
    {
        return $this->getValue();
    }
    
    function __invoke()
    {
        return $this->getValue();
    }
    
    /**
     * @return string
     */    
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return string
     */    
    public function getValue()
    {
        if (!is_null($this->_value))
            return $this->_value;
        
        try {
            $originalValue = $this->behavior->owner->{$this->originalAttribute};
            if ($originalValue === null)
                return $this->nullValue;
            
            if (!is_null($this->behavior->sourceDataFormat))
                $originalValue = $this->behavior->sourceDataFormat->parse($originalValue, $this->sourceFormat);

            if (!is_null($this->behavior->outputDataFormat))
                return $this->behavior->outputDataFormat->format($originalValue, $this->outputFormat);
            else
                return $originalValue;
        } catch (\Exception $e) {
            return $this->nullValue;
        }        
    }
    
    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $originalValue = $value;
        
        try
        {
            if (!is_null($this->behavior->outputDataFormat))
                $value = $this->behavior->outputDataFormat->parse($value, $this->outputFormat);

            if (!is_null($this->behavior->sourceDataFormat))
                $value = $this->behavior->sourceDataFormat->format($value, $this->sourceFormat);

            $this->behavior->owner->{$this->originalAttribute} = $value;        
            
            $this->_value = null;
            $this->_error = null;
        } catch (\Exception $e) {
            $this->_value = $originalValue;
            $this->_error = $e->getMessage();
        }
    }
    
    public function reset()
    {
        $this->_value = null;
        $this->_error = null;
    }
}