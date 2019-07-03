<?php
declare(strict_types=1);

namespace Tobias\Zend\Authentication\Doctrine\Adapter;

use Doctrine\Common\Persistence\ObjectManager;
use Interop\Config\ConfigurationTrait;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Psr\Container\ContainerInterface;

final class DoctrineAdapterFactory implements RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    public function dimensions(): iterable
    {
        return ['doctrine', 'authentication'];
    }

    public function mandatoryOptions(): iterable
    {
        return [
            'object_manager', 'identity_class', 'identity_property', 'credential_property',
        ];
    }

    public function __invoke(ContainerInterface $container): DoctrineAdapter
    {
        $options = $this->options($container->get('config'), 'orm_default');
        /** @var ObjectManager $manager */
        $manager = $container->get($options['object_manager']);

        return new DoctrineAdapter(
            $manager->getRepository($options['identity_class']),
            $options['identity_property'],
            $options['credential_property'],
            $options['credential_callable'] ?? null
        );
    }
}
