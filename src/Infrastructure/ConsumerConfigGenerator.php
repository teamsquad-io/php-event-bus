<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use TeamSquad\EventBus\Domain\Command;
use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\Exception\FileNotFound;
use TeamSquad\EventBus\Domain\Listen;
use TeamSquad\EventBus\Domain\RenamedEvent;

use function count;

use function get_class;

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
    private bool $fromConsole;

    public function __construct(string $vendorFolder, AutoloadConfig $config)
    {
        $this->config = $config;
        $this->vendorFolder = $vendorFolder;

        // Detect if we are being called from a CLI context
        $this->fromConsole = PHP_SAPI === 'cli';
    }

    /**
     * @throws AnnotationException
     * @throws FileNotFound
     * @throws ReflectionException
     *
     * @return array<array-key, mixed>
     *
     * @noinspection PhpRedundantVariableDocTypeInspection
     */
    public function generate(): array
    {
        if (!is_file($this->vendorFolder . '/autoload.php')) {
            throw new FileNotFound($this->vendorFolder . '/autoload.php');
        }

        /** @var ClassLoader $classLoader */
        $classLoader = require $this->vendorFolder . '/autoload.php';
        /** @var array<class-string, string> $classMap */
        $classMap = $classLoader->getClassMap();
        if (count($classMap) < 1000) {
            throw new RuntimeException('Class map is too small, did you run composer dump-autoload -o?');
        }

        $consumers = [];
        $controllers = [];
        $routes = [];
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
                    if ($method->getNumberOfRequiredParameters() > 0 && ($this->isListen($method) || $this->isHandle($method))) {
                        $firstParameter = $this->extractEventClassFromMethod($method);
                        if (!$firstParameter) {
                            continue;
                        }

                        if ($firstParameter->isInstantiable() && ($this->isEvent($firstParameter) || $this->isCommand($firstParameter))) {
                            /** @var Event|Command $evt */
                            $evt = $firstParameter->newInstanceWithoutConstructor();
                            $routingKey = [];
                            if ($evt instanceof Event) {
                                $routingKey = [
                                    $evt->eventName(),
                                ];
                            } elseif ($evt instanceof Command) {
                                $routingKey = [
                                    $evt->commandName(),
                                ];
                            }
                            foreach ($annotationReader->getClassAnnotations($firstParameter) as $annotation) {
                                if ($annotation instanceof RenamedEvent) {
                                    $routingKey[] = $annotation->old;
                                }
                            }
                            $consumers[] = [
                                'name'        => $consumerName . '::' . $method->getName(),
                                'routing_key' => $routingKey,
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
                sprintf('Directory "%s" could not be created. Please check permissions.', $configDir)
            );
        }

        if (empty($consumers)) {
            throw new RuntimeException(
                'No consumers found. Possible because of a misconfiguration in the white/black list. ' .
                $this->getReasonBecauseNoConfigGenerated()
            );
        }

        if (empty($controllers)) {
            throw new RuntimeException(
                'No controllers found. Check that your controllers implements Consumer::class and the constructors are callable (no private __construct)'
            );
        }

        if (empty($routes)) {
            throw new RuntimeException(
                "No routes found. Check that your controllers methods start with 'listen' and have at least one parameter \$event that implements Event::class"
            );
        }

        $this->writeToFile($configDir . '/auto_controllerMap.php', $controllers);
        $this->writeToFile($configDir . '/auto_routes.php', $routes);
        $this->writeToFile($configDir . '/auto_consumerConf.php', $consumers);

        if ($this->fromConsole) {
            echo 'Generated ' . count($consumers) . ' consumers successfully' . PHP_EOL;
        }

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
        if ($positionClassName === false || $positionClassName === 0) {
            return $fullyQualifiedName;
        }

        $className = substr($fullyQualifiedName, $positionClassName);
        $positionSeparator = strpos($fullyQualifiedName, '\\');
        if ($positionSeparator === false || $positionSeparator === 0) {
            return $className;
        }

        $context = substr($fullyQualifiedName, $positionSeparator + 1);
        $length = strpos($context, '\\');
        if ($length === false || $length === 0) {
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

    /**
     * @param ReflectionMethod $method
     *
     * @return ReflectionClass<object|Event>|null
     */
    private function extractEventClassFromMethod(ReflectionMethod $method): ?ReflectionClass
    {
        // If we are in PHP 8, we can use the ReflectionNamedType::getName() method
        $parameters = $method->getParameters();
        if (count($parameters) === 0) {
            return null;
        }
        $parameter = $parameters[0];

        if (PHP_VERSION_ID >= 80000) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType) {
                return null;
            }

            $className = $type->getName();
            if (!class_exists($className)) {
                return null;
            }

            return new ReflectionClass($className);
        }

        $type = $parameter->getClass();
        if (!$type instanceof ReflectionClass) {
            return null;
        }

        return $type;
    }

    private function getReasonBecauseNoConfigGenerated(): string
    {
        $reason = '';
        if ($this->config->hasWhiteList()) {
            $reason = 'Whitelist is ' . implode(', ', $this->config->getWhiteList());
        }
        if ($this->config->hasBlackList()) {
            $reason = 'and Blacklist is ' . implode(', ', $this->config->getBlackList());
        }
        return $reason;
    }

    private function isListen(ReflectionMethod $method): bool
    {
        return strpos($method->getName(), 'listen') === 0;
    }

    private function isHandle(ReflectionMethod $method): bool
    {
        return strpos($method->getName(), 'handle') === 0;
    }

    /**
     * @param ReflectionClass<Event|object> $firstParameter
     *
     * @return bool
     */
    private function isEvent(ReflectionClass $firstParameter): bool
    {
        return $firstParameter->implementsInterface(Event::class);
    }

    /**
     * @param ReflectionClass<Command|object> $firstParameter
     *
     * @return bool
     */
    private function isCommand(ReflectionClass $firstParameter): bool
    {
        return $firstParameter->implementsInterface(Command::class);
    }
}
