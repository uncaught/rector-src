<?php

declare(strict_types=1);

namespace Rector\Caching\Config;

use Nette\Utils\Strings;
use Rector\Core\Exception\ShouldNotHappenException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Inspired by https://github.com/symplify/easy-coding-standard/blob/e598ab54686e416788f28fcfe007fd08e0f371d9/packages/changed-files-detector/src/FileHashComputer.php
 */
final class FileHashComputer
{
    public function compute(string $filePath): string
    {
        $this->ensureIsYamlOrPhp($filePath);

        $containerBuilder = new ContainerBuilder();
        $fileLoader = $this->createFileLoader($filePath, $containerBuilder);

        $fileLoader->load($filePath);

        return $this->arrayToHash($containerBuilder->getDefinitions()) .
            $this->arrayToHash($containerBuilder->getParameterBag()->all());
    }

    private function ensureIsYamlOrPhp(string $filePath): void
    {
        if (Strings::match($filePath, '#\.(yml|yaml|php)$#')) {
            return;
        }

        throw new ShouldNotHappenException(sprintf(
            'Provide only YAML/PHP file, ready for Symfony Dependency Injection. "%s" given', $filePath
        ));
    }

    /**
     * @param mixed[] $array
     */
    private function arrayToHash(array $array): string
    {
        return md5(serialize($array));
    }

    private function createFileLoader(string $filePath, ContainerBuilder $containerBuilder): LoaderInterface
    {
        $fileLocator = new FileLocator([dirname($filePath)]);
        $loaderResolver = new LoaderResolver([
            new GlobFileLoader($containerBuilder, $fileLocator),
            new PhpFileLoader($containerBuilder, $fileLocator),
            new YamlFileLoader($containerBuilder, $fileLocator),
        ]);

        $loader = $loaderResolver->resolve($filePath);
        if (! $loader) {
            throw new ShouldNotHappenException();
        }

        return $loader;
    }
}
