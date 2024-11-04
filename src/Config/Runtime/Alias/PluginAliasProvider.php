<?php

namespace Siarko\Plugins\Config\Runtime\Alias;

use Siarko\DependencyManager\Config\Runtime\Alias\AliasProviderInterface;
use Siarko\Plugins\Generator\Developer\Interceptor\InterceptorGenerator;
use Siarko\Plugins\PluginLibrary;

class PluginAliasProvider implements AliasProviderInterface
{

    /**
     * @param PluginLibrary $pluginLibrary
     */
    public function __construct(
        private readonly PluginLibrary $pluginLibrary
    )
    {
    }

    /**
     * @param string $className
     * @param string $foundName
     * @return string
     */
    public function getAlias(string $className, string $foundName): string
    {
        if(!$this->isInterceptor($foundName) && $this->pluginLibrary->pluginRegistered($foundName)){
            return $foundName.InterceptorGenerator::SUFFIX;
        }
        return $foundName;
    }

    /**
     * @param string $foundName
     * @return bool
     */
    private function isInterceptor(string $foundName): bool
    {
        return str_ends_with($foundName, InterceptorGenerator::SUFFIX);
    }
}