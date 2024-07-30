<?php

namespace Siarko\Plugins\Config\Plugin\Instance;

class PluginInstanceProvider
{

    /**
     * @param object[] $instances
     */
    public function __construct(
        private readonly array $instances = []
    )
    {
    }

    /**
     * @param string $className
     * @return object
     */
    public function getInstance(string $className): object
    {
        return $this->instances[ltrim($className, '\\')];
    }
}