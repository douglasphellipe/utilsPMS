<?php

namespace Dotenv\Repository;

use Dotenv\Repository\Adapter\ArrayAdapter;
use InvalidArgumentException;
use ReturnTypeWillChange;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * Are we immutable?
     *
     * @var bool
     */
    private $immutable;

    /**
     * The record of loaded variables.
     *
     * @var \Dotenv\Repository\Adapter\ArrayAdapter
     */
    private $loaded;

    /**
     * Create a new repository instance.
     *
     * @param bool $immutable
     *
     * @return void
     */
    public function __construct($immutable)
    {
        $this->immutable = $immutable;
        $this->loaded = new ArrayAdapter();
    }

    /**
     * Get an environment variable.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return string|null
     */
    public function get($name)
    {
        if (!is_string($name) || '' === $name) {
            throw new InvalidArgumentException('Expected name to be a non-empty string.');
        }

        return $this->getInternal($name);
    }

    /**
     * Get an environment variable.
     *
     * @param non-empty-string $name
     *
     * @return string|null
     */
    abstract protected function getInternal($name);

    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        if (!is_string($name) || '' === $name) {
            throw new InvalidArgumentException('Expected name to be a non-empty string.');
        }

        // Don't overwrite existing environment variables if we're immutable
        // Ruby's dotenv does this with `ENV[key] ||= value`.
        if ($this->immutable && $this->get($name) !== null && $this->loaded->get($name)->isEmpty()) {
            return;
        }

        $this->setInternal($name, $value);
        $this->loaded->set($name, '');
    }

    /**
     * Set an environment variable.
     *
     * @param non-empty-string $name
     * @param string|null      $value
     *
     * @return void
     */
    abstract protected function setInternal($name, $value = null);

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function clear($name)
    {
        if (!is_string($name) || '' === $name) {
            throw new InvalidArgumentException('Expected name to be a non-empty string.');
        }

        // Don't clear anything if we're immutable.
        if ($this->immutable) {
            return;
        }

        $this->clearInternal($name);
    }

    /**
     * Clear an environment variable.
     *
     * @param non-empty-string $name
     *
     * @return void
     */
    abstract protected function clearInternal($name);

    /**
     * Tells whether environment variable has been defined.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return is_string($name) && $name !== '' && $this->get($name) !== null;
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }
}
