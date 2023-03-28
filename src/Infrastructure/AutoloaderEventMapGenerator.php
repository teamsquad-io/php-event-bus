<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use TeamSquad\EventBus\Domain\Command;
use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\EventMapGenerator;
use TeamSquad\EventBus\Domain\Exception\InvalidArguments;
use TeamSquad\EventBus\Domain\Exception\UnknownEventException;
use TeamSquad\EventBus\Domain\Listen;
use TeamSquad\EventBus\Domain\RenamedEvent;

use function dirname;
use function gettype;
use function is_array;

/**
 * AutoloaderEventMapGenerator generates an event map using autoloaded classes
 * and annotations.
 *
 * @psalm-suppress UnresolvableInclude
 */
class AutoloaderEventMapGenerator implements EventMapGenerator
{
    /** @var array<string, class-string<Event|Command>> */
    private static array $eventMap;
    private string $vendorPath;
    private ?string $eventMapFilePath;
    private AutoloadConfig $config;

    /**
     * @param string $vendorFolder path to the composer vendor folder
     * @param string|null $eventMapFilePath if null, the event map will not be saved
     * @param AutoloadConfig|array<string, array<string>|string> $configuration
     *
     * @psalm-param AutoloadConfig|array{
     *      consumer_queue_listen_name?: string,
     *      event_bus_exchange_name?: string,
     *      configuration_path?: string,
     *      white_list?: array<string>|string,
     *      black_list?: array<string>|string
     * } $configuration
     *
     * @throws UnknownEventException
     * @throws InvalidArguments
     */
    public function __construct(string $vendorFolder, ?string $eventMapFilePath, $configuration)
    {
        if (is_array($configuration)) {
            $this->config = new AutoloadConfig($configuration);
        } elseif ($configuration instanceof AutoloadConfig) {
            $this->config = $configuration;
        } else {
            throw new InvalidArguments(sprintf('Invalid configuration type %s. Expected array or %s', gettype($configuration), AutoloadConfig::class));
        }

        $this->vendorPath = $vendorFolder;
        $this->eventMapFilePath = $eventMapFilePath;
        if (!$this->loadEventMapFile($this->eventMapFilePath)) {
            $this->generate();
        }
    }

    /**
     * @throws InvalidArguments
     * @throws UnknownEventException
     */
    public static function createAutomatically(AutoloadConfig $config): self
    {
        // Find vendor folder automatically
        $vendorDirPath = dirname(__DIR__, 2);
        while (!file_exists($vendorDirPath . '/vendor/autoload.php')) {
            /** @psalm-suppress TypeDoesNotContainType */
            if ($vendorDirPath === '/') {
                throw new RuntimeException('Could not find vendor folder');
            }
            chdir('..');
            $vendorDirPath = dirname(__DIR__, 2);
        }
        chdir(__DIR__);
        return new self(
            $vendorDirPath . '/vendor',
            $vendorDirPath . '/eventMapFile.php',
            $config
        );
    }

    /**
     * @param string $routingKey
     *
     * @throws UnknownEventException
     *
     * @return class-string<Event|Command>
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
     */
    public function generate(): void
    {
        /** @var ClassLoader $classLoader */
        $classLoader = require $this->vendorPath . '/autoload.php';
        /** @var array<class-string<Event|Command>, string> $classMap */
        $classMap = $classLoader->getClassMap();
        $events = [];
        $annotationReader = new AnnotationReader();
        foreach ($classMap as $class => $path) {
            if (!$this->config->isIncludedInWhiteList($class)) {
                continue;
            }
            if ($this->config->isIncludedInBlackList($class)) {
                continue;
            }

            try {
                $reflect = new ReflectionClass($class);
                if ($class !== Listen::class &&
                    $reflect->isInstantiable() &&
                    ($reflect->implementsInterface(Event::class) || $reflect->implementsInterface(Command::class))) {
                    /** @var Command|Event $event */
                    $event = $reflect->newInstanceWithoutConstructor();
                    $eventName = $event->eventName();
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

        if (empty($events)) {
            throw new InvalidArguments(
                sprintf(
                    'No events found with whitelist "%s" and blacklist "%s"',
                    implode(', ', $this->config->getWhiteList()),
                    implode(', ', $this->config->getBlackList())
                )
            );
        }

        self::$eventMap = $events;
        $this->save();
    }

    /**
     * @throws InvalidArguments
     */
    private function save(): void
    {
        if ($this->eventMapFilePath === null || $this->eventMapFilePath === '') {
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

    /**
     * @throws InvalidArguments
     *
     * @psalm-suppress MixedAssignment
     */
    private function loadEventMapFile(?string $eventMapFilePath): bool
    {
        if ($eventMapFilePath === null || $eventMapFilePath === '') {
            return false;
        }

        // Check if the directory where the event map file should be saved exists
        $eventMapDirectory = dirname($eventMapFilePath);
        if (is_dir($eventMapDirectory)) {
            if (is_file($eventMapFilePath)) {
                self::$eventMap = require $eventMapFilePath;
                return true;
            }

            return false;
        }

        throw new InvalidArguments(
            sprintf(
                'The directory where the event map file should be saved does not exist: %s',
                $eventMapDirectory
            )
        );
    }
}
