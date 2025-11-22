<?php

namespace CaRMen\Translation;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Doctrine\ORM\EntityManagerInterface;
use CaRMen\Entity\Translation;

class DBTranslationLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function load($resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);
        $translations = $this->em->getRepository(Translation::class)->findBy(['locale' => $locale]);

        foreach ($translations as $t) {
            $catalogue->set($t->getKey(), $t->getMessage(), $domain);
        }

        return $catalogue;
    }
}
