<?php

namespace Siarko\Plugins;

use Siarko\Plugins\Config\Plugin\Definition\Code\ClassTypeMapper;
use Siarko\Plugins\Config\Plugin\Definition\PluginDefinition;

class PluginLibrary
{

    /**
     * @var string[][][]
     */
    private ?array $pluginData = null;
    private array $definitions = [];

    /**
     * @param PluginDefinition[][] $config
     */
    public function __construct(
        private readonly ClassTypeMapper $classTypeMapper,
        private readonly array $config = []
    )
    {
        $this->classTypeMapper->map(array_keys($config));
    }

    /**
     * @param string $aliasedType
     * @return bool
     */
    public function pluginRegistered(string $aliasedType): bool
    {
        if(empty($this->config)){ return false; }
        return $this->pluginsExists($aliasedType);
    }

    /**
     * Build and return sorted list of plugins for given method
     * @param string $typeName
     * @param string $methodName
     * @return string[][]
     */
    public function getPluginsForMethod(string $typeName, string $methodName): array
    {
        if($this->definitions[$typeName][$methodName] ?? false){
            return $this->definitions[$typeName][$methodName];
        }
        if($this->definitions[$typeName] ?? false){
            $this->definitions[$typeName] = [];
        }
        return ($this->definitions[$typeName][$methodName] = $this->getDefinitionsForType($typeName, $methodName));
    }

    /**
     * @param string $typeName
     * @param string $methodName
     * @return array
     */
    private function getDefinitionsForType(string $typeName, string $methodName): array {
        if($this->pluginData === null){
            $this->pluginData = $this->buildAllPluginData();
        }
        $implementations = array_merge(
            [$typeName],
            $this->classTypeMapper->getImplementations($typeName),
            $this->classTypeMapper->getExtends($typeName)
        );
        $definitions = [];
        foreach ($implementations as $implementation) {
            $definitions = array_merge($definitions, $this->pluginData[$implementation][$methodName] ?? []);
        }
        return $definitions;
    }

    /**
     * @param string $typeName
     * @return bool
     */
    private function pluginsExists(string $typeName): bool
    {
        if(array_key_exists($typeName, $this->config)){ return true; }
        if(!class_exists($typeName)){ return false;}
        $implementation = $this->classTypeMapper->getImplementations($typeName);
        return !empty($implementation) || !empty($this->classTypeMapper->getExtends($typeName));
    }


    /**
     * @return string[][][]
     */
    private function buildAllPluginData(): array
    {
        $result = [];
        foreach ($this->config as $parentClass => $pluginConfigs) {
            $pluginConfigs = $this->sortPlugins($pluginConfigs);
            $result[$parentClass] = $this->buildPluginData($pluginConfigs);
        }
        return $result;
    }

    /**
     * @param PluginDefinition[] $pluginConfigs
     * @return array
     */
    private function buildPluginData(array $pluginConfigs): array
    {
        $result = [];
        foreach ($pluginConfigs as $pluginConfig) {
            if(!$pluginConfig->isEnabled() || empty($pluginConfig->getMethods())){ continue; }
            foreach ($pluginConfig->getMethods() as $methodName => $executionTypes) {
                $pluginClass = $pluginConfig->getPluginClass();
                if(!array_key_exists($methodName, $result)){ $result[$methodName] = []; }
                foreach ($executionTypes as $executionType => $pluginMethod) {
                    $result[$methodName][$pluginClass][$executionType] = $pluginMethod;
                }
            }
        }
        return $result;
    }

    /**
     * @param array $plugins
     * @return array
     */
    private function sortPlugins(array $plugins): array
    {
        usort($plugins, function(PluginDefinition $a, PluginDefinition $b) {
            return ($a->getSortOrder() ?? $b->getSortOrder() + 1) <=> ($b->getSortOrder() ?? $a->getSortOrder()+1);
        });
        return $plugins;
    }

}