<?php
declare(strict_types=1);

namespace TobiasTest\Zend\Authentication\Doctrine\Adapter;

use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Tobias\Zend\Authentication\Doctrine\Adapter\DoctrineAdapter;
use TobiasTest\Zend\Authentication\Doctrine\Adapter\TestAsset\IdentityObject;
use TobiasTest\Zend\Authentication\Doctrine\Adapter\TestAsset\PublicPropertiesIdentityObject;
use Zend\Authentication\Adapter\Exception\InvalidArgumentException;
use Zend\Authentication\Adapter\Exception\RuntimeException;
use Zend\Authentication\Adapter\Exception\UnexpectedValueException;

final class DoctrineAdapterTest extends TestCase
{
    public function testWillRequireIdentityValue(): void
    {
        $this->expectException(
            RuntimeException::class
        );
        $this->expectExceptionMessage(
            'A value for the identity was not provided prior to authentication with ObjectRepository authentication '
            . 'adapter'
        );
        $adapter = new DoctrineAdapter(
            $this->createMock(ObjectRepository::class),
            '',
            ''
        );
        $adapter->setCredential('a credential');
        $adapter->authenticate();
    }

    public function testWillRequireCredentialValue(): void
    {
        $this->expectException(
            RuntimeException::class
        );
        $this->expectExceptionMessage(
            'A credential value was not provided prior to authentication with ObjectRepository authentication adapter'
        );
        $adapter = new DoctrineAdapter(
            $this->createMock(ObjectRepository::class),
            '',
            ''
        );
        $adapter->setIdentity('an identity');
        $adapter->authenticate();
    }

    public function testWillRejectInvalidCredentialCallable(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );
        $this->expectExceptionMessage(
            '"array" is not a callable'
        );
        new DoctrineAdapter(
            $this->createMock(ObjectRepository::class),
            '',
            '',
            []
        );
    }

    public function testAuthentication(): void
    {
        $entity = new IdentityObject();
        $entity->setUsername('a username');
        $entity->setPassword('a password');
        $objectRepository = $this->createMock(ObjectRepository::class);
        $method = $objectRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->with($this->equalTo(['username' => 'a username']))
            ->willReturn($entity);
        $adapter = new DoctrineAdapter(
            $objectRepository,
            'username',
            'password'
        );
        $adapter->setIdentity('a username');
        $adapter->setCredential('a password');
        $result = $adapter->authenticate();
        $this->assertTrue($result->isValid());
        $this->assertInstanceOf(
            IdentityObject::class,
            $result->getIdentity()
        );
        $method->willReturn(null);
        $result = $adapter->authenticate();
        $this->assertFalse($result->isValid());
    }

    public function testAuthenticationWithPublicProperties(): void
    {
        $entity = new PublicPropertiesIdentityObject();
        $entity->username = 'a username';
        $entity->password = 'a password';
        $objectRepository = $this->createMock(ObjectRepository::class);
        $method = $objectRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->with($this->equalTo(['username' => 'a username']))
            ->willReturn($entity);
        $adapter = new DoctrineAdapter(
            $objectRepository,
            'username',
            'password'
        );
        $adapter->setIdentity('a username');
        $adapter->setCredential('a password');
        $result = $adapter->authenticate();
        $this->assertTrue($result->isValid());
        $method->willReturn(null);
        $result = $adapter->authenticate();
        $this->assertFalse($result->isValid());
    }

    public function testWillRefuseToAuthenticateWithoutGettersOrPublicMethods(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['username' => 'a username']))
            ->willReturn(new \stdClass());
        $adapter = new DoctrineAdapter(
            $objectRepository,
            'username',
            'password'
        );
        $adapter->setIdentity('a username');
        $adapter->setCredential('a password');
        $adapter->authenticate();
    }

    public function testCanValidateWithSpecialCrypt(): void
    {
        $hash = '$2y$07$usesomesillystringforsalt$';
        $entity = new IdentityObject();
        $entity->setUsername('username');
        // Crypt password using Blowfish
        $entity->setPassword(crypt('password', $hash));
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->with($this->equalTo(['username' => 'username']))
            ->willReturn($entity);
        $adapter = new DoctrineAdapter(
            $objectRepository,
            'username',
            'password',
            static function (IdentityObject $identity, $credentialValue) use ($hash) {
                return $identity->getPassword() === crypt($credentialValue, $hash);
            }
        );
        $adapter->setIdentity('username');
        $adapter->setCredential('password');
        $result = $adapter->authenticate();
        $this->assertTrue($result->isValid());
        $adapter->setCredential('wrong password');
        $result = $adapter->authenticate();
        $this->assertFalse($result->isValid());
    }

    public function testWillRefuseToAuthenticateWhenInvalidInstanceIsFound(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['username' => 'a username']))
            ->willReturn(new \stdClass());
        $adapter = new DoctrineAdapter(
            $objectRepository,
            'username',
            'password'
        );
        $adapter->setIdentity('a username');
        $adapter->setCredential('a password');
        $adapter->authenticate();
    }

    public function testWillNotCastAuthCredentialValue(): void
    {
        $objectRepository = $this->createMock(ObjectRepository::class);
        $adapter = new DoctrineAdapter(
            $objectRepository,
            'username',
            'password'
        );
        $entity = new IdentityObject();
        $entity->setPassword(0);
        $adapter->setIdentity('a username');
        $adapter->setCredential('00000');
        $objectRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['username' => 'a username']))
            ->willReturn($entity);
        $this->assertFalse($adapter->authenticate()->isValid());
    }
}
