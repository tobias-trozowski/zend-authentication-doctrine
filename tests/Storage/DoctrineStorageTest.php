<?php
declare(strict_types=1);

namespace TobiasTest\Zend\Authentication\Doctrine\Storage;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Tobias\Zend\Authentication\Doctrine\Storage\DoctrineStorage;
use TobiasTest\Zend\Authentication\Doctrine\Adapter\TestAsset\IdentityObject;
use Zend\Authentication\Storage\NonPersistent as NonPersistentStorage;
use Zend\Authentication\Storage\StorageInterface;

final class DoctrineStorageTest extends TestCase
{
    public function testCanRetrieveEntityFromObjectRepositoryStorage(): void
    {
        // Identifier is considered to be username here
        $entity = new IdentityObject();
        $entity->setUsername('a username');
        $entity->setPassword('a password');
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo('a username'))
            ->willReturn($entity);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->equalTo($entity))
            ->willReturn($entity->getUsername());
        $storage = new DoctrineStorage(
            new NonPersistentStorage(),
            $objectRepository,
            $metadata
        );
        $storage->write($entity);
        $this->assertFalse($storage->isEmpty());
        $result = $storage->read();
        $this->assertEquals($entity, $result);
        $key = $storage->readKeyOnly();
        $this->assertEquals('a username', $key);
    }

    public function testStorageIsCleared(): void
    {
        $storage = $this->prophesize(StorageInterface::class);
        $storage->clear()->shouldBeCalledOnce();
        $object = new DoctrineStorage(
            $storage->reveal(),
            $this->prophesize(ObjectRepository::class)->reveal(),
            $this->prophesize(ClassMetadata::class)->reveal()
        );
        $object->clear();
    }

    public function testReadReturnsNullIfIdentityDoesNotExist(): void
    {
        $storage = $this->prophesize(StorageInterface::class);
        $storage->read()->willReturn(null)->shouldBeCalledOnce();
        $object = new DoctrineStorage(
            $storage->reveal(),
            $this->prophesize(ObjectRepository::class)->reveal(),
            $this->prophesize(ClassMetadata::class)->reveal()
        );
        $this->assertNull($object->read());
    }
}
