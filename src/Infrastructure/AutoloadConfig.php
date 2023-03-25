<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\Exception\InvalidArguments;

use function is_array;
use function is_string;

class AutoloadConfig
{
    /** @var string List of events namespaces or paths to be included in the event bus */
    public const WHITE_LIST_CONFIG_KEY = 'white_list';
    /** @var string List of events namespaces or paths to be excluded from the event bus */
    public const BLACK_LIST_CONFIG_KEY = 'black_list';
    /** @var string Prefix to be added to the consumer that will be created by Amqp2fcgi */
    public const CONSUMER_QUEUE_LISTEN_NAME_KEY = 'consumer_queue_listen_name';
    /** @var string Exchange name */
    public const EVENT_BUS_EXCHANGE_NAME_KEY = 'event_bus_exchange_name';
    /** @var string Path to the configuration file */
    public const CONFIGURATION_PATH_KEY = 'configuration_path';

    /** @var array<string, array<string>|string> */
    private array $config;

    /**
     * @param array<string, array<string>|string> $configuration
     *
     * @psalm-param array{
     *      consumer_queue_listen_name?: string,
     *      event_bus_exchange_name?: string,
     *      configuration_path?: string,
     *      white_list?: array<string>|string,
     *      black_list?: array<string>|string
     * } $configuration
     *
     * @throws InvalidArguments
     */
    public function __construct(array $configuration)
    {
        $mandatoryKeys = [
            self::CONSUMER_QUEUE_LISTEN_NAME_KEY,
            self::EVENT_BUS_EXCHANGE_NAME_KEY,
            self::CONFIGURATION_PATH_KEY,
        ];
        foreach ($mandatoryKeys as $mandatoryKey) {
            if (!isset($configuration[$mandatoryKey])) {
                throw new InvalidArguments(sprintf("Configuration key '%s' is not set", $mandatoryKey));
            }
        }

        $this->config = $configuration;
    }

    /**
     * @param string $consumerQueueListenName
     * @param string $eventBusExchangeName
     * @param string $configurationPath
     * @param array<string> $whiteList
     * @param array<string> $blackList
     * @param array<string, array<string>|string> $extraConfiguration
     *
     * @psalm-param array{
     *      consumer_queue_listen_name?: string,
     *      event_bus_exchange_name?: string,
     *      configuration_path?: string,
     *      white_list?: array<string>|string,
     *      black_list?: array<string>|string
     * } $extraConfiguration
     *
     * @throws InvalidArguments
     *
     * @return self
     */
    public static function create(
        string $consumerQueueListenName,
        string $eventBusExchangeName,
        string $configurationPath,
        array $whiteList = [],
        array $blackList = [],
        array $extraConfiguration = []
    ): self {
        $config = [
            self::CONSUMER_QUEUE_LISTEN_NAME_KEY => $consumerQueueListenName,
            self::EVENT_BUS_EXCHANGE_NAME_KEY => $eventBusExchangeName,
            self::CONFIGURATION_PATH_KEY => $configurationPath,
        ];
        if (!empty($whiteList)) {
            $config[self::WHITE_LIST_CONFIG_KEY] = $whiteList;
        }
        if (!empty($blackList)) {
            $config[self::BLACK_LIST_CONFIG_KEY] = $blackList;
        }

        return new self(
            array_merge($config, $extraConfiguration)
        );
    }

    /**
     * Prefix to be added to the consumer that will be created by Amqp2fcgi
     *
     * @return string
     */
    public function consumerQueueExchangeListenName(): string
    {
        /** @var string $exchangeName */
        $exchangeName = $this->config[self::CONSUMER_QUEUE_LISTEN_NAME_KEY];
        return $exchangeName;
    }

    /**
     * Exchange name
     *
     * @return string
     */
    public function eventBusExchangeName(): string
    {
        /** @var string $exchangeName */
        $exchangeName = $this->config[self::EVENT_BUS_EXCHANGE_NAME_KEY] ?? 'my_company.eventBus';
        return $exchangeName;
    }

    /**
     * Path to the configuration file
     *
     * @return string
     */
    public function configurationPath(): string
    {
        /** @var string $configurationPath */
        $configurationPath = $this->config[self::CONFIGURATION_PATH_KEY];
        return $configurationPath;
    }

    /**
     * Check if the class is included in the white list
     *
     * @param string $className
     *
     * @return bool
     */
    public function isIncludedInWhiteList(string $className): bool
    {
        $includedClassNames = $this->getWhiteList();
        if (empty($includedClassNames)) {
            return true;
        }
        return $this->isClassNameInList($includedClassNames, $className);
    }

    /**
     * Check if the class is included in the black list
     *
     * @param string $className
     *
     * @return bool
     */
    public function isIncludedInBlackList(string $className): bool
    {
        $excludedClassNames = $this->getBlackList();
        if (empty($excludedClassNames)) {
            return false;
        }
        return $this->isClassNameInList($excludedClassNames, $className);
    }

    /**
     * List of events namespaces or paths to be included in the event bus
     *
     * @return array<array-key, string>
     */
    public function getWhiteList(): array
    {
        $arr = $this->config[self::WHITE_LIST_CONFIG_KEY] ?? [];
        if (is_string($arr)) {
            return [$arr];
        }

        return $arr;
    }

    /**
     * List of events namespaces or paths to be excluded from the event bus
     *
     * @return array<array-key, string>
     */
    public function getBlackList(): array
    {
        $arr = $this->config[self::BLACK_LIST_CONFIG_KEY] ?? [];
        if (is_string($arr)) {
            return [$arr];
        }

        return $arr;
    }

    public function hasWhiteList(): bool
    {
        $whiteList = $this->getWhiteList();
        return !empty($whiteList);
    }

    public function hasBlackList(): bool
    {
        $blackList = $this->getBlackList();
        return !empty($blackList);
    }

    /**
     * @return array<string, array<string>|string>
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * Check if the class is included in the given list
     *
     * @param string|array<array-key, string> $list
     * @param string $className
     *
     * @return bool
     */
    private function isClassNameInList($list, string $className): bool
    {
        if (is_array($list)) {
            foreach ($list as $includedClassName) {
                if (stripos($className, $includedClassName) !== false) {
                    return true;
                }
            }

            return false;
        }

        return stripos($className, $list) !== false;
    }
}
