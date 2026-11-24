<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\TooManyLoginAttemptsException;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Request;
use App\Http\Response;
use App\Http\SafeRedirect;
use App\I18n\Translator;
use App\Security\SessionInterface;
use App\Service\AuthService;
use App\Validation\Rule\Email;
use App\Validation\Rule\Required;
use App\Validation\Validator;
use App\View\View;

final class AuthController extends AbstractController
{
    public function __construct(
        View $view,
        private readonly AuthService $auth,
        private readonly Validator $validator,
        private readonly Translator $translator,
        private readonly SessionInterface $session,
    ) {
        parent::__construct($view);
    }

    public function showLogin(Request $request): Response
    {
        return $this->renderForm(
            email: '',
            notice: $request->query('logged_out') === '1' ? $this->translator->trans('auth.logged_out') : null,
        );
    }

    public function login(Request $request): Response
    {
        $result = $this->validator->validate($request->all(), [
            'email' => [new Required(), new Email()],
            'password' => [new Required()],
        ]);
        $email = (string) ($result->validated()['email'] ?? '');

        if (!$result->isValid()) {
            return $this->renderForm($email, errors: $result->errors(), status: 422);
        }

        try {
            // Hasło bierzemy wprost z żądania: walidator przycina spacje, a w haśle są one znaczące.
            $this->auth->attempt($email, (string) $request->input('password'), $request->ip());
        } catch (InvalidCredentialsException) {
            return $this->renderForm($email, formError: $this->translator->trans('auth.failed'), status: 422);
        } catch (TooManyLoginAttemptsException $e) {
            return $this->renderForm($email, formError: $this->translator->trans('auth.throttled', ['minutes' => $e->retryAfterMinutes]), status: 429);
        }

        $intended = SafeRedirect::localPath($this->session->get(AuthMiddleware::INTENDED_URL_KEY));
        $this->session->remove(AuthMiddleware::INTENDED_URL_KEY);

        return $this->redirect($intended ?? '/dashboard');
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();

        return $this->redirect('/login?logged_out=1');
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderForm(
        string $email,
        array $errors = [],
        ?string $formError = null,
        ?string $notice = null,
        int $status = 200,
    ): Response {
        return $this->render('auth/login', [
            'titleKey' => 'auth.login.title',
            'email' => $email,
            'errors' => $errors,
            'formError' => $formError,
            'notice' => $notice,
        ], $status);
    }
}
