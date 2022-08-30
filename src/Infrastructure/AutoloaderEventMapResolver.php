<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\EventMapResolver;
use TeamSquad\EventBus\Domain\Exception\InvalidArguments;
use TeamSquad\EventBus\Domain\Exception\UnknownEventException;
use TeamSquad\EventBus\Domain\Listen;
use TeamSquad\EventBus\Domain\RenamedEvent;

/**
 * @psalm-suppress UnresolvableInclude
 */
class AutoloaderEventMapResolver implements EventMapResolver
{
    /** @var array<string, class-string<Event>> */
    private static $eventMap;
    private string $vendorPath;
    private ?string $configFile;

    /**
     * @param string $vendorPath
     * @param string|null $configFile
     *
     * @throws InvalidArguments
     * @psalm-suppress MixedAssignment
     */
    public function __construct(string $vendorPath, ?string $configFile)
    {
        $this->vendorPath = $vendorPath;
        $this->configFile = $configFile;
        if ($this->configFile && is_file($this->configFile)) {
            self::$eventMap = require $this->configFile;
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
     * @noinspection PhpRedundantVariableDocTypeInspection
     */
    public function generate(bool $save = false): void
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
            /**
             * @psalm-suppress DeprecatedMethod
             */
            AnnotationRegistry::registerLoader($autoloadFunction);
        }
        $annotationReader = new AnnotationReader();
        foreach ($classMap as $class => $path) {
            if ((str_contains($class, 'VtsMedia') && !str_contains($class, 'VtsMedia\\Tests\\')) ||
                (str_contains($class, 'VtsBackOffice') && !str_contains($class, 'VtsBackOffice\\Tests\\'))) {
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
                    echo "[WARN] Class {$class} not found in {$path}" . PHP_EOL;
                }
            }
        }
        self::$eventMap = $events;
        if ($save) {
            $this->save();
        }
    }

    /**
     * @throws InvalidArguments
     */
    private function save(): void
    {
        if (!$this->configFile) {
            throw new InvalidArguments('No config file defined');
        }

        $fp = fopen($this->configFile, 'wb');
        if (!$fp) {
            throw new RuntimeException('Unable to open file ' . $this->configFile);
        }
        $sprintf = sprintf('<?php return %s;', var_export(self::$eventMap, true));
        fwrite($fp, $sprintf);
        fclose($fp);
    }
}
