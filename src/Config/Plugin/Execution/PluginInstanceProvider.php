<?php

namespace Siarko\Plugins\Config\Plugin\Execution;

class PluginInstanceProvider
{

    /**
     * @param object[] $plugins
     */
    public function __construct(
        private readonly array $plugins = []
    )
    {
    }

    /**
     * @param string $className
     * @return object
     */
    public function get(string $className): object
    {
        return $this->plugins[trim($className, '\\')];
    }

}