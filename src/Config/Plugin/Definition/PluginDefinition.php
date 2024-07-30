<?php

namespace Siarko\Plugins\Config\Plugin\Definition;

use Siarko\Plugins\Config\Plugin\Execution\PluginExecution;
use Siarko\Plugins\Exception\Config\Plugin\PluginMethodDefinitionException;
use Siarko\Serialization\Api\Json\SerializableInterface;

class PluginDefinition implements SerializableInterface
{

    use Serialization\PluginDefinition;

    /**
     * @var string[][]
     */
    private array $methods = [];

    /**
     * @param string $pluginClass
     * @param bool $enabled
     * @param int|null $sortOrder
     * @param string[] $methods
     * @throws PluginMethodDefinitionException
     */
    public function __construct(
        private readonly string $pluginClass,
        private readonly bool $enabled,
        private readonly ?int $sortOrder = null,
        array $methods = []
    )
    {
        foreach ($methods as $method) {
            $this->addMethod($method);
        }
    }

    /**
     * @return string
     */
    public function getPluginClass(): string
    {
        return $this->pluginClass;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return int|null
     */
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    /**
     * @param string $methodName
     * @return void
     * @throws PluginMethodDefinitionException
     */
    private function addMethod(string $methodName): void
    {
        $execType = $this->getExecutionType($methodName);
        $targetMethodName = lcfirst(mb_substr($methodName, mb_strlen($execType->name)));
        if(!array_key_exists($targetMethodName, $this->methods)){
            $this->methods[$targetMethodName] = [];
        }
        $this->methods[$targetMethodName][$execType->name] = $methodName;
    }

    /**
     * @throws PluginMethodDefinitionException
     */
    private function getExecutionType(string $methodName): PluginExecution
    {
        foreach (PluginExecution::cases() as $case) {
            if(str_starts_with($methodName, mb_strtolower($case->name))) {
                return $case;
            }
        }
        throw new PluginMethodDefinitionException("Method {$methodName} does not define correct execution type");
    }
}