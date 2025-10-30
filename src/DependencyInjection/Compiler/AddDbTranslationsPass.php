<?php
namespace CaRMen\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddDbTranslationsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('translator.default') && !$container->hasDefinition('translator')) {
            return;
        }

        // choose the translator service available in your app
        $translatorId = $container->hasDefinition('translator.default') ? 'translator.default' : 'translator';
        $definition = $container->getDefinition($translatorId);

        // list locales your app supports — set this parameter in config/services.yaml
        // services.yaml: parameters: app_locales: ['en','da','de']
        $locales = $container->hasParameter('app_locales') ? $container->getParameter('app_locales') : ['%kernel.default_locale%'];

        foreach ($locales as $locale) {
            // call translator->addResource('db', null, $locale, 'messages')
            $definition->addMethodCall('addResource', ['db', null, $locale, 'messages']);
        }
    }
}
