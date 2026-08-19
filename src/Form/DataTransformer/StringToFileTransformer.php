<?php

namespace CaRMen\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;

class StringToFileTransformer implements DataTransformerInterface {

    public function __construct(private EntityManagerInterface $entityManager) {
    }

    public function transform($file): string {
        if (null === $file) {
            return '';
        }
        dump($file);
        return $file->getId();
    }

    public function reverseTransform($fileId): ?File {
        if (!$file) {
            return null;
        }

        $file = $this->entityManager->getRepository(File::class)->find($fileId);

        if (null === $file) {
            throw new \Exception("ssdf");
        }
        return $file;
    }
}
