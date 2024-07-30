<?php

namespace Siarko\Plugins\Config\Modifier;

use Siarko\ConfigFiles\Api\Modifier\ModifierInterface;
use Siarko\ConfigFiles\Api\Modifier\ModifierManagerInterface;
use Siarko\DependencyManager\Config\Init\Modifier\Builder\ArgumentInjector;
use Siarko\Files\Api\FileInterface;
use Siarko\Plugins\Config\Plugin\Definition\PluginDefinitionBuilder;
use Siarko\Plugins\Config\Plugin\Instance\PluginInstanceProvider;
use Siarko\Plugins\PluginLibrary;

class PluginDefinitionModifier implements ModifierInterface
{

    public const PLUGIN_KEY = 'plugins';

    public const LIBRARY_CONFIG_ARGUMENT = 'config';
    public const INSTANCE_PROVIDER_ARGUMENT = 'instances';

    /**
     * @param ArgumentInjector $argumentInjector
     * @param PluginDefinitionBuilder $pluginDefinitionBuilder
     */
    public function __construct(
        private readonly ArgumentInjector $argumentInjector,
        private readonly PluginDefinitionBuilder $pluginDefinitionBuilder
    )
    {
    }

    /**
     * @return array|string[][]
     */
    public function getDependencyOrder(): array
    {
        return [];
    }

    /**
     * @param ModifierManagerInterface $manager
     * @param FileInterface $file
     * @param array $config
     * @return array
     * @throws \ReflectionException
     */
    public function apply(ModifierManagerInterface $manager, FileInterface $file, array $config): array
    {
        if($pluginDefinition = $config[self::PLUGIN_KEY] ?? null) {
            $pluginDefinition = $this->createDefinition($pluginDefinition);
            $config = $this->argumentInjector->injectArgument(
                $config, PluginLibrary::class, self::LIBRARY_CONFIG_ARGUMENT, $pluginDefinition
            );
            $pluginTypes = $this->getPluginTypes($pluginDefinition);
            $config = $this->argumentInjector->injectArgument(
                $config, PluginInstanceProvider::class, self::INSTANCE_PROVIDER_ARGUMENT, $pluginTypes
            );
            unset($config[self::PLUGIN_KEY]);
        }
        return $config;
    }

    /**
     * @param array $pluginDefinition
     * @return array
     */
    protected function createDefinition(array $pluginDefinition): array
    {
        $result = [];
        foreach ($pluginDefinition as $parentClass => $pluginConfig) {
            $parentClassName = $this->trimClassName($parentClass);
            if(is_string($pluginConfig)) {
                $result[$parentClassName] = [$this->pluginDefinitionBuilder->buildDefinition($pluginConfig)];
            }else{
                $result[$parentClassName] = [];
                foreach ($pluginConfig as $index => $pluginType) {
                    $result[$parentClassName][$index] = $this->pluginDefinitionBuilder->buildDefinition($pluginType);
                }
            }
        }
        return $result;
    }

    /**
     * @param array $pluginDefinitions
     * @return array
     */
    protected function getPluginTypes(array $pluginDefinitions): array
    {
        $result = [];
        foreach ($pluginDefinitions as $pluginDefinition) {
            foreach ($pluginDefinition as $pluginType) {
                $result[$pluginType->getPluginClass()] = $pluginType->getPluginClass();
            }
        }
        return $result;
    }

    /**
     * @param string $className
     * @return string
     */
    private function trimClassName(string $className): string
    {
        return ltrim($className, '\\');
    }
}