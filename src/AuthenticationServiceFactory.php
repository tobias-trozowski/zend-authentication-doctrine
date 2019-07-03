<?php
declare(strict_types=1);

namespace Tobias\Zend\Authentication\Doctrine;

use Psr\Container\ContainerInterface;
use Zend\Authentication\AuthenticationService;

final class AuthenticationServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new AuthenticationService(
            $container->get(Storage\DoctrineStorage::class),
            $container->get(Adapter\DoctrineAdapter::class)
        );
    }
}
