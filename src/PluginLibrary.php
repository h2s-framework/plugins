<?php

namespace Siarko\Plugins;

use Siarko\Plugins\Config\Plugin\Definition\PluginDefinition;
use Siarko\Plugins\Exception\Config\Plugin\PluginDefinitionNotFoundException;

class PluginLibrary
{

    /**
     * @var string[][][]
     */
    private ?array $pluginData = null;

    /**
     * @param PluginDefinition[][] $config
     */
    public function __construct(
        private readonly array $config = []
    )
    {
    }

    /**
     * @param string $typeName
     * @return bool
     */
    public function pluginRegistered(string $typeName): bool
    {
        return class_exists($typeName) && ($this->searchExplicitDefinition($typeName) || $this->searchImplementation($typeName));
    }

    /**
     * @param string $typeName
     * @return PluginDefinition[]
     * @throws PluginDefinitionNotFoundException
     */
    public function getPluginConfig(string $typeName): array
    {
        return $this->config[ltrim($typeName, '\\')]
            ?? throw new PluginDefinitionNotFoundException("Plugin definition for type {$typeName} not found");
    }

    /**
     * Build and return sorted list of plugins for given method
     * @param string $typeName
     * @param string $methodName
     * @return string[][]
     */
    public function getPluginsForMethod(string $typeName, string $methodName): array
    {
        if($this->pluginData === null){
            $this->pluginData = $this->buildAllPluginData();
        }
        return $this->pluginData[$typeName][$methodName] ?? [];
    }

    /**
     * @param string $typeName
     * @return bool
     */
    private function searchExplicitDefinition(string $typeName): bool
    {
        return array_key_exists($typeName, $this->config);
    }

    /**
     * @param string $typeName
     * @return bool
     */
    private function searchImplementation(string $typeName): bool
    {

        if(!str_ends_with($typeName, 'Interface')){
            return false;
        }
        return false;
        // TODO Implement this
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