<?php
declare(strict_types=1);

namespace Tobias\Zend\Authentication\Doctrine\Storage;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectRepository;
use Zend\Authentication\Storage\StorageInterface;

final class DoctrineStorage implements StorageInterface
{
    /** @var StorageInterface */
    private $storage;

    /** @var ObjectRepository */
    private $objectRepository;

    /** @var ClassMetadata */
    private $classMetadata;

    public function __construct(
        StorageInterface $storage,
        ObjectRepository $objectRepository,
        ClassMetadata $classMetadata
    ) {
        $this->storage = $storage;
        $this->objectRepository = $objectRepository;
        $this->classMetadata = $classMetadata;
    }

    public function isEmpty(): bool
    {
        return $this->storage->isEmpty();
    }

    /**
     * This function assumes that the storage only contains identifier values (which is the case if
     * the ObjectRepository authentication adapter is used).
     *
     * @return null|object
     */
    public function read(): ?object
    {
        if ($identity = $this->storage->read()) {
            return $this->objectRepository->find($identity);
        }
        return null;
    }

    /**
     * Will return the key of the identity. If only the key is needed, this avoids an
     * unnecessary db call
     *
     * @return mixed
     */
    public function readKeyOnly()
    {
        return $this->storage->read();
    }

    /**
     * @param object $identity
     *
     * @return void
     */
    public function write($identity): void
    {
        $identifierValues = $this->classMetadata->getIdentifierValues($identity);
        $this->storage->write($identifierValues);
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->storage->clear();
    }
}
