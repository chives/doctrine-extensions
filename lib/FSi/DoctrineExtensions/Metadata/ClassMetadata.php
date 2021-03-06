<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Metadata;

class ClassMetadata extends AbstractClassMetadata
{
    /**
     * @var array
     */
    protected $classMetadata = [];

    /**
     * @var array
     */
    protected $propertyMetadata = [];

    /**
     * @var array
     */
    protected $methodMetadata = [];

    /**
     * @param string $index
     * @param mixed $value
     */
    public function addClassMetadata($index, $value)
    {
        $this->classMetadata[$index] = $value;
        return $this;
    }

    /**
     * @param string $index
     */
    public function hasClassMetadata($index)
    {
        return isset($this->classMetadata[$index]);
    }

    /**
     * @param string $index
     */
    public function getClassMetadata($index)
    {
        if ($this->hasClassMetadata($index)) {
            return $this->classMetadata[$index];
        }

        return false;
    }

    /**
     * Return all class metadata.
     *
     * @return array
     */
    public function getAllClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * Add new value under $index for $property. If value already exists
     * it will be overwritten.
     *
     * @param string $property
     * @param string $index
     * @param mixed $value
     * @return ClassMetadata
     */
    public function addPropertyMetadata($property, $index, $value)
    {
        if (!isset($this->propertyMetadata[$property])) {
            $this->propertyMetadata[$property] = [$index => $value];
        } else {
            $this->propertyMetadata[$property][$index] = $value;
        }

        return $this;
    }

    /**
     * @param string $property
     * @param string $index
     * @return boolean
     */
    public function hasPropertyMetadata($property, $index)
    {
        return isset($this->propertyMetadata[$property], $this->propertyMetadata[$property][$index]);
    }

    /**
     * Returns value from $index for $property
     *
     * @param string $property
     * @param string $index
     *
     * @return boolean - return value or fase if there is nothing under $index for $property
     */
    public function getPropertyMetadata($property, $index)
    {
        if ($this->hasPropertyMetadata($property, $index)) {
            return $this->propertyMetadata[$property][$index];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getAllPropertyMetadata()
    {
        return $this->propertyMetadata;
    }

    /**
     * Add new value under $index for $method. If value already exists
     * it will be overwritten.
     *
     * @param string $method
     * @param string $index
     * @param mixed $value
     * @return ClassMetadata
     */
    public function addMethodMetadata($method, $index, $value)
    {
        if (!isset($this->methodMetadata[$method])) {
            $this->methodMetadata[$method] = [$index => $value];
        } else {
            $this->methodMetadata[$method][$index] = $value;
        }

        return $this;
    }

    /**
     * Check if there is a value under $index for $property
     *
     * @param string $method
     * @param string $index
     * @return boolean
     */
    public function hasMethodMetadata($method, $index)
    {
        return isset($this->methodMetadata[$method], $this->methodMetadata[$method][$index]);
    }

    /**
     * Returns value from $index for $method
     *
     * @param string $method
     * @param string $index
     *
     * @return boolean - return value or fase if there is nothing under $index for $property
     */
    public function getMethodMetadata($method, $index)
    {
        if ($this->hasMethodMetadata($method, $index)) {
            return $this->methodMetadata[$method][$index];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getAllMethodMetadata()
    {
        return $this->methodMetadata;
    }
}
