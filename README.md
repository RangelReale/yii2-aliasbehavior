Alias Behavior for Yii2
=======================

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist RangelReale/yii2-aliasbehavior "*"
```

or add

```json
"RangelReale/yii2-aliasbehavior": "*"
```

to the `require` section of your composer.json.


The idea
--------

This behavior adds an attribute alias that can perform format conversion between 2
data formats.

The aliased field by default is named <attribute>_alias.


How to use
----------

In your model:

```php
/**
 * @property string posted_at This is your property that you have in the database table, it has DATETIME format
 */
class Post extends ActiveRecord
{
    // ... Some code here

    public function behaviors()
    {
        return [
            'alias' => [
                'class' => AliasBehavior::className(), // Our behavior
                'sourceDataFormat' => MongoDBDataFormat::className(),
                'outputDataFormat' => DataFormat::className(),
                'attributes' => [
                    'start_date' => [
                        'format' => 'datetime',
                    ],
                ],
            ]
        ];
    }
}
```


How is works
------------

Behavior creates "virtual" attribute named attribute_name_alias for each attribute you define in the 'attributes' section.
When you read `$yourModel->attribute_name_alias` behavior will return object with the type AliasAttribute. If
this object will be used in the string context, it will be converted to string with the magical __toString method.
And during this original value of `attribute_name` will be converted into the local representation.

When you assign value to the `$yourModel->attribute_name_alias` internally it will be assigned to `value` property
of the AliasAttribute class and converted to the your database-stored property.

You can also define individual configuration for each attribute and define it's local name, format and so on.


Credits
-------

Based on [omnilight/yii2-datetime](https://github.com/omnilight/yii2-datetime).