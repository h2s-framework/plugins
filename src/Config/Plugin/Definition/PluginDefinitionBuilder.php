<?php

namespace Siarko\Plugins\Config\Plugin\Definition;

use Siarko\Plugins\Config\Attribute\PluginMethod;
use Siarko\Plugins\Exception\Config\Plugin\Definition\PluginDefinitionErrorException;
use Siarko\Utils\Code\ClassStructureProvider;
use Siarko\Utils\Code\MethodStructure;

class PluginDefinitionBuilder
{

    private const CONFIG_KEY_CLASS = 'class';

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
        $pluginClassName = $config[PluginDefinition::KEY_PLUGIN_CLASS];
        $classStructure = $this->classStructureProvider->get($pluginClassName);
        $pluginMethods = $classStructure->getMethods(\ReflectionMethod::IS_PUBLIC);
        $pluginMethods = array_map(fn($method) => $method->getName(), array_filter($pluginMethods, function(MethodStructure $method){
            return !empty($method->getNativeReflection()->getAttributes(PluginMethod::class));
        }));
        $className = ltrim($pluginClassName, '\\');
        return $this->pluginDefinitionFactory->create([
            PluginDefinition::KEY_PLUGIN_CLASS => $className,
            PluginDefinition::KEY_ENABLED => $config[PluginDefinition::KEY_ENABLED],
            PluginDefinition::KEY_SORT_ORDER => $config[PluginDefinition::KEY_SORT_ORDER],
            PluginDefinition::KEY_METHODS => $pluginMethods
        ]);

    }

    /**
     * @param array|string $config
     * @return array
     * @throws PluginDefinitionErrorException
     */
    private function parseConfig(array|string $config): array
    {
        if(is_string($config)){
            return [
                PluginDefinition::KEY_ENABLED => true,
                PluginDefinition::KEY_SORT_ORDER => null,
                PluginDefinition::KEY_PLUGIN_CLASS => $config
            ];
        }
        return [
            PluginDefinition::KEY_ENABLED => $config[PluginDefinition::KEY_ENABLED] ?? true,
            PluginDefinition::KEY_SORT_ORDER => $config[PluginDefinition::KEY_SORT_ORDER] ?? null,
            PluginDefinition::KEY_PLUGIN_CLASS => $config[self::CONFIG_KEY_CLASS]
                ?? throw new PluginDefinitionErrorException('Plugin class not defined in plugin definition config')
        ];
    }

}