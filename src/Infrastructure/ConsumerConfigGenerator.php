<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\Exception\FileNotFound;
use TeamSquad\EventBus\Domain\Listen;
use TeamSquad\EventBus\Domain\RenamedEvent;

use function get_class;

use function is_array;

use const PHP_EOL;

/**
 * ConsumerConfigGenerator generates consumers, queue and exchange configuration.
 * With this generated configuration, the go consumer knows which queue to listen to and create if needed.
 *
 * @psalm-suppress UnresolvableInclude
 */
class ConsumerConfigGenerator
{
    private AutoloadConfig $config;
    private string $vendorFolder;

    public function __construct(string $vendorFolder, AutoloadConfig $config)
    {
        $this->config = $config;
        $this->vendorFolder = $vendorFolder;
    }

    /**
     * @throws FileNotFound
     * @throws ReflectionException
     * @throws AnnotationException
     *
     * @return array<array-key, mixed>
     *
     * @noinspection PhpRedundantVariableDocTypeInspection
     * @noinspection PhpDeprecationInspection
     */
    public function generate(): array
    {
        if (!is_file($this->vendorFolder . '/autoload.php')) {
            throw new FileNotFound($this->vendorFolder . '/autoload.php');
        }

        /** @var ClassLoader $classLoader */
        $classLoader = require $this->vendorFolder . '/autoload.php';
        /** @var array<class-string<Consumer>, string> $classMap */
        $classMap = $classLoader->getClassMap();
        $consumers = [];
        $controllers = [];
        $routes = [];
        $functions = spl_autoload_functions();
        if (is_array($functions)) {
            foreach ($functions as $autoloadFunction) {
                /** @psalm-suppress DeprecatedMethod */
                AnnotationRegistry::registerLoader($autoloadFunction);
            }
        }
        $annotationReader = new AnnotationReader();
        foreach ($classMap as $class => $_) {
            if (!$this->config->isIncludedInWhiteList($class)) {
                continue;
            }
            if ($this->config->isIncludedInBlackList($class)) {
                continue;
            }

            $reflectClass = new ReflectionClass($class);
            if ($class !== static::class &&
                $reflectClass->isInstantiable() &&
                $reflectClass->isSubclassOf(Consumer::class)
            ) {
                $methods = $reflectClass->getMethods(ReflectionMethod::IS_PUBLIC);
                $consumerName = get_class($reflectClass->newInstanceWithoutConstructor());
                $controller = $this->classUniqueUrl($class);
                foreach ($methods as $method) {
                    if ($method->getNumberOfRequiredParameters() > 0 && strpos($method->getName(), 'listen') === 0) {
                        $evtClass = $method->getParameters()[0]->getClass();
                        if (!$evtClass) {
                            continue;
                        }
                        if ($evtClass->implementsInterface(Event::class) && $evtClass->isInstantiable()) {
                            /** @var Event $evt */
                            $evt = $evtClass->newInstanceWithoutConstructor();
                            $rk = [$evt->eventName()];
                            foreach ($annotationReader->getClassAnnotations($evtClass) as $annotation) {
                                if ($annotation instanceof RenamedEvent) {
                                    $rk[] = $annotation->old;
                                }
                            }
                            $consumers[] = [
                                'name'        => $consumerName . '::' . $method->getName(),
                                'routing_key' => $rk,
                                'unique'      => false,
                                'url'         => $this->generateUniqueUrl($method),
                                'queue'       => $this->generateQueueName($method),
                                'exchange'    => $this->config->eventBusExchangeName(),
                                'function'    => $method->getName(),
                                'params'      => [
                                    'passive'     => false,
                                    'durable'     => false,
                                    'exclusive'   => false,
                                    'auto_delete' => false,
                                    'nowait'      => false,
                                    'args'        => [
                                        'x-expires'   => [
                                            'type' => 'int',
                                            'val'  => 300000,
                                        ],
                                        'x-ha-policy' => [
                                            'type' => 'string',
                                            'val'  => 'all',
                                        ],
                                    ],
                                ],
                            ];
                        } else {
                            echo $consumerName . '::' . $method->getName() . PHP_EOL;
                            $listenAnnotation = $annotationReader->getMethodAnnotation($method, Listen::class);
                            if (!$listenAnnotation) {
                                throw new AnnotationException(
                                    sprintf('Listening to a non Event class and missing %s Annotation', Listen::class)
                                );
                            }
                            $consumers[] = [
                                'name'        => $consumerName . '::' . $method->getName(),
                                'routing_key' => [$listenAnnotation->routingKey],
                                'url'         => $this->generateUniqueUrl($method),
                                'queue'       => $this->generateQueueName($method),
                                'unique'      => false,
                                'exchange'    => $this->config->eventBusExchangeName(),
                                'function'    => $method->getName(),
                                'params'      => [
                                    'passive'     => false,
                                    'durable'     => false,
                                    'exclusive'   => false,
                                    'auto_delete' => false,
                                    'nowait'      => false,
                                    'args'        => [
                                        'x-expires'   => [
                                            'type' => 'int',
                                            'val'  => 300000,
                                        ],
                                        'x-ha-policy' => [
                                            'type' => 'string',
                                            'val'  => 'all',
                                        ],
                                    ],
                                ],
                            ];
                        }
                        if (!isset($controllers[$controller])) {
                            $controllers[$controller] = $class;
                            $routes[] = [
                                'pattern' => '/_/' . $controller,
                                'route'   => $controller . '/index',
                            ];
                        }
                    }
                }
            }
        }
        $configDir = $this->config->configurationPath();
        if (!is_dir($configDir) && !mkdir($configDir, 0777, true) && !is_dir($configDir)) {
            throw new RuntimeException(
                sprintf('Directory "%s" was not created', $configDir)
            );
        }

        $this->writeToFile($configDir . '/auto_controllerMap.php', $controllers);
        $this->writeToFile($configDir . '/auto_routes.php', $routes);
        $this->writeToFile($configDir . '/auto_consumerConf.php', $consumers);

        return [
            'controllers' => $controllers,
            'routes'      => $routes,
            'consumers'   => $consumers,
        ];
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return string
     */
    private function generateQueueName(ReflectionMethod $method): string
    {
        return $this->config->consumerQueueExchangeListenName() . '.' . $this->generateName($method->class) . '.' . $method->getName();
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @return string
     */
    private function classUniqueUrl(string $fullyQualifiedName): string
    {
        return strtolower($this->generateName($fullyQualifiedName, '-'));
    }

    /**
     * @param string $fullyQualifiedName
     * @param string $replacer
     *
     * @return string
     */
    private function generateName(string $fullyQualifiedName, string $replacer = '.'): string
    {
        $positionClassName = strrpos($fullyQualifiedName, '\\');
        if (!$positionClassName) {
            return $fullyQualifiedName;
        }

        $className = substr($fullyQualifiedName, $positionClassName);
        $positionSeparator = strpos($fullyQualifiedName, '\\');
        if (!$positionSeparator) {
            return $className;
        }

        $context = substr($fullyQualifiedName, $positionSeparator + 1);
        $length = strpos($context, '\\');
        if (!$length) {
            return $context;
        }

        $context = substr($context, 0, $length);
        return str_replace('\\', $replacer, $context . $className);
    }

    private function generateUniqueUrl(ReflectionMethod $method): string
    {
        return '/_/' . $this->classUniqueUrl($method->class);
    }

    /**
     * @param string $controllerMapPath
     * @param array<array-key, mixed> $controllers
     *
     * @return void
     */
    private function writeToFile(string $controllerMapPath, array $controllers): void
    {
        $fp = fopen($controllerMapPath, 'wb');
        if (!$fp) {
            throw new RuntimeException(
                sprintf('File "%s" was not created', $controllerMapPath)
            );
        }

        fwrite($fp, '<?php return ' . var_export($controllers, true) . ';');
        fclose($fp);
    }
}
