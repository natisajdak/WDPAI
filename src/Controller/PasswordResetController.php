<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Exception\InvalidPasswordResetTokenException;
use App\Http\Request;
use App\Http\Response;
use App\I18n\Translator;
use App\Security\SessionInterface;
use App\Service\PasswordResetService;
use App\Validation\Rule\Email;
use App\Validation\Rule\Length;
use App\Validation\Rule\Required;
use App\Validation\Validator;
use App\View\View;

final class PasswordResetController extends AbstractController
{
    /** bcrypt bierze pod uwagę najwyżej 72 bajty hasła. */
    private const PASSWORD_MIN = 10;
    private const PASSWORD_MAX = 72;

    public function __construct(
        View $view,
        private readonly PasswordResetService $passwordReset,
        private readonly Validator $validator,
        private readonly Translator $translator,
        private readonly SessionInterface $session,
    ) {
        parent::__construct($view);
    }

    public function showRequestForm(Request $request): Response
    {
        return $this->render('auth/forgot-password', ['titleKey' => 'auth.forgot.title', 'email' => '', 'errors' => []]);
    }

    public function sendLink(Request $request): Response
    {
        $result = $this->validator->validate($request->all(), ['email' => [new Required(), new Email()]]);

        if (!$result->isValid()) {
            return $this->render('auth/forgot-password', [
                'titleKey' => 'auth.forgot.title',
                'email' => (string) ($result->validated()['email'] ?? ''),
                'errors' => $result->errors(),
            ], 422);
        }

        $this->passwordReset->requestReset((string) $result->validated()['email']);
        $this->session->flash('success', $this->translator->trans('auth.forgot.sent'));

        return $this->redirect('/forgot-password');
    }

    public function showResetForm(Request $request): Response
    {
        $token = $request->query('token', '') ?? '';

        return $this->renderResetForm($token, [], $this->passwordReset->isTokenValid($token));
    }

    public function reset(Request $request): Response
    {
        $token = (string) $request->input('token', '');
        $password = (string) $request->input('password', '');

        $result = $this->validator->validate(['password' => $password], [
            'password' => [new Required(), new Length(self::PASSWORD_MIN, self::PASSWORD_MAX)],
        ]);
        $errors = $result->errors();

        if (!isset($errors['password']) && $password !== (string) $request->input('password_confirmation', '')) {
            $errors['password_confirmation'] = $this->translator->trans('validation.password_confirmation');
        }

        if ($errors !== []) {
            return $this->renderResetForm($token, $errors, true, 422);
        }

        try {
            $this->passwordReset->resetPassword($token, $password);
        } catch (InvalidPasswordResetTokenException) {
            return $this->renderResetForm($token, [], false, 422);
        }

        $this->session->flash('success', $this->translator->trans('auth.reset.done'));

        return $this->redirect('/login');
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderResetForm(string $token, array $errors, bool $tokenValid, int $status = 200): Response
    {
        return $this->render('auth/reset-password', [
            'titleKey' => 'auth.reset.title',
            'token' => $token,
            'tokenValid' => $tokenValid,
            'errors' => $errors,
            'minLength' => self::PASSWORD_MIN,
        ], $status);
    }
}
