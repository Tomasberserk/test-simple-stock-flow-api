<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Ports\Inbound\Authenticate;
use App\Application\Ports\Inbound\AuthResult;
use App\Application\Ports\Outbound\PasswordHasher;
use App\Application\Ports\Outbound\TokenGenerator;
use App\Application\Ports\Outbound\UserRepository;
use App\Domain\Exception\DuplicateUsernameException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Model\User;
use App\Domain\ValueObject\Role;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;

final class AuthenticationService implements Authenticate
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordHasher $passwordHasher,
        private readonly TokenGenerator $tokenGenerator
    ) {}

    public function login(string $username, string $password): AuthResult
    {
        $usr = Username::fromString($username);
        $user = $this->userRepository->findByUsername($usr);

        // CA-07.2: Dadas credenciales inválidas, rechazo que no revela si falló el usuario o la contraseña
        if ($user === null || !$this->passwordHasher->verify($password, $user->getPasswordHash())) {
            throw new InvalidCredentialsException("Credenciales inválidas");
        }

        return $this->tokenGenerator->generate($user->getId(), $user->getUsername(), $user->getRole());
    }

    /**
     * DP-04: Alta de usuario operador. La API solo puede crear vendedores ('seller'), nunca 'admin'.
     */
    public function registerSeller(string $username, string $password): AuthResult
    {
        $usr = Username::fromString($username);
        $existing = $this->userRepository->findByUsername($usr);
        if ($existing !== null) {
            throw new DuplicateUsernameException("El nombre de usuario ya se encuentra registrado");
        }

        $hash = $this->passwordHasher->hash($password);
        $user = new User(
            id: UserId::generate(),
            username: $usr,
            passwordHash: $hash,
            role: Role::seller()
        );

        $this->userRepository->save($user);

        return $this->tokenGenerator->generate($user->getId(), $user->getUsername(), $user->getRole());
    }
}
