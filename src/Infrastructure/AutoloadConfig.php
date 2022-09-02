<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use PHLAK\Config\Config;
use PHLAK\Config\Exceptions\InvalidContextException;

use function is_array;

class AutoloadConfig
{
    public const WHITE_LIST_CONFIG_KEY = 'white_list';
    public const BLACK_LIST_CONFIG_KEY = 'black_list';

    private Config $config;

    /**
     * @param array<string, array<string>> $configuration
     *
     * @throws InvalidContextException
     */
    public function __construct(array $configuration = [])
    {
        $this->config = new Config($configuration);
    }

    public function isIncludedInWhiteList(string $className): bool
    {
        /** @var null|string|array<array-key, string> $includedClassNames */
        $includedClassNames = $this->config->get(self::WHITE_LIST_CONFIG_KEY, []);
        return $this->isClassNameInConfig($includedClassNames, $className);
    }

    public function isExcludedInBlackList(string $className): bool
    {
        /** @var null|string|array<array-key, string> $excludedClassNames */
        $excludedClassNames = $this->config->get(self::BLACK_LIST_CONFIG_KEY, []);
        return $this->isClassNameInConfig($excludedClassNames, $className);
    }

    /**
     * @param null|string|array<array-key, string> $config
     * @param string $className
     *
     * @return bool
     */
    private function isClassNameInConfig($config, string $className): bool
    {
        if (empty($config)) {
            return false;
        }

        if (is_array($config)) {
            foreach ($config as $includedClassName) {
                if (strpos($className, $includedClassName) !== false) {
                    return true;
                }
            }

            return false;
        }

        return strpos($config, $className) !== false;
    }
}
