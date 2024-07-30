<?php

namespace Siarko\Plugins\Config\Plugin\Definition;

use Siarko\Plugins\Config\Attribute\PluginMethod;
use Siarko\Plugins\Exception\Config\Plugin\PluginDefinitionErrorException;
use Siarko\Utils\Code\ClassStructureProvider;
use Siarko\Utils\Code\MethodStructure;

class PluginDefinitionBuilder
{

    private const KEY_PLUGIN_CLASS = 'class';
    private const KEY_ENABLED = 'enabled';
    private const KEY_SORT_ORDER = 'sortOrder';

    /**
     * @param ClassStructureProvider $classStructureProvider
     * @param PluginDefinitionFactory $pluginDefinitionFactory
     */
    public function __construct(
        private readonly ClassStructureProvider $classStructureProvider,
        private readonly PluginDefinitionFactory $pluginDefinitionFactory,
    )
    {
    }

    /**
     * @param string|array $config
     * @return PluginDefinition
     * @throws \ReflectionException
     */
    public function buildDefinition(string|array $config): PluginDefinition
    {
        $config = $this->parseConfig($config);
        $pluginClassName = $config[self::KEY_PLUGIN_CLASS];
        $classStructure = $this->classStructureProvider->get($pluginClassName);
        $pluginMethods = $classStructure->getMethods(\ReflectionMethod::IS_PUBLIC);
        $pluginMethods = array_map(fn($method) => $method->getName(), array_filter($pluginMethods, function(MethodStructure $method){
            return !empty($method->getNativeReflection()->getAttributes(PluginMethod::class));
        }));
        $className = ltrim($pluginClassName, '\\');
        return $this->pluginDefinitionFactory->create([
            'pluginClass' => $className,
            'enabled' => $config[self::KEY_ENABLED],
            'sortOrder' => $config[self::KEY_SORT_ORDER],
            'methods' => $pluginMethods
        ]);

    }

    /**
     * @param array|string $config
     * @return array
     */
    private function parseConfig(array|string $config): array
    {
        if(is_string($config)){
            return [
                self::KEY_ENABLED => true,
                self::KEY_SORT_ORDER => null,
                self::KEY_PLUGIN_CLASS => $config
            ];
        }
        return [
            self::KEY_ENABLED => $config[self::KEY_ENABLED] ?? true,
            self::KEY_SORT_ORDER => $config[self::KEY_SORT_ORDER] ?? null,
            self::KEY_PLUGIN_CLASS => $config[self::KEY_PLUGIN_CLASS]
                ?? throw new PluginDefinitionErrorException('Plugin class not defined in plugin definition config')
        ];
    }

}