<?php

namespace Siarko\Plugins\Config\Plugin\Execution;

use Siarko\Plugins\Config\Plugin\Instance\PluginInstanceProvider;
use Siarko\Plugins\Exception\Config\Plugin\PluginDefinitionNotFoundException;
use Siarko\Plugins\PluginLibrary;

class PluginExecutor
{

    /**
     * @param PluginLibrary $pluginLibrary
     * @param PluginInstanceProvider $instanceProvider
     */
    public function __construct(
        private readonly PluginLibrary $pluginLibrary,
        private readonly PluginInstanceProvider $instanceProvider
    )
    {
    }

    /**
     * @param object $interceptor
     * @param string $methodName
     * @param array $arguments
     * @param callable $callback
     * @return mixed
     * @throws PluginDefinitionNotFoundException
     */
    public function execute(object $interceptor, string $methodName, array $arguments, callable $callback): mixed
    {
        $interceptedClass = get_parent_class($interceptor);
        $pluginConfigs = $this->pluginLibrary->getPluginsForMethod($interceptedClass, $methodName);
        if($pluginConfigs){
            return $this->callPlugins($pluginConfigs, $interceptor, $methodName, $arguments, $callback);
        }
        return $callback(...$arguments);
    }

    /**
     * @param array $pluginConfigs
     * @param object $interceptor
     * @param string $methodName
     * @param array $arguments
     * @param callable $callback
     * @return mixed
     */
    private function callPlugins(
        array $pluginConfigs,
        object $interceptor,
        string $methodName,
        array $arguments,
        callable $callback
    ): mixed
    {
        $nextPluginConfig = $this->shiftPlugin($pluginConfigs);
        if(!$nextPluginConfig){ //no more plugins available -> call parent method
            return $callback(...$arguments);
        }

        $pluginInstance = $this->instanceProvider->getInstance(key($nextPluginConfig));
        $executionTypes = current($nextPluginConfig);
        $callbackArguments = func_get_args();

        //Call before plugin method
        if($beforeMethod = $executionTypes[PluginExecution::BEFORE->name] ?? false) {
            if(is_array($newArguments = $pluginInstance->$beforeMethod($interceptor, ...$arguments))){
                $arguments = $newArguments;
            }
        }

        //call around plugin method - and construct callback for next plugin/parent method
        if($aroundMethod = $executionTypes[PluginExecution::AROUND->name] ?? false) {
            $aroundCallback = function() use ($callbackArguments) {
                return $this->callPlugins(...$callbackArguments);
            };
            $result = $pluginInstance->$aroundMethod($interceptor, $aroundCallback, ...$arguments);
        }else{
            $result = $this->callPlugins(...$callbackArguments);
        }

        //call after plugin method
        if($afterMethod = $executionTypes[PluginExecution::AFTER->name] ?? false) {
            $result = $pluginInstance->$afterMethod($interceptor, $result, ...$arguments);
        }
        return $result;
    }

    /**
     * @param array $pluginConfigs
     * @return array|false
     */
    private function shiftPlugin(array &$pluginConfigs): array|false
    {
        if($pluginConfigs){
            return array_splice($pluginConfigs, 0, 1);
        }
        return false;
    }
}