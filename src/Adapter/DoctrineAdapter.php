<?php
declare(strict_types=1);

namespace Tobias\Zend\Authentication\Doctrine\Adapter;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Persistence\ObjectRepository;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Adapter\Exception;
use Zend\Authentication\Adapter\Exception\InvalidArgumentException;
use Zend\Authentication\Result;
use function get_class;
use function gettype;
use function is_callable;
use function is_string;
use function method_exists;
use function property_exists;
use function sprintf;

final class DoctrineAdapter extends AbstractAdapter
{
    /** @var ObjectRepository */
    private $objectRepository;

    /** @var string */
    private $identityProperty;

    /** @var string */
    private $credentialProperty;

    /** @var callable|string|null */
    private $credentialCallable;

    /**
     * DoctrineAdapter constructor.
     *
     * @param ObjectRepository     $objectRepository
     * @param string               $identityProperty
     * @param string               $credentialProperty
     * @param callable|string|null $credentialCallable
     */
    public function __construct(
        ObjectRepository $objectRepository,
        string $identityProperty,
        string $credentialProperty,
        $credentialCallable = null
    ) {
        $this->objectRepository = $objectRepository;
        $this->identityProperty = $identityProperty;
        $this->credentialProperty = $credentialProperty;
        if ($credentialCallable !== null && !is_callable($credentialCallable)) {
            throw new InvalidArgumentException(
                sprintf(
                    '"%s" is not a callable',
                    is_string($credentialCallable) ? $credentialCallable : gettype($credentialCallable)
                )
            );
        }
        $this->credentialCallable = $credentialCallable;
    }

    /**
     * Performs an authentication attempt
     *
     * @return Result
     * @throws Exception\ExceptionInterface If authentication cannot be performed
     */
    public function authenticate(): Result
    {
        if ($this->identity === null || empty($this->identity)) {
            throw new Exception\RuntimeException(
                'A value for the identity was not provided prior to authentication with ObjectRepository '
                . 'authentication adapter'
            );
        }

        if ($this->credential === null || empty($this->credential)) {
            throw new Exception\RuntimeException(
                'A credential value was not provided prior to authentication with ObjectRepository'
                . ' authentication adapter'
            );
        }

        $entity = $this->objectRepository->findOneBy([$this->identityProperty => $this->identity]);
        if ($entity === null) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                $this->identity,
                ['A record with the supplied identity could not be found.']
            );
        }

        return $this->validateIdentity($entity);
    }

    /**
     * This method attempts to validate that the record in the resultset is indeed a
     * record that matched the identity provided to this adapter.
     *
     * @param object $identity
     *
     * @return Result
     * @throws Exception\UnexpectedValueException
     */
    protected function validateIdentity(object $identity): Result
    {
        $credentialProperty = $this->credentialProperty;
        $getter = 'get' . Inflector::classify($credentialProperty);
        $documentCredential = null;
        if (method_exists($identity, $getter)) {
            $documentCredential = $identity->$getter();
        } elseif (property_exists($identity, $credentialProperty)) {
            $documentCredential = $identity->{$credentialProperty};
        } else {
            throw new Exception\UnexpectedValueException(
                sprintf(
                    'Property (%s) in (%s) is not accessible. You should implement %s::%s()',
                    $credentialProperty,
                    get_class($identity),
                    get_class($identity),
                    $getter
                )
            );
        }

        $credentialValue = $this->credential;
        if ($this->credentialCallable) {
            $credentialValue = call_user_func($this->credentialCallable, $identity, $credentialValue);
        }

        if ($credentialValue !== true && $credentialValue !== $documentCredential) {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->identity, ['Supplied credential is invalid.']);
        }

        return new Result(Result::SUCCESS, $identity, ['Authentication successful.']);
    }
}
