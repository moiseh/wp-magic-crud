<?php
namespace WPMC\Field;

class UrlField extends TextField
{
    /**
     * @var string
     * @required
     */
    private $target;

    public function toArray()
    {
        $arr = parent::toArray();
        $arr['target'] = $this->target;

        return $arr;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    public function formatValue($value, $item)
    {
        if ( !empty($value) ) {
            $value = sprintf('<a href="%s" target="%s">%s</a>', $value, $this->getTarget(), basename($value));
        }

        return $value;
    }
}