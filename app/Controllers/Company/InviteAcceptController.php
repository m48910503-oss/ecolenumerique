<?php
declare(strict_types=1);

namespace App\Controllers\Company;

use App\Core\Controller;
use App\Models\CompanyInviteAcceptModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedReferralModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\ReferralService;
use App\Services\Company\InviteAcceptService;

final class InviteAcceptController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $token = trim((string) ($_GET['token'] ?? ''));
        $currentUser = $this->currentUser();
        $isLoggedIn = $currentUser !== null;

        $service = new InviteAcceptService(
            $pdo,
            new CompanyInviteAcceptModel($pdo),
            new ReferralService(new SharedReferralModel($pdo)),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $state = $service->buildInitialState($token);
        $flashSuccess = (string) $this->flashGet('success', '');
        if ($flashSuccess !== '') {
            $state['successMessage'] = $flashSuccess;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $state = $service->submit($state, $currentUser, $_POST);
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && empty($state['errors'])
            && (string) ($state['successMessage'] ?? '') !== ''
        ) {
            $this->flashSet('success', (string) $state['successMessage']);
            $this->redirectSelf('company/invite_accept');
        }

        $this->render('company/invite_accept', [
            'siteLogoUrl' => $siteLogoUrl,
            'errors' => $state['errors'] ?? [],
            'successMessage' => (string) ($state['successMessage'] ?? ''),
            'invite' => $state['invite'] ?? null,
            'token' => $token,
            'isLoggedIn' => $isLoggedIn,
            'firstName' => (string) ($state['firstName'] ?? ''),
            'lastName' => (string) ($state['lastName'] ?? ''),
            'guestEmail' => (string) ($state['guestEmail'] ?? ''),
        ]);
    }
}
