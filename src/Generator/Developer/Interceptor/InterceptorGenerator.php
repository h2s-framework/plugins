<?php

namespace Siarko\Plugins\Generator\Developer\Interceptor;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Siarko\Plugins\Config\Plugin\Execution\PluginExecutor;
use Siarko\Utils\Code\ClassStructureProvider;
use Siarko\Utils\Code\MethodStructure;

class InterceptorGenerator implements \Siarko\DependencyManager\Generator\IGenerator
{

    private const CONSTRUCTOR_NAME = '__construct';
    private const EXECUTOR_FIELD_NAME = '__pluginExecutor';
    public const CLASS_NAME = 'Interceptor';

    public const SUFFIX = '\\'.self::CLASS_NAME;

    /**
     * @param ClassStructureProvider $classStructureProvider
     * @param array $methodBlacklist
     */
    public function __construct(
        private readonly ClassStructureProvider $classStructureProvider,
        private readonly array $methodBlacklist = []
    )
    {
    }

    /**
     * @param string $className
     * @return bool
     */
    function canGenerate(string $className): bool
    {
        return str_ends_with($className, self::SUFFIX);
    }

    /**
     * @param string $fullClassName
     * @return string
     * @throws \ReflectionException
     */
    function generate(string $fullClassName): string
    {
        $baseClassName = substr($fullClassName, 0, strrpos($fullClassName, '\\'));
        $file = new PhpFile();
        $class = $file->addClass($fullClassName);
        $class->setExtends($baseClassName);
        $this->generateClass($class, $baseClassName);
        return $file;
    }

    /**
     * @param ClassType $class
     * @param string $baseClassName
     * @return void
     * @throws \ReflectionException
     */
    private function generateClass(ClassType $class, string $baseClassName): void
    {
        $structure = $this->classStructureProvider->get($baseClassName);
        foreach ($structure->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED) as $method) {
            if(in_array($method->getName(), $this->methodBlacklist)){
                continue;
            }
            if($method->getName() === self::CONSTRUCTOR_NAME){
                $this->generateConstructor($class, $method);
                continue;
            }
            $this->generateMethod($class, $method);
        }
    }

    /**
     * @param ClassType $class
     * @param MethodStructure $method
     * @return void
     */
    private function generateConstructor(ClassType $class, MethodStructure $method): void
    {
        $executorProp = $class->addProperty(self::EXECUTOR_FIELD_NAME);
        $executorProp->setType(PluginExecutor::class);
        $executorProp->setProtected();
        $executorProp->setReadOnly();
        $constructor = $this->generateMethodStructure($class, $method);

        //add plugin executor as first parameter
        $params = $constructor->getParameters();
        $constructor->setParameters([]);
        $pluginExecParam = $constructor->addParameter(self::EXECUTOR_FIELD_NAME);
        $constructor->setParameters(array_merge([$pluginExecParam], $params, ));
        $pluginExecParam->setType(PluginExecutor::class);

        $constructor->setBody(
            '$this->__pluginExecutor = $'.self::EXECUTOR_FIELD_NAME.";\n".
            'parent::__construct('.implode(',', array_map(function($param){return '$'.$param->getName();}, $method->getParameters())).');'
        );
    }

    /**
     * @param ClassType $class
     * @param MethodStructure $method
     * @return void
     */
    private function generateMethod(ClassType $class, \Siarko\Utils\Code\MethodStructure $method): void
    {
        $newMethod = $this->generateMethodStructure($class, $method);
        $params = [];
        foreach ($method->getParameters() as $parameter) {
            $params[] = '$'.$parameter->getName();
        }

        $returnType = $method->getNativeReflection()->getReturnType();
        $returnsData = ($returnType && $returnType->getName() !== 'void');
        $params = implode(',', $params);
        $parentExecutionCode = 'function('.$params."){\n\t".($returnsData ? 'return ' : ''). 'parent::'.$method->getName()."($params);\n}";
        $pluginExecutionCode = ($returnsData ? 'return ' : '').
            '$this->__pluginExecutor->execute($this, \''.$method->getName().'\', func_get_args(),'.$parentExecutionCode.');';
        $newMethod->setBody($pluginExecutionCode);
    }

    /**
     * @param ClassType $class
     * @param MethodStructure $method
     * @return Method
     */
    private function generateMethodStructure(ClassType $class, MethodStructure $method): Method
    {
        $methodReflection = $method->getNativeReflection();
        $newMethod = $class->addMethod($method->getName());
        if($methodReflection->isPublic()){
            $newMethod->setPublic();
        }else{
            $newMethod->setProtected();
        }
        if(($returnType = $methodReflection->getReturnType())){
            $newMethod->setReturnType($returnType->getName());
        }
        foreach ($methodReflection->getParameters() as $parameter) {
            $this->generateParam($newMethod, $parameter);
        }
        $newMethod->setComment($this->getDocBlock($method));
        return $newMethod;
    }

    /**
     * @param Method $newMethod
     * @param \ReflectionParameter $parameter
     * @return void
     */
    private function generateParam(Method $newMethod, \ReflectionParameter $parameter): void
    {
        $newParam = $newMethod->addParameter($parameter->getName());
        if($parameter->isDefaultValueAvailable()){
            $newParam->setDefaultValue($parameter->getDefaultValue());
        }
        $paramType = $parameter->getType();
        if($paramType){
            $newParam->setType($paramType->getName());
        }
    }

    /**
     * @param MethodStructure $method
     * @return string
     */
    private function getDocBlock(MethodStructure $method): string
    {
        $docBlock = $method->getDocBlock();
        $result = '';
        foreach ($docBlock?->children ?? [] as $child) {
            $result .= $child."\n";
        }
        return $result;
    }
}