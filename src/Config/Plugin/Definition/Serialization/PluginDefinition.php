<?php

namespace Siarko\Plugins\Config\Plugin\Definition\Serialization;

trait PluginDefinition
{

    /*private const KEY_PLUGIN_CLASS = 'pluginClass';
    private const KEY_ENABLED = 'enabled';
    private const KEY_SORT_ORDER = 'sortOrder';
    private const KEY_METHODS = 'methods';*/

    /**
     * @return mixed
     */
    /*public function serialize(): mixed
    {
        return [
            self::KEY_PLUGIN_CLASS => $this->getPluginClass(),
            self::KEY_ENABLED => $this->isEnabled(),
            self::KEY_SORT_ORDER => $this->getSortOrder(),
            self::KEY_METHODS => $this->getMethods()
        ];
    }*/
    public function serialize(): mixed
    {
        return [
            'pluginClass' => $this->getPluginClass(),
            'enabled' => $this->isEnabled(),
            'sortOrder' => $this->getSortOrder(),
            'methods' => $this->getMethods()
        ];
    }

    /**
     * @param array $data
     * @return static
     */
    /*public static function deserialize(array $data): static
    {
        $object = new static(
            $data[self::KEY_PLUGIN_CLASS],
            $data[self::KEY_ENABLED],
            $data[self::KEY_SORT_ORDER]
        );
        $object->methods = $data[self::KEY_METHODS];
        return $object;
    }*/
    public static function deserialize(array $data): static
    {
        $object = new static(
            $data['pluginClass'],
            $data['enabled'],
            $data['sortOrder']
        );
        $object->methods = $data['methods'];
        return $object;
    }
}