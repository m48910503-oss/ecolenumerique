<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;

use App\Models\TeacherMeetingsModel;

final class MeetingsService extends Service
{
    private \PDO $pdo;
    private TeacherMeetingsModel $meetings;

    public function __construct(\PDO $pdo, TeacherMeetingsModel $meetings)
    {
        $this->pdo = $pdo;
        $this->meetings = $meetings;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(array $user, bool $isAdmin, string $method, array $get, array $post): array
    {
        $meetingsHasDuration = $this->schema()->ensureCourseMeetingsDurationColumn();

        $errors = [];
        $success = (string) $this->flashGet('success', '');

        $status = (string) ($get['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'confirmed', 'cancelled', 'all'], true)) {
            $status = 'pending';
        }
        $courseFilterId = (int) ($get['course_id'] ?? 0);
        $q = trim((string) ($get['q'] ?? ''));

        if ($method === 'POST') {
            $actionResult = $this->handleAction($user, $isAdmin, $post);
            $errors = $actionResult['errors'];
            $success = $actionResult['success'];

            if ($errors === [] && $success !== '') {
                $this->flashSet('success', $success);
                return [
                    'redirectTo' => $this->siteUrl('teacher/meetings'),
                    'view' => [],
                ];
            }
        }

        $courses = $this->meetings->findCoursesForFilter($isAdmin, (int) $user['id']);
        $meetingRequests = $this->meetings->findMeetingRequests(
            $isAdmin,
            (int) $user['id'],
            $status,
            $courseFilterId,
            $q,
            $meetingsHasDuration
        );
        [$meetingGroups, $groupOrder] = $this->groupMeetingRequests($meetingRequests);

        $statusLabels = [
            'pending' => 'En attente',
            'confirmed' => "Confirm\u{00e9}",
            'cancelled' => 'Annule',
        ];

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'success' => $success,
                'status' => $status,
                'courseFilterId' => $courseFilterId,
                'q' => $q,
                'courses' => $courses,
                'meetingGroups' => $meetingGroups,
                'groupOrder' => $groupOrder,
                'statusLabels' => $statusLabels,
                'fmtDt' => static function (?string $dbValue): string {
                    return self::fmtDt($dbValue);
                },
                'dtLocalValue' => static function (?string $dbValue): string {
                    return self::dtLocalValue($dbValue);
                },
            ],
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @return array{errors:array<int,string>,success:string}
     */
    private function handleAction(array $user, bool $isAdmin, array $post): array
    {
        $errors = [];
        $success = '';

        $action = (string) ($post['action'] ?? '');
        $meetingId = (int) ($post['meeting_id'] ?? 0);
        $reason = trim((string) ($post['reason'] ?? ''));
        if ($meetingId < 1) {
            return ['errors' => ['Rendez-vous invalide.'], 'success' => ''];
        }

        $meeting = $this->meetings->findMeetingForModeration($meetingId);
        if (!$meeting) {
            return ['errors' => ['Rendez-vous introuvable.'], 'success' => ''];
        }
        if (!$isAdmin && (int) ($meeting['teacher_id'] ?? 0) !== (int) $user['id']) {
            return ['errors' => ["Acc\u{00e8}s refus\u{00e9}."], 'success' => ''];
        }

        if ($action === 'confirm_meeting') {
            if ((string) ($meeting['status'] ?? '') !== 'pending') {
                $errors[] = "Seuls les rendez-vous en attente peuvent \u{00ea}tre confirm\u{00e9}s.";
            }

            $startAt = self::parseDtLocal((string) ($post['start_at'] ?? '')) ?? ((string) ($meeting['start_at'] ?? '') !== '' ? (string) $meeting['start_at'] : null);
            $endAt = self::parseDtLocal((string) ($post['end_at'] ?? '')) ?? ((string) ($meeting['end_at'] ?? '') !== '' ? (string) $meeting['end_at'] : null);
            if ($startAt && !$endAt) {
                $durationMin = (int) ($meeting['duration_min'] ?? 0);
                if ($durationMin < 1) {
                    $durationMin = 60;
                }
                $endAt = date('Y-m-d H:i:s', strtotime($startAt) + ($durationMin * 60));
            }
            if (!$startAt || !$endAt) {
                $errors[] = 'Dates de rendez-vous manquantes.';
            } elseif (strtotime($startAt) >= strtotime($endAt)) {
                $errors[] = "La date de fin doit \u{00ea}tre apr\u{00e8}s la date de d\u{00e9}but.";
            }

            $meetingLink = trim((string) ($post['meeting_link'] ?? ''));
            $meetingPlace = trim((string) ($post['meeting_place'] ?? ''));
            $isOnline = (string) ($meeting['mode'] ?? '') === 'online';
            if ($isOnline && $meetingLink === '') {
                $errors[] = 'Ajoutez le lien de visioconference.';
            }
            if (!$isOnline && $meetingPlace === '') {
                $errors[] = 'Ajoutez l adresse du rendez-vous.';
            }

            if ($errors === []) {
                try {
                    $this->pdo->beginTransaction();

                    $this->meetings->updateMeetingConfirmed(
                        $meetingId,
                        (int) $user['id'],
                        $startAt,
                        $endAt,
                        $isOnline ? $meetingLink : null,
                        $isOnline ? null : $meetingPlace
                    );
                    $this->meetings->cancelSiblingPending($meeting);

                    $courseUrl = !empty($meeting['course_slug'])
                        ? $this->absUrl('course?slug=' . (string) $meeting['course_slug'])
                        : $this->absUrl('course?id=' . (int) $meeting['course_id']);
                    $body = "Votre rendez-vous pour \""
                        . ((string) ($meeting['course_title'] ?? 'cours'))
                        . "\" a \u{00e9}t\u{00e9} confirm\u{00e9}. D\u{00e9}but : "
                        . $startAt . ' - Fin : ' . $endAt . '.';
                    if ($isOnline) {
                        $body .= ' Lien : ' . $meetingLink;
                    } else {
                        $body .= ' Adresse : ' . $meetingPlace;
                    }
                    $this->meetings->insertNotification(
                        (int) $meeting['student_id'],
                        "Rendez-vous confirm\u{00e9}",
                        $body,
                        $courseUrl
                    );

                    $this->pdo->commit();
                    $success = "Rendez-vous confirm\u{00e9}.";
                } catch (\Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    $errors[] = 'Erreur lors de la confirmation.';
                }
            }
        } elseif ($action === 'cancel_meeting') {
            $currentStatus = (string) ($meeting['status'] ?? '');
            if (!in_array($currentStatus, ['pending', 'confirmed'], true)) {
                $errors[] = "Ce rendez-vous ne peut pas être annulé.";
            }
            if ($reason === '') {
                $errors[] = 'Un motif est requis pour annuler ce rendez-vous.';
            }

            if ($errors === []) {
                try {
                    $this->pdo->beginTransaction();

                    $wasPending = $currentStatus === 'pending';
                    $this->meetings->updateMeetingStatus($meetingId, 'cancelled');
                    $this->meetings->cancelSiblingPending($meeting);

                    $courseUrl = !empty($meeting['course_slug'])
                        ? $this->absUrl('course?slug=' . (string) $meeting['course_slug'])
                        : $this->absUrl('course?id=' . (int) $meeting['course_id']);

                    $notifTitle = "Rendez-vous annulé";
                    $notifBody = "Le rendez-vous \""
                        . ((string) ($meeting['title'] ?? 'Rendez-vous'))
                        . "\" a été annulé par le formateur. Motif : "
                        . $reason;
                    if ($wasPending) {
                        $notifTitle = "Nouveaux créneaux demandés";
                        $notifBody = "Le formateur n'est disponible sur aucun de vos créneaux proposés pour \""
                            . ((string) ($meeting['course_title'] ?? 'cours'))
                            . "\". Motif : " . $reason . '. Merci de proposer 3 nouveaux créneaux.';
                    }
                    $this->meetings->insertNotification(
                        (int) $meeting['student_id'],
                        $notifTitle,
                        $notifBody,
                        $courseUrl
                    );

                    $this->pdo->commit();
                    $success = $wasPending
                        ? "Créneaux refusés. L'étudiant a été invité à proposer 3 nouveaux créneaux."
                        : "Rendez-vous annulé.";
                } catch (\Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    $errors[] = "Erreur lors de l'annulation.";
                }
            }
        } else {
            $errors[] = 'Action invalide.';
        }

        return ['errors' => $errors, 'success' => $success];
    }

    /**
     * @param array<int,array<string,mixed>> $meetingRequests
     * @return array{0:array<string,array<string,mixed>>,1:array<int,string>}
     */
    private function groupMeetingRequests(array $meetingRequests): array
    {
        $meetingGroups = [];
        $groupOrder = [];
        foreach ($meetingRequests as $m) {
            $keyData = [
                'course_id' => (int) ($m['course_id'] ?? 0),
                'chapter_id' => $m['chapter_id'] !== null ? (int) $m['chapter_id'] : null,
                'title' => (string) ($m['title'] ?? ''),
                'mode' => (string) ($m['mode'] ?? ''),
                'student_id' => (int) ($m['student_id'] ?? 0),
                'status' => (string) ($m['status'] ?? ''),
            ];
            $key = md5((string) json_encode($keyData));
            if (!isset($meetingGroups[$key])) {
                $studentName = trim((string) ($m['first_name'] ?? '') . ' ' . (string) ($m['last_name'] ?? ''));
                if ($studentName === '') {
                    $studentName = "\u{00c9}tudiant";
                }
                $meetingGroups[$key] = [
                    'key' => $key,
                    'key_data' => $keyData,
                    'course_title' => (string) ($m['course_title'] ?? ''),
                    'course_slug' => (string) ($m['course_slug'] ?? ''),
                    'chapter_title' => (string) ($m['chapter_title'] ?? ''),
                    'title' => (string) ($m['title'] ?? 'Rendez-vous'),
                    'mode' => (string) ($m['mode'] ?? ''),
                    'status' => (string) ($m['status'] ?? ''),
                    'student_name' => $studentName,
                    'student_email' => (string) ($m['email'] ?? ''),
                    'items' => [],
                ];
                $groupOrder[] = $key;
            }
            $meetingGroups[$key]['items'][] = $m;
        }

        foreach ($meetingGroups as &$group) {
            usort($group['items'], static function ($a, $b) {
                $ta = !empty($a['start_at']) ? strtotime((string) $a['start_at']) : PHP_INT_MAX;
                $tb = !empty($b['start_at']) ? strtotime((string) $b['start_at']) : PHP_INT_MAX;
                return $ta <=> $tb;
            });
        }
        unset($group);

        return [$meetingGroups, $groupOrder];
    }

    private static function dtLocalValue(?string $dbValue): string
    {
        if (!$dbValue) {
            return '';
        }
        $ts = strtotime($dbValue);
        if (!$ts) {
            return '';
        }
        return date('Y-m-d\TH:i', $ts);
    }

    private static function parseDtLocal(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $value);
        if (!$dt) {
            $ts = strtotime($value);
            if (!$ts) {
                return null;
            }
            return date('Y-m-d H:i:s', $ts);
        }
        return $dt->format('Y-m-d H:i:s');
    }

    private static function fmtDt(?string $dbValue): string
    {
        if (!$dbValue) {
            return '';
        }
        $ts = strtotime($dbValue);
        if (!$ts) {
            return (string) $dbValue;
        }
        return date('d/m/Y H:i', $ts);
    }
}

