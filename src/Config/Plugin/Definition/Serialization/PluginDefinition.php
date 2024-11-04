<?php

namespace Siarko\Plugins\Config\Plugin\Definition\Serialization;

trait PluginDefinition
{

    /**
     * @return mixed
     */
    public function serialize(): mixed
    {
        return [
            self::KEY_PLUGIN_CLASS => $this->getPluginClass(),
            self::KEY_ENABLED => $this->isEnabled(),
            self::KEY_SORT_ORDER => $this->getSortOrder(),
            self::KEY_METHODS => $this->getMethods()
        ];
    }

    /**
     * @param array $data
     * @return static
     */
    public static function deserialize(array $data): static
    {
        $object = new static(
            $data[self::KEY_PLUGIN_CLASS],
            $data[self::KEY_ENABLED],
            $data[self::KEY_SORT_ORDER]
        );
        $object->methods = $data[self::KEY_METHODS];
        return $object;
    }
}