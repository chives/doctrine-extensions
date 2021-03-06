<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata as TranslatableClassMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @internal
 */
class TranslationHelper
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param PropertyAccessor|null $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     * @param object $translation
     * @param string $locale
     */
    public function copyTranslationProperties(ClassTranslationContext $context, $object, $translation, $locale)
    {
        $this->copyProperties(
            $translation,
            $object,
            array_flip($context->getAssociationMetadata()->getProperties()),
            $context->getTranslationMetadata()
        );
        $this->setObjectLocale($context->getTranslatableMetadata(), $object, $locale);
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     * @param string $defaultLocale
     */
    public function copyPropertiesToTranslation(
        ClassTranslationContext $context,
        $object,
        $defaultLocale
    ) {
        $translationAssociationMeta = $context->getAssociationMetadata();

        $locale = $this->getObjectLocale($context, $object);
        if (is_null($locale) || $locale === '') {
            $locale = $defaultLocale;
        }

        $translatableRepository = $context->getTranslatableRepository();
        $translation = $translatableRepository->getTranslation(
            $object,
            $locale,
            $translationAssociationMeta->getAssociationName()
        );

        $objectManager = $context->getObjectManager();
        if (!$objectManager->contains($translation)) {
            $objectManager->persist($translation);
        }

        $this->copyProperties(
            $object,
            $translation,
            $translationAssociationMeta->getProperties(),
            $context->getTranslationMetadata()
        );
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     */
    public function removeEmptyTranslation(ClassTranslationContext $context, $object)
    {
        if ($this->hasTranslatedProperties($context, $object)) {
            return;
        }

        $objectLocale = $this->getObjectLocale($context, $object);
        if (is_null($objectLocale) || $objectLocale === '') {
            return;
        }

        $translationAssociationMeta = $context->getAssociationMetadata();
        $associationName = $translationAssociationMeta->getAssociationName();
        $translatableRepository = $context->getTranslatableRepository();
        $translation = $translatableRepository->findTranslation($object, $objectLocale, $associationName);

        if (!isset($translation)) {
            return;
        }

        $context->getObjectManager()->remove($translation);

        $translations = $translatableRepository->getTranslations($object, $associationName);
        if ($translations->contains($translation)) {
            $translations->removeElement($translation);
        }
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     */
    public function clearTranslatableProperties(ClassTranslationContext $context, $object)
    {
        $translationMeta = $context->getTranslationMetadata();
        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($context->getAssociationMetadata()->getProperties() as $property => $translationField) {
            if ($translationMeta->isCollectionValuedAssociation($translationField)) {
                $propertyAccessor->setValue($object, $property, new ArrayCollection());
            } else {
                $propertyAccessor->setValue($object, $property, null);
            }
        }

        $this->setObjectLocale($context->getTranslatableMetadata(), $object, null);
    }

    /**
     * @param ClassTranslationContext $context
     * @param object $object
     * @return bool
     */
    public function hasTranslatedProperties(ClassTranslationContext $context, $object)
    {
        $translationMeta = $context->getTranslationMetadata();
        $properties = $context->getAssociationMetadata()->getProperties();
        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($properties as $property => $translationField) {
            $value = $propertyAccessor->getValue($object, $property);
            if ($translationMeta->isCollectionValuedAssociation($translationField)
                && count($value)
                || !$translationMeta->isCollectionValuedAssociation($translationField)
                && null !== $value
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassTranslationContext $context
     * @param $object
     * @return string
     */
    public function getObjectLocale(ClassTranslationContext $context, $object)
    {
        $localeProperty = $context->getTranslatableMetadata()->localeProperty;
        return $this->getPropertyAccessor()->getValue($object, $localeProperty);
    }

    /**
     * @param TranslatableClassMetadata $classMetadata
     * @param object $object
     * @param string $locale
     */
    private function setObjectLocale(TranslatableClassMetadata $classMetadata, $object, $locale)
    {
        $localeProperty = $classMetadata->localeProperty;
        $this->getPropertyAccessor()->setValue($object, $localeProperty, $locale);
    }

    /**
     * @param object $source
     * @param object $target
     * @param array $properties
     */
    private function copyProperties($source, $target, $properties, ClassMetadata $classMetadata)
    {
        $propertyAccessor = $this->getPropertyAccessor();
        foreach ($properties as $sourceField => $targetField) {
            if ($classMetadata->isCollectionValuedAssociation($targetField)
                && $propertyAccessor->getValue($target, $targetField) === null
            ) {
                $reflection = new \ReflectionClass($target);
                $property = $reflection->getProperty($targetField);
                $property->setAccessible(true);
                $property->setValue($target, new ArrayCollection());
                $property->setAccessible(false);
            }

            $value = $propertyAccessor->getValue($source, $sourceField);
            $propertyAccessor->setValue($target, $targetField, $value);
        }
    }

    /**
     * @return \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (!isset($this->propertyAccessor)) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
