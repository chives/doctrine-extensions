<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use FSi\DoctrineExtensions\Mapping\Driver\DriverInterface;
use FSi\DoctrineExtensions\Mapping\Exception\RuntimeException;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;

abstract class AbstractFileDriver implements DriverInterface
{
    /**
     * @var ClassMetadataFactory
     */
    private $baseMetadataFactory;

    /**
     * @var FileLocator
     */
    private $locator;

    /**
     * {@inheritdoc}
     */
    public function setBaseMetadataFactory(ClassMetadataFactory $metadataFactory)
    {
        $this->baseMetadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseMetadataFactory()
    {
        if (!isset($this->baseMetadataFactory)) {
            throw new RuntimeException('Required base metadata factory has not been set on the file driver.');
        }
        return $this->baseMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $metadata)
    {
        if ($this->getBaseMetadataFactory()->isTransient($metadata->getClassName())) {
            return;
        }
        $this->loadExtendedClassMetadata($this->getBaseMetadataFactory()->getMetadataFor($metadata->getClassName()), $metadata);
    }

    /**
     * @param FileLocator $locator
     */
    public function setFileLocator(FileLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @throws RuntimeException
     * @return FileLocator
     */
    public function getFileLocator()
    {
        if (!isset($this->locator)) {
            throw new RuntimeException('Required file locator has not been set on the file driver.');
        }

        return $this->locator;
    }

    /**
     * Load extended class metadata based on class metadata coming from underlying
     * ORM or ODM and this driver abilities to read extended metadata.
     *
     * @param ClassMetadataInfo $baseClassMetadata
     * @param ClassMetadataInterface $extendedClassMetadata
     */
    abstract protected function loadExtendedClassMetadata(ClassMetadataInfo $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata);

    /**
     * Returns path of the file containing class matadata.
     *
     * This method shout be used in loadClassMetadata to reach metadata file.
     *
     * @param ClassMetadataInterface $metadata
     * @return FileLocator
     */
    protected function findMappingFile(ClassMetadataInterface $metadata)
    {
        return $this->getFileLocator()->findMappingFile($metadata->getClassName());
    }
}
