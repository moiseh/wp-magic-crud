<?php
namespace WPMC;

use Exception;
use JsonMapper;
use WPMC\Field\BelongsToField;
use WPMC\Field\BooleanField;
use WPMC\Field\CheckboxMultiField;
use WPMC\Field\DateField;
use WPMC\Field\DatetimeField;
use WPMC\Field\DecimalField;
use WPMC\Field\DurationField;
use WPMC\Field\EmailField;
use WPMC\Field\ExternalImageField;
use WPMC\Field\FloatField;
use WPMC\Field\HasManyField;
use WPMC\Field\IntegerField;
use WPMC\Field\JsonField;
use WPMC\Field\LocalFileField;
use WPMC\Field\OneToManyField;
use WPMC\Field\SelectField;
use WPMC\Field\TextAreaField;
use WPMC\Field\TextField;
use WPMC\Field\UrlField;
use WPMC\Field\VirtualField;

class FieldResolver
{
    private $name;
    private $field = [];

    public function __construct($name, $field = [])
    {
        $this->name = $name;
        $this->field = $field;
    }

    public function getField(): FieldBase
    {
        $name = $this->name;
        $field = $this->field;

        if ( empty($field['type'])) {
            throw new Exception('Field type not defined for: ' . $name);
        }

        $field['name'] = $name;

        switch($field['type']) {
            case 'text': $obj = new TextField($field); break;
            case 'textarea': $obj = new TextAreaField($field); break;
            case 'json': $obj = new JsonField($field); break;
            case 'integer': $obj = new IntegerField($field); break;
            case 'float': $obj = new FloatField($field); break;
            case 'decimal': $obj = new DecimalField($field); break;
            case 'email': $obj = new EmailField($field); break;
            case 'checkbox_multi': $obj = new CheckboxMultiField($field); break;
            case 'select': $obj = new SelectField($field); break;
            case 'url': $obj = new UrlField($field); break;
            case 'local_file': $obj = new LocalFileField($field); break;
            case 'external_image': $obj = new ExternalImageField($field); break;
            case 'duration': $obj = new DurationField($field); break;
            case 'date': $obj = new DateField($field); break;
            case 'datetime': $obj = new DatetimeField($field); break;
            case 'boolean': $obj = new BooleanField($field); break;
            case 'belongs_to': $obj = new BelongsToField($field); break;
            case 'has_many': $obj = new HasManyField($field); break;
            case 'one_to_many': $obj = new OneToManyField($field); break;
            case 'virtual': $obj = new VirtualField($field); break;
            default: throw new Exception('Unknown field type: ' . $field['type']);
        }

        $jm = new JsonMapper();
        $jm->bExceptionOnMissingData = true;
        $jm->bExceptionOnUndefinedProperty = true;
        $jm->bEnforceMapType = false;
        $jm->map($field, $obj);

        return $obj;
    }

    public static function buildField($field): FieldBase
    {
        if ( empty($field['name'])) {
            throw new Exception('Field name not defined');
        }

        $resolver = new FieldResolver($field['name'], $field);

        return $resolver->getField();
    }
}