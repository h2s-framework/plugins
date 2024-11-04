<?php

namespace Siarko\Plugins\Config\Plugin\Definition\Code;

use Siarko\Utils\Code\ClassStructureProvider;

class ClassTypeMapper
{

    private array $mappedClasses = [
        ClassType::ABSTRACT_CLASS->name => [],
        ClassType::INTERFACE->name => [],
        ClassType::INSTANTIABLE_CLASS->name => []
    ];

    /**
     * @param ClassStructureProvider $classStructureProvider
     */
    public function __construct(
        private readonly ClassStructureProvider $classStructureProvider
    )
    {
    }

    /**
     * @param array $classTypes
     * @return void
     */
    public function map(array $classTypes): void
    {
        foreach ($classTypes as $className) {
            try{
                $structure = $this->classStructureProvider->get($className);
                $index = (
                $structure->isInterface() ? ClassType::INTERFACE :
                    ($structure->isAbstract() ? ClassType::ABSTRACT_CLASS : ClassType::INSTANTIABLE_CLASS)
                );
                $this->mappedClasses[$index->name][] = $className;
            }catch (\ReflectionException $e){
                //TODO log
            }
        }
    }

    /**
     * @param string $typeName
     * @return array
     */
    public function getImplementations(string $typeName): array
    {
        $interfaces = array_keys(class_implements($typeName));
        return array_intersect($interfaces, $this->mappedClasses[ClassType::INTERFACE->name]);
    }

    /**
     * @param string $typeName
     * @return array
     */
    public function getExtends(string $typeName): array
    {
        $parent = get_parent_class($typeName);
        if($parent === false){
            return [];
        }
        return in_array($parent, $this->mappedClasses[ClassType::ABSTRACT_CLASS->name]) ? [$parent] : [];
    }

}