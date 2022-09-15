<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\Exception\FileNotFound;

use function is_array;

class AutoloadConfig
{
    public const WHITE_LIST_CONFIG_KEY = 'white_list';
    public const BLACK_LIST_CONFIG_KEY = 'black_list';

    /** @var array<string, array<string>|string> */
    private array $config;

    /**
     * @param array<string, array<string>|string> $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->config = $configuration;
    }

    public function consumerQueueExchangeListenName(): string
    {
        /** @var string $exchangeName */
        $exchangeName = $this->config['consumer_queue_listen_name'] ?? 'my_company.event.listen';
        return $exchangeName;
    }

    /**
     * @return string
     */
    public function eventBusExchangeName(): string
    {
        /** @var string $exchangeName */
        $exchangeName = $this->config['event_bus_exchange_name'] ?? 'my_company.event_bus';
        return $exchangeName;
    }

    /**
     * @throws FileNotFound
     */
    public function configurationPath(): string
    {
        /** @var string $configurationPath */
        $configurationPath = $this->config['configuration_path'] ?? null;
        if (!$configurationPath) {
            throw new FileNotFound('Configuration "configuration_path" is not set');
        }
        return $configurationPath;
    }

    public function isIncludedInWhiteList(string $className): bool
    {
        /** @var null|string|array<array-key, string> $includedClassNames */
        $includedClassNames = $this->config[self::WHITE_LIST_CONFIG_KEY] ?? [];
        if (empty($includedClassNames)) {
            return true;
        }
        return $this->isClassNameInConfig($includedClassNames, $className);
    }

    public function isIncludedInBlackList(string $className): bool
    {
        /** @var null|string|array<array-key, string> $excludedClassNames */
        $excludedClassNames = $this->config[self::BLACK_LIST_CONFIG_KEY] ?? [];
        if (empty($excludedClassNames)) {
            return false;
        }
        return $this->isClassNameInConfig($excludedClassNames, $className);
    }

    /**
     * @param string|array<array-key, string> $config
     * @param string $className
     *
     * @return bool
     */
    private function isClassNameInConfig($config, string $className): bool
    {
        if (is_array($config)) {
            foreach ($config as $includedClassName) {
                if (stripos($className, $includedClassName) !== false) {
                    return true;
                }
            }

            return false;
        }

        return stripos($className, $config) !== false;
    }
}
