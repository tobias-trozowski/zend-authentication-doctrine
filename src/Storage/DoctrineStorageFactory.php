<?php
declare(strict_types=1);

namespace Tobias\Zend\Authentication\Doctrine\Storage;

use Doctrine\Common\Persistence\ObjectManager;
use Interop\Config\ConfigurationTrait;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Psr\Container\ContainerInterface;
use Tobias\Zend\Authentication\Doctrine\Storage;

final class DoctrineStorageFactory implements RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    public function dimensions(): iterable
    {
        return ['doctrine', 'authentication'];
    }

    public function mandatoryOptions(): iterable
    {
        return [
            'object_manager', 'identity_class',
        ];
    }

    public function __invoke(ContainerInterface $container)
    {
        $options = $this->options($container->get('config'), 'orm_default');
        /** @var ObjectManager $manager */
        $manager = $container->get($options['object_manager']);

        return new DoctrineStorage(
            $container->get(Storage::class),
            $manager->getRepository($options['identity_class']),
            $manager->getClassMetadata($options['identity_class'])
        );
    }
}
