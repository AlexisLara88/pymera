<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AccountPasswordValidationException;
use CodeIgniter\Shield\Entities\User;
use RuntimeException;

/**
 * Changes the password of the authenticated account through Shield.
 */
final class AccountPasswordService
{
    public function changePassword(
        mixed $currentPassword,
        mixed $newPassword,
        mixed $newPasswordConfirmation,
    ): void {
        if (! is_string($currentPassword) || $currentPassword === '') {
            throw new AccountPasswordValidationException('Ingresá tu contraseña actual.');
        }

        if (! is_string($newPassword) || $newPassword === '') {
            throw new AccountPasswordValidationException('Ingresá una contraseña nueva.');
        }

        if (! is_string($newPasswordConfirmation) || $newPasswordConfirmation === '') {
            throw new AccountPasswordValidationException('Confirmá la contraseña nueva.');
        }

        if (! hash_equals($newPassword, $newPasswordConfirmation)) {
            throw new AccountPasswordValidationException('Las contraseñas nuevas no coinciden.');
        }

        $userId = auth()->id();

        if (! is_int($userId) || $userId <= 0) {
            throw new AccountPasswordValidationException('La cuenta autenticada no está disponible.');
        }

        $users = auth()->getProvider();
        $user  = $users->findById($userId);

        if (! $user instanceof User) {
            throw new AccountPasswordValidationException('La cuenta autenticada no está disponible.');
        }

        $passwordHash = $user->password_hash;

        if (! is_string($passwordHash) || $passwordHash === '') {
            throw new RuntimeException('The authenticated account has no password identity.');
        }

        $passwords = service('passwords');

        if (! $passwords->verify($currentPassword, $passwordHash)) {
            throw new AccountPasswordValidationException('La contraseña actual no es correcta.');
        }

        if ($passwords->verify($newPassword, $passwordHash)) {
            throw new AccountPasswordValidationException(
                'La contraseña nueva debe ser diferente de la actual.',
            );
        }

        $this->assertValidLength($newPassword);

        $result = $passwords->check($newPassword, $user);

        if (! $result->isOK()) {
            $minimumLength = config('Auth')->minimumPasswordLength;

            throw new AccountPasswordValidationException(
                "Usá al menos {$minimumLength} caracteres y evitá contraseñas comunes o basadas en tus datos de acceso.",
            );
        }

        $user->password = $newPassword;

        if (! $users->save($user)) {
            throw new RuntimeException('Shield could not update the password identity.');
        }

        session()->regenerate(true);

        log_message('notice', 'Password changed for authenticated user {userId}.', [
            'userId' => $userId,
        ]);
    }

    private function assertValidLength(string $password): void
    {
        if (config('Auth')->hashAlgorithm === PASSWORD_BCRYPT && strlen($password) > 72) {
            throw new AccountPasswordValidationException('La contraseña nueva es demasiado larga.');
        }

        if (config('Auth')->hashAlgorithm !== PASSWORD_BCRYPT && mb_strlen($password) > 255) {
            throw new AccountPasswordValidationException('La contraseña nueva es demasiado larga.');
        }
    }
}
