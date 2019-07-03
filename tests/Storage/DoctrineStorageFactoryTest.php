<?php
declare(strict_types=1);

namespace TobiasTest\Zend\Authentication\Doctrine\Storage;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Interop\Config\Exception\ExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tobias\Zend\Authentication\Doctrine\Storage;
use Tobias\Zend\Authentication\Doctrine\Storage\DoctrineStorageFactory;
use Zend\Authentication\Storage\StorageInterface;

final class DoctrineStorageFactoryTest extends TestCase
{
    private $object;

    protected function setUp(): void
    {
        $this->object = new DoctrineStorageFactory();
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

    public function testCreateSucceeds(): void
    {
        $config = [
            'doctrine' => [
                'authentication' => [
                    'orm_default' => [
                        'object_manager' => ObjectManager::class,
                        'identity_class' => 'IdentityClass',
                    ],
                ],
            ]
            ,];
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config)->shouldBeCalledOnce();

        $em = $this->prophesize(ObjectManager::class);
        $em->getRepository('IdentityClass')->willReturn(
            $this->prophesize(ObjectRepository::class)->reveal()
        )->shouldBeCalledOnce();
        $em->getClassMetadata('IdentityClass')->willReturn(
            $this->prophesize(ClassMetadata::class)->reveal()
        )->shouldBeCalledOnce();
        $container->get(ObjectManager::class)->willReturn($em->reveal())->shouldBeCalledOnce();
        $container->get(Storage::class)->willReturn(
            $this->prophesize(StorageInterface::class)->reveal()
        )->shouldBeCalledOnce();

        $this->object->__invoke($container->reveal());
    }
}
