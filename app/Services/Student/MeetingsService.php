<?php
declare(strict_types=1);

namespace App\Services\Student;

use App\Core\Service;
use App\Models\MeetingsModel;

final class MeetingsService extends Service
{
    private const MEETINGS_PER_PAGE = 8;

    private MeetingsModel $meetings;

    public function __construct(MeetingsModel $meetings)
    {
        $this->meetings = $meetings;
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildPageData(int $userId, array $query = []): array
    {
        $unreadCount = 0;
        try {
            $unreadCount = $this->meetings->countUnreadNotifications($userId);
        } catch (\Throwable $e) {
            $unreadCount = 0;
        }

        $upcomingMeetingsCount = 0;
        $confirmedMeetings = [];
        $confirmedMeetingsCount = 0;
        $pagination = Pagination::build(0, 1, self::MEETINGS_PER_PAGE);
        try {
            $upcomingMeetingsCount = $this->meetings->countUpcomingConfirmedMeetings($userId);
            $confirmedMeetingsCount = $this->meetings->countConfirmedMeetings($userId);
            $page = Pagination::pageFromQuery($query, 'page');
            $pagination = Pagination::build($confirmedMeetingsCount, $page, self::MEETINGS_PER_PAGE);
            $confirmedMeetings = $this->meetings->findConfirmedMeetingsPage(
                $userId,
                (int) $pagination['offset'],
                (int) $pagination['per_page']
            );
        } catch (\Throwable $e) {
            $upcomingMeetingsCount = 0;
            $confirmedMeetings = [];
            $confirmedMeetingsCount = 0;
            $pagination = Pagination::build(0, 1, self::MEETINGS_PER_PAGE);
        }

        $fmtDt = static function (?string $dbValue): string {
            if (!$dbValue) {
                return '';
            }
            $ts = strtotime($dbValue);
            if (!$ts) {
                return (string) $dbValue;
            }
            return date('d/m/Y H:i', $ts);
        };

        return [
            'unreadCount' => $unreadCount,
            'upcomingMeetingsCount' => $upcomingMeetingsCount,
            'confirmedMeetings' => $confirmedMeetings,
            'confirmedMeetingsCount' => $confirmedMeetingsCount,
            'pagination' => $pagination,
            'fmtDt' => $fmtDt,
        ];
    }
}
