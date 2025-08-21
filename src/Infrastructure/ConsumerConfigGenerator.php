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
use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\ConsumerConfig;
use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\Exception\FileNotFound;
use TeamSquad\EventBus\Domain\Listen;
use TeamSquad\EventBus\Domain\RenamedEvent;

use function array_key_exists;
use function count;
use function get_class;
use function sprintf;

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
     * @throws ReflectionException
     * @throws AnnotationException
     * @throws FileNotFound
     *
     * @return array<array-key, mixed>
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
                $userConfiguration = [];
                $amqp = 'default';
                $bus = $annotationReader->getClassAnnotation($reflectClass, Bus::class);
                if ($bus) {
                    $amqp = $bus->bus;
                }
                $methods = $reflectClass->getMethods(ReflectionMethod::IS_PUBLIC);
                $consumerName = get_class($reflectClass->newInstanceWithoutConstructor());
                $controller = $this->classUniqueUrl($class);
                foreach ($methods as $method) {
                    if ($method->getNumberOfRequiredParameters() > 0 && str_starts_with($method->getName(), 'listen')) {
                        $manual = $this->isManual($annotationReader, $method);
                        if ($manual) {
                            $routingKey = $manual->routingKey;
                            $userConfiguration['queue'] = $manual->queue;
                            $userConfiguration['exchange'] = $manual->exchange;
                            $userConfiguration['createQueue'] = $manual->createQueue;
                            $userConfiguration['workers'] = $manual->workers;
                            $userConfiguration['passive'] = $manual->passive;
                            $userConfiguration['durable'] = $manual->durable;
                            $userConfiguration['exclusive'] = $manual->exclusive;
                            $userConfiguration['autoDelete'] = $manual->autoDelete;
                            $userConfiguration['nowait'] = $manual->noWait;
                            $userConfiguration['args'] = $manual->args;
                        } else {
                            $eventClass = $this->extractEventClassFromMethod($method);
                            if (!$eventClass) {
                                continue;
                            }

                            $attributeConsumerConfig = $method->getAttributes(ConsumerConfig::class);
                            if (isset($attributeConsumerConfig[0])) {
                                $userConfiguration = $attributeConsumerConfig[0]->getArguments();
                            }
                            if ($eventClass->implementsInterface(Event::class) && $eventClass->isInstantiable()) {
                                /** @var Event $evt */
                                $evt = $eventClass->newInstanceWithoutConstructor();
                                $routingKey = [
                                    $evt->eventName(),
                                ];
                                foreach ($annotationReader->getClassAnnotations($eventClass) as $annotation) {
                                    if ($annotation instanceof RenamedEvent) {
                                        $routingKey[] = $annotation->old;
                                    }
                                }
                            } else {
                                $listenAnnotation = $annotationReader->getMethodAnnotation($method, Listen::class);
                                if (!$listenAnnotation) {
                                    throw new AnnotationException(
                                        sprintf('Listening to a non Event class and missing %s Annotation', Listen::class)
                                    );
                                }

                                $routingKey = [$listenAnnotation->routingKey];
                            }
                        }

                        $consumers[] = [
                            'amqp'         => $this->getUserConfig('amqp', $userConfiguration, $amqp),
                            'name'         => $consumerName . '::' . $method->getName(),
                            'routing_key'  => $this->getUserConfig('routingKey', $userConfiguration, $routingKey),
                            'unique'       => $this->getUserConfig('unique', $userConfiguration, false),
                            'url'          => $this->generateUniqueUrl($method),
                            'queue'        => $this->getUserConfig('queue', $userConfiguration, $this->generateQueueName($method)),
                            'exchange'     => $this->getUserConfig('exchange', $userConfiguration, $this->config->eventBusExchangeName()),
                            'function'     => $this->getUserConfig('function', $userConfiguration, $method->getName()),
                            'create_queue' => $this->getUserConfig('createQueue', $userConfiguration, true),
                            'workers'      => $this->getUserConfig('workers', $userConfiguration, 1),
                            'params'       => [
                                'passive'     => $this->getUserConfig('passive', $userConfiguration, false),
                                'durable'     => $this->getUserConfig('durable', $userConfiguration, false),
                                'exclusive'   => $this->getUserConfig('exclusive', $userConfiguration, false),
                                'auto_delete' => $this->getUserConfig('autoDelete', $userConfiguration, false),
                                'nowait'      => $this->getUserConfig('nowait', $userConfiguration, false),
                                'args'        => $this->getUserConfig('args', $userConfiguration, [
                                    'x-expires'   => [
                                        'type' => 'int',
                                        'val'  => 300000,
                                    ],
                                    'x-ha-policy' => [
                                        'type' => 'string',
                                        'val'  => 'all',
                                    ],
                                ]),
                            ],
                        ];

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

    private function isManual(AnnotationReader $annotationReader, ReflectionMethod $method): null|Manual
    {
        return $annotationReader->getMethodAnnotation($method, Manual::class);
    }

    /**
     * Get user configuration value or return default value.
     *
     * @param string $key
     * @param array<array-key, mixed> $userConfiguration
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    private function getUserConfig(string $key, array $userConfiguration, mixed $defaultValue): mixed
    {
        return array_key_exists($key, $userConfiguration) ? $userConfiguration[$key] : $defaultValue;
    }
}
