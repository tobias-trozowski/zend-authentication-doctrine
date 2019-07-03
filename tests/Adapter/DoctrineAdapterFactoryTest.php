<?php
declare(strict_types=1);

namespace TobiasTest\Zend\Authentication\Doctrine\Adapter;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Interop\Config\Exception\ExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tobias\Zend\Authentication\Doctrine\Adapter\DoctrineAdapterFactory;

final class DoctrineAdapterFactoryTest extends TestCase
{
    private $object;

    protected function setUp(): void
    {
        $this->object = new DoctrineAdapterFactory();
    }

    private function getConfigWithout(string ...$keys): array
    {
        $config = [
            'object_manager'      => ObjectManager::class,
            'identity_class'      => 'IdentityClass',
            'identity_property'   => 'username',
            'credential_property' => 'password',
            'credential_callable' => null,
        ];

        foreach ($keys as $key) {
            unset($config[$key]);
        }

        return [
            'doctrine' => [
                'authentication' => [
                    'orm_default' => $config,
                ],
            ],
        ];
    }

    public function invalidConfigProvider(): iterable
    {
        yield 'empty' => [[]];
        yield 'missing all' => [['doctrine' => ['authentication' => ['orm_default' => [],],],]];
        yield 'missing object_manager' => [$this->getConfigWithout('object_manager')];
        yield 'missing identity_class' => [$this->getConfigWithout('identity_class')];
        yield 'missing identity_property' => [$this->getConfigWithout('identity_property')];
        yield 'missing credential_property' => [$this->getConfigWithout('credential_property')];
    }

    /**
     * @dataProvider invalidConfigProvider
     *
     * @param array $config
     */
    public function testCreationFailsWithMissingOptions(array $config): void
    {
        $this->expectException(ExceptionInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config)->shouldBeCalledOnce();

        $this->object->__invoke($container->reveal());
    }

    public function testCreationSucceedsWithoutCallable(): void
    {
        $or = $this->prophesize(ObjectRepository::class);
        $om = $this->prophesize(ObjectManager::class);
        $om->getRepository('IdentityClass')->willReturn($or->reveal())->shouldBeCalledOnce();
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ObjectManager::class)->willReturn($om->reveal())->shouldBeCalledOnce();
        $container->get('config')->willReturn(
            $this->getConfigWithout('credential_callable')
        )->shouldBeCalledOnce();

        $instance = $this->object->__invoke($container->reveal());

        $this->assertNotNull($instance);
    }

    public function testCreationSucceedsWithCallable(): void
    {
        $or = $this->prophesize(ObjectRepository::class);
        $om = $this->prophesize(ObjectManager::class);
        $om->getRepository('IdentityClass')->willReturn($or->reveal())->shouldBeCalledOnce();
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ObjectManager::class)->willReturn($om->reveal())->shouldBeCalledOnce();
        $container->get('config')->willReturn($this->getConfigWithout(''))->shouldBeCalledOnce();

        $instance = $this->object->__invoke($container->reveal());

        $this->assertNotNull($instance);
    }
}
