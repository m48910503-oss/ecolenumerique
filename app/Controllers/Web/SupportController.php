<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\StudentDashboardModel;
use App\Services\Shared\DashboardBadgeService;
use App\Services\Support\SupportGuestContextService;
use App\Services\Web\SupportService;

final class SupportController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->currentUser();
        if (!$user) {
            $guestContext = new SupportGuestContextService();
            $guest = $guestContext->ensure();
            $support = new SupportService();
            $pageData = $support->buildPageData([], $_GET);
            $initialConv = (int) ($guest['active_conversation_id'] ?? 0);
            if ($initialConv < 1) {
                $initialConv = (int) ($pageData['initialConversation'] ?? 0);
            }

            $this->render('web/support_public', [
                'basePath' => $pageData['basePath'],
                'initialConv' => $initialConv,
                'guestProfile' => [
                    'name' => (string) ($guest['guest_name'] ?? ''),
                    'email' => (string) ($guest['guest_email'] ?? ''),
                ],
                'loginUrl' => $this->siteUrl('login?return=' . urlencode($this->siteUrl('support'))),
                'siteLogoUrl' => $this->siteLogoUrl(),
            ]);
            return;
        }

        $user = $this->requireAuth(true, $this->siteUrl('support'));
        $service = new SupportService();
        $data = $service->buildPageData($user, $_GET);
        $requestedSpace = isset($_GET['space']) ? (string) $_GET['space'] : null;
        $badgeData = (new DashboardBadgeService($pdo))->build($user, $requestedSpace);
        $ticketFeed = $service->buildTicketFeed(
            $user,
            (bool) ($data['isStaff'] ?? false),
            (string) ($badgeData['space'] ?? ''),
            500
        );

        $studentCoursesCount = 0;
        if (($badgeData['space'] ?? null) === 'student') {
            try {
                $studentCoursesCount = (new StudentDashboardModel($pdo))->countActiveEnrollments((int) ($user['id'] ?? 0));
            } catch (\Throwable $e) {
                $studentCoursesCount = 0;
            }
        }

        $this->render('web/support', [
            'user' => $user,
            'isStaff' => $data['isStaff'],
            'basePath' => $data['basePath'],
            'initialConv' => $data['initialConversation'],
            'unreadCount' => (int) ($badgeData['unreadCount'] ?? 0),
            'supportUnreadCount' => (int) ($badgeData['supportUnreadCount'] ?? 0),
            'pendingMeetingsCount' => (int) ($badgeData['pendingMeetingsCount'] ?? 0),
            'dashboardSpace' => $badgeData['space'] ?? null,
            'siteLogoUrl' => $this->siteLogoUrl(),
            'supportTickets' => $ticketFeed['tickets'] ?? [],
            'supportTicketStats' => $ticketFeed['stats'] ?? ['open' => 0, 'waiting' => 0, 'resolved' => 0],
            'studentCoursesCount' => $studentCoursesCount,
        ]);
    }
}
