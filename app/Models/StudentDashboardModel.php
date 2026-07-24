<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class StudentDashboardModel extends Model
{
    private ?bool $hasArchivedAtColumn = null;

    public function countUnreadNotifications(int $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0';
        if ($this->hasArchivedAtColumn()) {
            $sql .= ' AND archived_at IS NULL';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countUnreadSupportReplies(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM support_messages m
             JOIN support_conversations c ON c.id = m.conversation_id
             LEFT JOIN support_conversation_reads r
                ON r.conversation_id = c.id
               AND r.user_id = :uid_read
             WHERE c.created_by = :uid_owner
               AND m.id > COALESCE(r.last_read_message_id, 0)
               AND m.sender_id <> :uid_sender'
        );
        $stmt->execute([
            ':uid_read' => $userId,
            ':uid_owner' => $userId,
            ':uid_sender' => $userId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function countUnreadSupportNotifications(int $userId): int
    {
        $sql = 'SELECT COUNT(*)
             FROM notifications
             WHERE user_id = :uid
               AND is_read = 0
               AND (
                    LOWER(title) LIKE :support
                    OR LOWER(body) LIKE :support
               )';
        if ($this->hasArchivedAtColumn()) {
            $sql .= ' AND archived_at IS NULL';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':support' => '%support%',
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchNotifications(int $userId, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $sql = 'SELECT * FROM notifications WHERE user_id = :uid';
        if ($this->hasArchivedAtColumn()) {
            $sql .= ' AND archived_at IS NULL';
        }
        $sql .= " ORDER BY created_at DESC LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function countActiveEnrollments(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE user_id = :uid AND status = "active"');
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countPaidOrders(int $userId): int
    {
        if (!$this->tableExists('orders')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM orders
             WHERE user_id = :uid
               AND LOWER(status) IN ("paid","completed","succeeded","success","free")'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countViewedCourses(int $userId): int
    {
        if (!$this->tableExists('course_views')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT course_id)
             FROM course_views
             WHERE user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countExamAttempts(int $userId): int
    {
        if (!$this->tableExists('exam_attempts')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM exam_attempts
             WHERE user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function sumLessonPositionSeconds(int $userId): int
    {
        if (!$this->tableExists('lesson_positions')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(seconds), 0)
             FROM lesson_positions
             WHERE user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array{day_key:string,total_seconds:int}>
     */
    public function fetchLessonPositionDailyTotals(int $userId, string $fromDate): array
    {
        if (!$this->tableExists('lesson_positions')) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT DATE(updated_at) AS day_key, COALESCE(SUM(seconds), 0) AS total_seconds
             FROM lesson_positions
             WHERE user_id = :uid
               AND updated_at >= :from_date
             GROUP BY DATE(updated_at)
             ORDER BY day_key ASC'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':from_date' => $fromDate,
        ]);
        $rows = $stmt->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $row) {
            $dayKey = trim((string) ($row['day_key'] ?? ''));
            if ($dayKey === '') {
                continue;
            }

            $out[] = [
                'day_key' => $dayKey,
                'total_seconds' => max(0, (int) ($row['total_seconds'] ?? 0)),
            ];
        }

        return $out;
    }

    public function countCompletedCourses(int $userId): int
    {
        if (
            !$this->tableExists('enrollments')
            || !$this->tableExists('course_lessons')
            || !$this->tableExists('course_chapters')
            || !$this->tableExists('lesson_completions')
        ) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM enrollments e
             WHERE e.user_id = :uid
               AND e.status = "active"
               AND (
                    SELECT COUNT(*)
                    FROM course_lessons cl
                    JOIN course_chapters ch ON ch.id = cl.chapter_id
                    WHERE ch.course_id = e.course_id
               ) > 0
               AND (
                    SELECT COUNT(*)
                    FROM lesson_completions lc
                    WHERE lc.user_id = :uid_done
                      AND lc.course_id = e.course_id
               ) >= (
                    SELECT COUNT(*)
                    FROM course_lessons cl
                    JOIN course_chapters ch ON ch.id = cl.chapter_id
                    WHERE ch.course_id = e.course_id
               )'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':uid_done' => $userId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function countUpcomingMeetings(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*)
            FROM course_meetings
            WHERE student_id = :uid
              AND status = "confirmed"
              AND start_at IS NOT NULL
              AND start_at >= NOW()');
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function fetchNextConfirmedMeeting(int $userId): ?array
    {
        if (!$this->tableExists('course_meetings')) {
            return null;
        }

        $hasCourseId = $this->columnExists('course_meetings', 'course_id');
        $hasMeetingLink = $this->columnExists('course_meetings', 'meeting_link');
        $hasMeetingPlace = $this->columnExists('course_meetings', 'meeting_place');

        $courseJoin = ($hasCourseId && $this->tableExists('courses'))
            ? 'LEFT JOIN courses c ON c.id = cm.course_id'
            : '';

        $courseFields = $courseJoin !== ''
            ? 'c.title AS course_title, c.slug AS course_slug, '
            : 'NULL AS course_title, NULL AS course_slug, ';

        $linkField = $hasMeetingLink ? 'cm.meeting_link' : 'NULL';
        $placeField = $hasMeetingPlace ? 'cm.meeting_place' : 'NULL';

        $stmt = $this->pdo->prepare(
            "SELECT
                cm.id,
                {$courseFields}
                cm.title AS meeting_title,
                cm.mode,
                cm.start_at,
                cm.end_at,
                {$linkField} AS meeting_link,
                {$placeField} AS meeting_place
             FROM course_meetings cm
             {$courseJoin}
             WHERE cm.student_id = :uid
               AND cm.status = 'confirmed'
               AND cm.start_at IS NOT NULL
               AND cm.start_at >= NOW()
             ORDER BY cm.start_at ASC
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchPopularCoursesForStudent(int $userId, int $limit = 4): array
    {
        if (!$this->tableExists('courses')) {
            return [];
        }

        $limit = max(1, min(12, $limit));
        $hasCourseViews = $this->tableExists('course_views');
        $hasEnrollments = $this->tableExists('enrollments');
        $hasCourseRatings = $this->tableExists('course_ratings');
        $hasCategories = $this->tableExists('categories');

        $viewJoin = $hasCourseViews
            ? 'LEFT JOIN (SELECT course_id, COUNT(*) AS view_count FROM course_views GROUP BY course_id) cv ON cv.course_id = c.id'
            : '';
        $viewField = $hasCourseViews ? 'COALESCE(cv.view_count, 0)' : '0';
        $enrollJoin = $hasEnrollments
            ? 'LEFT JOIN enrollments e ON e.course_id = c.id AND e.user_id = :uid_enroll AND e.status = "active"'
            : '';
        $enrollFilter = $hasEnrollments ? 'AND e.id IS NULL' : '';
        $ratingJoin = $hasCourseRatings
            ? 'LEFT JOIN (SELECT course_id, AVG(rating) AS avg_rating, COUNT(*) AS rating_count FROM course_ratings GROUP BY course_id) r ON r.course_id = c.id'
            : '';
        $ratingAvgField = $hasCourseRatings ? 'COALESCE(r.avg_rating, 0)' : '0';
        $ratingCountField = $hasCourseRatings ? 'COALESCE(r.rating_count, 0)' : '0';
        $categoryJoin = $hasCategories ? 'LEFT JOIN categories cat ON cat.id = c.category_id' : '';
        $categoryField = $hasCategories ? 'cat.name AS category_name,' : 'NULL AS category_name,';

        $sql = "SELECT
                    c.id,
                    c.title,
                    c.slug,
                    c.level,
                    c.price_xof,
                    c.cover_path,
                    c.cover_alt,
                    c.created_at,
                    u.first_name,
                    u.last_name,
                    {$categoryField}
                    {$viewField} AS view_count,
                    {$ratingAvgField} AS avg_rating,
                    {$ratingCountField} AS rating_count
                FROM courses c
                LEFT JOIN users u ON u.id = c.teacher_id
                {$categoryJoin}
                {$viewJoin}
                {$ratingJoin}
                {$enrollJoin}
                WHERE c.status = 'approved'
                  AND c.deleted_at IS NULL
                  {$enrollFilter}
                ORDER BY view_count DESC, c.created_at DESC
                LIMIT {$limit}";

        $stmt = $this->pdo->prepare($sql);
        $params = [];
        if ($hasEnrollments) {
            $params[':uid_enroll'] = $userId;
        }
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        if ($rows !== []) {
            return $rows;
        }

        $fallbackSql = "SELECT
                            c.id,
                            c.title,
                            c.slug,
                            c.level,
                            c.price_xof,
                            c.cover_path,
                            c.cover_alt,
                            c.created_at,
                            u.first_name,
                            u.last_name,
                            {$categoryField}
                            0 AS view_count,
                            {$ratingAvgField} AS avg_rating,
                            {$ratingCountField} AS rating_count
                        FROM courses c
                        LEFT JOIN users u ON u.id = c.teacher_id
                        {$categoryJoin}
                        {$ratingJoin}
                        WHERE c.status = 'approved'
                          AND c.deleted_at IS NULL
                        ORDER BY c.created_at DESC
                        LIMIT {$limit}";
        $fallbackStmt = $this->pdo->query($fallbackSql);
        return $fallbackStmt ? ($fallbackStmt->fetchAll() ?: []) : [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchRecentActivity(int $userId, int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));
        $activities = [];

        if ($this->tableExists('notifications')) {
            $notifStmt = $this->pdo->prepare(
                'SELECT id, title, body, is_read, created_at, url
                 FROM notifications
                 WHERE user_id = :uid
                 ORDER BY created_at DESC
                 LIMIT 20'
            );
            $notifStmt->execute([':uid' => $userId]);
            foreach (($notifStmt->fetchAll() ?: []) as $row) {
                $isRead = (int) ($row['is_read'] ?? 0) === 1;
                $activities[] = [
                    'type' => 'notification',
                    'title' => (string) ($row['title'] ?? 'Notification'),
                    'meta' => (string) ($row['body'] ?? ''),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'url' => !empty($row['url'])
                        ? (string) $row['url']
                        : site_url('notifications?space=student&open=' . (int) ($row['id'] ?? 0)),
                    'tone' => $isRead ? 'slate' : 'rose',
                    'badge' => $isRead ? 'Lue' : 'Non lue',
                    'icon' => 'bell',
                ];
            }
        }

        if ($this->tableExists('orders')) {
            $orderStmt = $this->pdo->prepare(
                'SELECT reference, status, total_xof, created_at
                 FROM orders
                 WHERE user_id = :uid
                 ORDER BY created_at DESC
                 LIMIT 10'
            );
            $orderStmt->execute([':uid' => $userId]);
            foreach (($orderStmt->fetchAll() ?: []) as $row) {
                $status = strtolower((string) ($row['status'] ?? ''));
                $isPaid = in_array($status, ['paid', 'completed', 'succeeded', 'success', 'free'], true);
                $activities[] = [
                    'type' => 'order',
                    'title' => $isPaid ? 'Paiement validé' : 'Commande en cours',
                    'meta' => sprintf(
                        '%s · %s',
                        (string) ($row['reference'] ?? ''),
                        number_format((int) ($row['total_xof'] ?? 0), 0, ',', ' ') . ' F CFA'
                    ),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'url' => site_url('student/orders'),
                    'tone' => $isPaid ? 'emerald' : 'amber',
                    'badge' => $isPaid ? 'Payée' : 'En attente',
                    'icon' => 'receipt',
                ];
            }
        }

        if (
            $this->tableExists('exam_attempts')
            && $this->tableExists('exam_sessions')
            && $this->tableExists('courses')
        ) {
            $examStmt = $this->pdo->prepare(
                'SELECT
                    ea.status,
                    ea.score,
                    ea.created_at,
                    es.id AS exam_id,
                    es.title AS exam_title,
                    c.title AS course_title
                 FROM exam_attempts ea
                 JOIN exam_sessions es ON es.id = ea.exam_id
                 JOIN courses c ON c.id = es.course_id
                 WHERE ea.user_id = :uid
                 ORDER BY ea.created_at DESC
                 LIMIT 10'
            );
            $examStmt->execute([':uid' => $userId]);
            foreach (($examStmt->fetchAll() ?: []) as $row) {
                $status = strtolower((string) ($row['status'] ?? ''));
                $tone = $status === 'graded' ? 'emerald' : ($status === 'submitted' ? 'amber' : 'slate');
                $badge = $status === 'graded' ? 'Corrigé' : ($status === 'submitted' ? 'Soumis' : ucfirst($status));
                $meta = (string) ($row['course_title'] ?? 'Cours');
                if ($status === 'graded' && isset($row['score'])) {
                    $meta .= ' · Note: ' . (int) ($row['score'] ?? 0);
                }

                $activities[] = [
                    'type' => 'exam',
                    'title' => (string) ($row['exam_title'] ?? 'Examen'),
                    'meta' => $meta,
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'url' => site_url('student/exam?exam_id=' . (int) ($row['exam_id'] ?? 0)),
                    'tone' => $tone,
                    'badge' => $badge,
                    'icon' => 'file-text',
                ];
            }
        }

        usort($activities, static function (array $a, array $b): int {
            $aDate = (string) ($a['created_at'] ?? '');
            $bDate = (string) ($b['created_at'] ?? '');
            return strcmp($bDate, $aDate);
        });

        return array_slice($activities, 0, $limit);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchExamRows(int $userId, bool $attemptsHasMeetingId, int $limit = 50): array
    {
        $limit = max(1, $limit);
        $meetingIdSelect = $attemptsHasMeetingId
            ? '(SELECT ea.meeting_id FROM exam_attempts ea WHERE ea.exam_id = es.id AND ea.user_id = :uid ORDER BY ea.id DESC LIMIT 1) AS last_meeting_id'
            : 'NULL AS last_meeting_id';

        $sql = 'SELECT
                es.id AS exam_id,
                es.title AS exam_title,
                es.type AS exam_type,
                es.duration_min,
                es.max_attempts,
                es.min_score,
                es.meeting_mode,
                c.id AS course_id,
                c.title AS course_title,
                c.slug AS course_slug,
                (SELECT COUNT(*) FROM exam_attempts ea WHERE ea.exam_id = es.id AND ea.user_id = :uid) AS attempts_count,
                (SELECT ea.status FROM exam_attempts ea WHERE ea.exam_id = es.id AND ea.user_id = :uid ORDER BY ea.id DESC LIMIT 1) AS last_status,
                (SELECT ea.score FROM exam_attempts ea WHERE ea.exam_id = es.id AND ea.user_id = :uid ORDER BY ea.id DESC LIMIT 1) AS last_score,
                (SELECT ea.submitted_at FROM exam_attempts ea WHERE ea.exam_id = es.id AND ea.user_id = :uid ORDER BY ea.id DESC LIMIT 1) AS last_submitted_at,
                ' . $meetingIdSelect . ',
                (SELECT COUNT(*) FROM exam_meetings em WHERE em.exam_id = es.id AND em.status = "available" AND em.start_at >= NOW()) AS available_slots
            FROM enrollments e
            JOIN courses c ON c.id = e.course_id
            JOIN exam_sessions es ON es.course_id = c.id
            WHERE e.user_id = :uid AND e.status = "active"
            ORDER BY es.created_at DESC, es.id DESC
            LIMIT ' . $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $meetingIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchExamMeetingsByIds(array $meetingIds): array
    {
        if ($meetingIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($meetingIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id, start_at, end_at, status, mode, link, place
             FROM exam_meetings
             WHERE id IN ($placeholders)
             LIMIT 200"
        );
        $stmt->execute($meetingIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchIssuedCertificates(int $userId, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $stmt = $this->pdo->prepare(
            "SELECT cert.code, cert.issued_at, cert.score, cert.status, c.title AS course_title
            FROM certificates cert
            JOIN courses c ON c.id = cert.course_id
            WHERE cert.user_id = :uid AND cert.status = 'issued'
            ORDER BY cert.issued_at DESC, cert.id DESC
            LIMIT {$limit}"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function sumReferralEarnings(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount_xof), 0) FROM referral_commissions WHERE referrer_id = :uid AND status = "earned"'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array{level:int,total_amount:int}>
     */
    public function sumReferralEarningsByLevel(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT level, COALESCE(SUM(amount_xof), 0) AS total_amount
            FROM referral_commissions
            WHERE referrer_id = :uid AND status = "earned"
            GROUP BY level'
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'level' => (int) ($row['level'] ?? 0),
                'total_amount' => (int) ($row['total_amount'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchReferralByUser(int $userId, int $limit = 50): array
    {
        return $this->fetchReferralByUserPage($userId, 0, $limit);
    }

    public function countReferralByUser(int $userId): int
    {
        if (!$this->tableExists('referral_commissions')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM (
                SELECT rc.buyer_id
                FROM referral_commissions rc
                WHERE rc.referrer_id = :uid
                  AND rc.status = "earned"
                GROUP BY rc.buyer_id
             ) ref_users'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countReferralRows(int $userId): int
    {
        if (!$this->tableExists('referrals')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM referrals
             WHERE referrer_user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchReferralRowsPage(int $userId, int $offset, int $limit = 50, bool $includeCommissionData = true): array
    {
        if (!$this->tableExists('referrals')) {
            return [];
        }

        $limit = max(1, $limit);
        $offset = max(0, $offset);

        if ($includeCommissionData && $this->tableExists('referral_commissions')) {
            $sql = "SELECT
                    r.referred_user_id AS buyer_id,
                    r.created_at AS referral_created_at,
                    COALESCE(u.first_name, '') AS buyer_first,
                    COALESCE(u.last_name, '') AS buyer_last,
                    COALESCE(SUM(CASE WHEN rc.level = 1 THEN rc.amount_xof ELSE 0 END), 0) AS amount_l1,
                    COALESCE(SUM(CASE WHEN rc.level = 2 THEN rc.amount_xof ELSE 0 END), 0) AS amount_l2,
                    COALESCE(SUM(rc.amount_xof), 0) AS total_amount,
                    COUNT(DISTINCT rc.order_id) AS total_count,
                    MAX(rc.created_at) AS last_at
                FROM referrals r
                LEFT JOIN users u ON u.id = r.referred_user_id
                LEFT JOIN referral_commissions rc
                    ON rc.referrer_id = r.referrer_user_id
                   AND rc.buyer_id = r.referred_user_id
                   AND rc.status = 'earned'
                WHERE r.referrer_user_id = :uid
                GROUP BY r.referred_user_id, r.created_at, u.first_name, u.last_name
                ORDER BY r.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        } else {
            $sql = "SELECT
                    r.referred_user_id AS buyer_id,
                    r.created_at AS referral_created_at,
                    COALESCE(u.first_name, '') AS buyer_first,
                    COALESCE(u.last_name, '') AS buyer_last,
                    0 AS amount_l1,
                    0 AS amount_l2,
                    0 AS total_amount,
                    0 AS total_count,
                    NULL AS last_at
                FROM referrals r
                LEFT JOIN users u ON u.id = r.referred_user_id
                WHERE r.referrer_user_id = :uid
                ORDER BY r.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchReferralByUserPage(int $userId, int $offset, int $limit = 50): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $stmt = $this->pdo->prepare(
            "SELECT rc.buyer_id,
                    b.first_name AS buyer_first, b.last_name AS buyer_last,
                    SUM(CASE WHEN rc.level = 1 THEN rc.amount_xof ELSE 0 END) AS amount_l1,
                    SUM(CASE WHEN rc.level = 2 THEN rc.amount_xof ELSE 0 END) AS amount_l2,
                    SUM(rc.amount_xof) AS total_amount,
                    COUNT(*) AS total_count,
                    MAX(rc.created_at) AS last_at
                FROM referral_commissions rc
                JOIN users b ON b.id = rc.buyer_id
                WHERE rc.referrer_id = :uid AND rc.status = 'earned'
                GROUP BY rc.buyer_id
                ORDER BY total_amount DESC
                LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function countReferralCommissionHistory(int $userId): int
    {
        if (!$this->tableExists('referral_commissions')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM referral_commissions
             WHERE referrer_id = :uid AND status = "earned"'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchReferralCommissionHistoryPage(int $userId, int $offset, int $limit = 50): array
    {
        if (!$this->tableExists('referral_commissions')) {
            return [];
        }

        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $stmt = $this->pdo->prepare(
            "SELECT rc.id,
                    rc.level,
                    rc.amount_xof,
                    rc.rate_percent,
                    rc.created_at,
                    c.title AS course_title,
                    b.first_name AS buyer_first,
                    b.last_name AS buyer_last,
                    o.reference AS order_ref
                FROM referral_commissions rc
                LEFT JOIN courses c ON c.id = rc.course_id
                LEFT JOIN users b ON b.id = rc.buyer_id
                LEFT JOIN orders o ON o.id = rc.order_id
                WHERE rc.referrer_id = :uid AND rc.status = 'earned'
                ORDER BY rc.created_at DESC, rc.id DESC
                LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    private function hasArchivedAtColumn(): bool
    {
        if ($this->hasArchivedAtColumn !== null) {
            return $this->hasArchivedAtColumn;
        }

        try {
            $this->hasArchivedAtColumn = $this->columnExists('notifications', 'archived_at');
        } catch (\Throwable $e) {
            $this->hasArchivedAtColumn = false;
        }

        return $this->hasArchivedAtColumn;
    }
}
