<?php

namespace RangelReale\aliasbehavior;

use yii\base\Behavior;
use yii\helpers\ArrayHelper;
use yii\db\BaseActiveRecord;
use RangelReale\dataformat\BaseDataFormat;

/**
 * Class AliasBehavior
 */
class AliasBehavior extends Behavior
{
    /**
     * @var string
     */    
    public $namingTemplate = '{attribute}_alias';
    
    /**
     * @var BaseDataFormat|array data format for source (saved value)  
     */
    public $sourceDataFormat;
    
    /**
     * @var BaseDataFormat|array data format for output (displayed value)  
     */
    public $outputDataFormat;
    
    /**
     * @var array List of the model attributes in one of the following formats:
     * ```php
     *  [
     *      'first', // This will use default configuration and virtual attribute template
     *      'second' => 'target_second', // This will use default configuration with custom attribute template
     *      'third' => [
     *          'targetAttribute' => 'target_third', // Optional
     *          // Rest of configuration
     *      ]
     *  ]
     * ```
     */
    public $attributes = [];
    
    /**
     * @var array
     */
    public $attributeConfig = ['class' => 'RangelReale\aliasbehavior\AliasAttribute'];

    /**
     * @var bool
     */
    public $performValidation = true;    
    
    /**
     * @var AliasAttribute[]
     */
    public $attributeValues = [];
    
    public function init()
    {
        if (!is_null($this->sourceDataFormat) && (is_array($this->sourceDataFormat) || is_string($this->sourceDataFormat)))
            $this->sourceDataFormat = \Yii::createObject($this->sourceDataFormat);

        if (!is_null($this->outputDataFormat) && (is_array($this->outputDataFormat) || is_string($this->outputDataFormat)))
            $this->outputDataFormat = \Yii::createObject($this->outputDataFormat);
        
        $this->prepareAttributes();
    }
    
    protected function prepareAttributes()
    {
        foreach ($this->attributes as $key => $value) 
        {
            $config = $this->attributeConfig;
            if (is_integer($key)) {
                $originalAttribute = $value;
                $targetAttribute = $this->processTemplate($originalAttribute);
            } else {
                $originalAttribute = $key;
                if (is_string($value)) {
                    $targetAttribute = $value;
                } else {
                    $targetAttribute = ArrayHelper::remove($value, 'targetAttribute', $this->processTemplate($originalAttribute));
                    $config = array_merge($config, $value);
                }
            }
            $config['behavior'] = $this;
            $config['originalAttribute'] = $originalAttribute;
            $this->attributeValues[$targetAttribute] = $config;
        }
    }    
    
    protected function processTemplate($originalAttribute)
    {
        return strtr($this->namingTemplate, [
            '{attribute}' => $originalAttribute,
        ]);
    }    
    
    public function events()
    {
        $events = [];
        if ($this->performValidation) {
            $events[BaseActiveRecord::EVENT_BEFORE_VALIDATE] = 'onBeforeValidate';
        }
        $events[BaseActiveRecord::EVENT_AFTER_FIND] = 'onAfterFind';
        return $events;
    }
    
    /**
     * Performs validation for all the attributes
     * @param Event $event
     */
    public function onBeforeValidate($event)
    {
        foreach ($this->attributeValues as $targetAttribute => $value) {
            if ($value instanceof AliasAttribute) {
                if ($value->getError() !== null)
                    $this->owner->addError($targetAttribute, $value->getError());
            }
        }
    }    

    /**
     * Reset aliases when record changes
     * @param Event $event
     */
    public function onAfterFind($event)
    {
        foreach ($this->attributeValues as $targetAttribute => $value) {
            if ($value instanceof AliasAttribute) {
                $value->reset();
            }
        }
    }    
    
    public function canGetProperty($name, $checkVars = true)
    {
        if ($this->hasAttribute($name)) {
            return true;
        }
        return parent::canGetProperty($name, $checkVars);
    }
    
    public function hasAttribute($name)
    {
        return isset($this->attributeValues[$name]);
    }
    
    public function canSetProperty($name, $checkVars = true)
    {
        if ($this->hasAttribute($name)) {
            return true;
        }
        return parent::canSetProperty($name, $checkVars);
    }
    
    public function __get($name)
    {
        if ($this->hasAttribute($name)) {
            return $this->getAttribute($name)->getValue();
        }
        return parent::__get($name);
    }
    
    public function __set($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->getAttribute($name)->setValue($value);
            return;
        }
        parent::__set($name, $value);
    }
    
    public function getAttribute($name)
    {
        if (is_array($this->attributeValues[$name])) {
            $this->attributeValues[$name] = \Yii::createObject($this->attributeValues[$name]);
        }
        return $this->attributeValues[$name];
    }    
}