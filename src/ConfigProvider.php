<?php
declare(strict_types=1);

namespace Tobias\Zend\Authentication\Doctrine;

use Zend\Authentication\Storage\Session;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'invokables' => [
                Storage::class => Session::class,
            ],
            'factories' => [
                Adapter\DoctrineAdapter::class => Adapter\DoctrineAdapterFactory::class,
                Storage\DoctrineStorage::class => Storage\DoctrineStorageFactory::class,
            ],
        ];
    }
}
