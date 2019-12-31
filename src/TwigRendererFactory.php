<?php

/**
 * @see       https://github.com/mezzio/mezzio-twigrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-twigrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Twig;

use ArrayObject;
use Psr\Container\ContainerInterface;
use Twig_Environment as TwigEnvironment;

/**
 * Create and return a Twig template instance.
 */
class TwigRendererFactory
{
    /**
     * @throws Exception\InvalidConfigException for invalid config service values.
     */
    public function __invoke(ContainerInterface $container) : TwigRenderer
    {
        $config      = $container->has('config') ? $container->get('config') : [];
        $config      = $this->mergeConfig($config);
        $environment = $this->getEnvironment($container);

        return new TwigRenderer($environment, isset($config['extension']) ? $config['extension'] : 'html.twig');
    }

    /**
     * Merge mezzio templating config with twig config.
     *
     * Pulls the `templates` and `twig` top-level keys from the configuration,
     * if present, and then returns the merged result, with those from the twig
     * array having precedence.
     *
     * @param array|ArrayObject $config
     * @throws Exception\InvalidConfigException if a non-array, non-ArrayObject
     *     $config is received.
     */
    private function mergeConfig($config) : array
    {
        $config = $config instanceof ArrayObject ? $config->getArrayCopy() : $config;

        if (! is_array($config)) {
            throw new Exception\InvalidConfigException(sprintf(
                'config service MUST be an array or ArrayObject; received %s',
                is_object($config) ? get_class($config) : gettype($config)
            ));
        }

        $mezzioConfig = isset($config['templates']) && is_array($config['templates'])
            ? $config['templates']
            : [];
        $twigConfig       = isset($config['twig']) && is_array($config['twig'])
            ? $config['twig']
            : [];

        return array_replace_recursive($mezzioConfig, $twigConfig);
    }

    /**
     * Retrieve and return the TwigEnvironment instance.
     *
     * If upgrading from a previous version of this package, developers will
     * not have registered the TwigEnvironment service yet; this method will
     * create it using the TwigEnvironmentFactory, but emit a deprecation
     * notice indicating the developer should update their configuration.
     *
     * If the service is registered, it is simply pulled and returned.
     */
    private function getEnvironment(ContainerInterface $container) : TwigEnvironment
    {
        if ($container->has(TwigEnvironment::class)) {
            return $container->get(TwigEnvironment::class);
        }

        \trigger_error(sprintf(
            '%s now expects you to register the factory %s for the service %s; '
            . 'please update your dependency configuration.',
            __CLASS__,
            TwigEnvironmentFactory::class,
            TwigEnvironment::class
        ), \E_USER_DEPRECATED);

        $factory = new TwigEnvironmentFactory();
        return $factory($container);
    }
}
