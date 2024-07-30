<?php

namespace Siarko\Plugins\Config\Plugin\Definition;

class PluginDefinitionFactory extends \Siarko\Api\Factory\AbstractFactory
{
	public function create(array $data = []): \Siarko\Plugins\Config\Plugin\Definition\PluginDefinition
	{
		return parent::_create(\Siarko\Plugins\Config\Plugin\Definition\PluginDefinition::class, $data);
	}
}
