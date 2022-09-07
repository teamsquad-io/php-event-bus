<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use PHLAK\Config\Exceptions\InvalidContextException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\EventMapGenerator;
use TeamSquad\EventBus\Domain\Exception\InvalidArguments;
use TeamSquad\EventBus\Domain\Exception\UnknownEventException;
use TeamSquad\EventBus\Domain\Listen;
use TeamSquad\EventBus\Domain\RenamedEvent;

/**
 * AutoloaderEventMapGenerator generates an event map using autoloaded classes
 * and annotations.
 *
 * @psalm-suppress UnresolvableInclude
 */
class AutoloaderEventMapGenerator implements EventMapGenerator
{
    /** @var array<string, class-string<Event>> */
    private static $eventMap;
    private string $vendorPath;
    private ?string $eventMapFilePath;
    private AutoloadConfig $config;

    /**
     * @param string $vendorFolder path to the composer vendor folder
     * @param string|null $eventMapFilePath if null, the event map will not be saved
     * @param array<string, array<string>|string> $configuration
     *
     * @throws InvalidArguments|InvalidContextException
     * @throws UnknownEventException
     *
     * @psalm-suppress MixedAssignment
     */
    public function __construct(string $vendorFolder, ?string $eventMapFilePath, array $configuration = [])
    {
        $this->config = new AutoloadConfig($configuration);
        $this->vendorPath = $vendorFolder;
        $this->eventMapFilePath = $eventMapFilePath;
        if ($this->eventMapFilePath && is_file($this->eventMapFilePath)) {
            self::$eventMap = require $this->eventMapFilePath;
        } else {
            $this->generate();
        }
    }

    /**
     * @param string $routingKey
     *
     * @throws UnknownEventException
     *
     * @return class-string<Event>
     */
    public function get(string $routingKey): string
    {
        if (!isset(self::$eventMap[$routingKey])) {
            throw new UnknownEventException(sprintf('No class defined for RoutingKey %s', $routingKey));
        }
        return self::$eventMap[$routingKey];
    }

    public function getAll(): array
    {
        return self::$eventMap;
    }

    /**
     * @throws InvalidArguments
     * @throws UnknownEventException
     *
     * @noinspection PhpRedundantVariableDocTypeInspection
     * @noinspection PhpDeprecationInspection
     */
    public function generate(): void
    {
        /** @var ClassLoader $classLoader */
        $classLoader = require $this->vendorPath . '/autoload.php';
        /** @var array<class-string<Event>, string> $classMap */
        $classMap = $classLoader->getClassMap();
        $events = [];
        $autoloadFunctions = spl_autoload_functions();
        if (!$autoloadFunctions) {
            $autoloadFunctions = [];
        }
        foreach ($autoloadFunctions as $autoloadFunction) {
            /** @psalm-suppress DeprecatedMethod */
            AnnotationRegistry::registerLoader($autoloadFunction);
        }
        $annotationReader = new AnnotationReader();
        foreach ($classMap as $class => $path) {
            if (!$this->config->isIncludedInWhiteList($class)) {
                continue;
            }
            if ($this->config->isExcludedInBlackList($class)) {
                continue;
            }

            try {
                $reflect = new ReflectionClass($class);
                if ($class !== Listen::class &&
                    $reflect->isInstantiable() &&
                    $reflect->implementsInterface(Event::class)) {
                    $eventName = $reflect->newInstanceWithoutConstructor()->eventName();
                    $events[$eventName] = $class;
                    foreach ($annotationReader->getClassAnnotations($reflect) as $annotation) {
                        if ($annotation instanceof RenamedEvent && $annotation->old !== null) {
                            $events[$annotation->old] = $class;
                        }
                    }
                }
            } catch (ReflectionException $e) {
                throw new UnknownEventException(sprintf('Class %s does not exist in path %s', $class, $path));
            }
        }
        self::$eventMap = $events;
        $this->save();
    }

    /**
     * @throws InvalidArguments
     */
    private function save(): void
    {
        if (!$this->eventMapFilePath) {
            throw new InvalidArguments('No config file defined');
        }

        $fp = fopen($this->eventMapFilePath, 'wb');
        if (!$fp) {
            throw new RuntimeException('Unable to open file ' . $this->eventMapFilePath);
        }
        $sprintf = sprintf('<?php return %s;', var_export(self::$eventMap, true));
        fwrite($fp, $sprintf);
        fclose($fp);
    }
}
