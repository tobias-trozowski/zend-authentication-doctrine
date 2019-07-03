<?php
declare(strict_types=1);

namespace TobiasTest\Zend\Authentication\Doctrine;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tobias\Zend\Authentication\Doctrine\Adapter\DoctrineAdapter;
use Tobias\Zend\Authentication\Doctrine\AuthenticationServiceFactory;
use Tobias\Zend\Authentication\Doctrine\Storage\DoctrineStorage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\StorageInterface;

final class AuthenticationServiceFactoryTest extends TestCase
{
    public function testCanBeCreated(): void
    {
        $or = $this->prophesize(ObjectRepository::class);
        $container = $this->prophesize(ContainerInterface::class);

        $container->get(DoctrineAdapter::class)->willReturn(
            new DoctrineAdapter($or->reveal(), 'username', 'password')
        )->shouldBeCalledOnce();

        $container->get(DoctrineStorage::class)->willReturn(
            new DoctrineStorage(
                $this->prophesize(StorageInterface::class)->reveal(),
                $or->reveal(),
                $this->prophesize(ClassMetadata::class)->reveal()
            )
        )->shouldBeCalledOnce();

        $factory = new AuthenticationServiceFactory();
        $instance = $factory->__invoke($container->reveal());

        $this->assertInstanceOf(AuthenticationService::class, $instance);
    }
}
