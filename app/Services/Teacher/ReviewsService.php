<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;
use App\Models\TeacherReviewsModel;

final class ReviewsService extends Service
{
    private TeacherReviewsModel $model;

    public function __construct(TeacherReviewsModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $roles
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(array $user, array $roles, string $method, array $get, array $post): array
    {
        $teacherId = (int) ($user['id'] ?? 0);
        $isAdmin = in_array('admin', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);
        if (!$isAdmin && !$isTeacher) {
            return [
                'redirectTo' => $this->siteUrl(''),
                'view' => [],
            ];
        }

        $filters = $this->normalizeFilters($get);
        $successMessage = (string) $this->flashGet('teacher_reviews_success', '');
        $errors = [];
        $moderationReady = $this->model->ratingsModerationReady();
        $replyReady = $this->model->ratingsReplyReady();

        if (strtoupper($method) === 'POST') {
            $action = trim((string) ($post['action'] ?? ''));
            $reviewId = max(0, (int) ($post['review_id'] ?? 0));
            $postedCourseId = max(0, (int) ($post['course_id'] ?? 0));
            $postedPage = max(1, (int) ($post['page'] ?? 0));
            if ($postedPage > 0) {
                $filters['page'] = $postedPage;
            }
            if ($postedCourseId > 0) {
                $filters['courseId'] = $postedCourseId;
                $filters['detail'] = true;
            }

            $mutated = false;
            $flashMessage = '';

            if ($action === 'hide_review' || $action === 'unhide_review') {
                if (!$moderationReady) {
                    $errors[] = 'Le masquage des avis n’est pas disponible sur cette base.';
                } elseif ($reviewId < 1) {
                    $errors[] = 'Avis invalide.';
                } else {
                    $row = $this->model->findReviewForTeacher($teacherId, $reviewId);
                    if (!$row) {
                        $errors[] = 'Avis introuvable.';
                    } else {
                        $mustHide = $action === 'hide_review';
                        $alreadyHidden = (int) ($row['is_hidden_by_teacher'] ?? 0) === 1;
                        if ($alreadyHidden !== $mustHide) {
                            $this->model->setReviewHiddenState($reviewId, $mustHide, $teacherId);
                            $mutated = true;
                        }
                        if ($filters['courseId'] <= 0) {
                            $filters['courseId'] = max(0, (int) ($row['course_id'] ?? 0));
                        }
                        $flashMessage = $mustHide ? 'Avis masqué avec succès.' : 'Avis rendu visible.';
                    }
                }
            } elseif ($action === 'save_reply' || $action === 'delete_reply') {
                if (!$replyReady) {
                    $errors[] = 'La réponse de l’enseignant est indisponible. Appliquez la migration `database/schema_v51.sql`.';
                } elseif ($reviewId < 1) {
                    $errors[] = 'Avis invalide.';
                } else {
                    $row = $this->model->findReviewForTeacher($teacherId, $reviewId);
                    if (!$row) {
                        $errors[] = 'Avis introuvable.';
                    } else {
                        if ($filters['courseId'] <= 0) {
                            $filters['courseId'] = max(0, (int) ($row['course_id'] ?? 0));
                        }

                        if ($action === 'save_reply') {
                            $replyText = trim((string) ($post['reply_text'] ?? ''));
                            if ($replyText === '') {
                                $errors[] = 'La réponse ne peut pas être vide.';
                            } else {
                                $replyLen = function_exists('mb_strlen') ? mb_strlen($replyText) : strlen($replyText);
                                if ($replyLen > 1200) {
                                    $errors[] = 'La réponse est trop longue (1200 caractères max).';
                                } else {
                                    $this->model->saveReviewReply($reviewId, $replyText, $teacherId);
                                    $mutated = true;
                                    $flashMessage = 'Réponse enregistrée.';
                                }
                            }
                        } else {
                            $this->model->clearReviewReply($reviewId);
                            $mutated = true;
                            $flashMessage = 'Réponse supprimée.';
                        }
                    }
                }
            }

            if ($mutated && $errors === [] && $flashMessage !== '') {
                $this->flashSet('teacher_reviews_success', $flashMessage);
                return [
                    'redirectTo' => $this->buildRedirectUrl($filters),
                    'view' => [],
                ];
            }
        }

        $courseOptions = $this->filterPublishedCourses($this->model->fetchCourseOptions($teacherId, true));
        $publishableCourseIds = [];
        foreach ($courseOptions as $course) {
            $courseId = max(0, (int) ($course['id'] ?? 0));
            if ($courseId > 0) {
                $publishableCourseIds[$courseId] = true;
            }
        }

        $visibleReviews = $this->decorateDetailRows(array_values(array_filter(
            $this->model->fetchReviewRows($teacherId, 0, 'visible', 0, 5000, true),
            static function (array $row) use ($publishableCourseIds): bool {
                $courseId = max(0, (int) ($row['course_id'] ?? 0));
                return $courseId > 0 && isset($publishableCourseIds[$courseId]);
            }
        )));
        $summary = $this->buildSummary($visibleReviews);
        $studentCounts = $this->model->fetchCourseStudentCounts($teacherId, true);
        $allCourseCards = $this->buildCourseCards($courseOptions, $visibleReviews, $studentCounts);
        $detailMode = $filters['courseId'] > 0;
        $selectedCourseCard = $detailMode ? $this->findCourseCard($allCourseCards, $filters['courseId']) : null;
        if ($detailMode && !$selectedCourseCard) {
            $detailMode = false;
        }

        $detailRows = $detailMode
            ? array_values(array_filter(
                $visibleReviews,
                static fn (array $row): bool => (int) ($row['course_id'] ?? 0) === (int) $filters['courseId']
            ))
            : [];
        $ratingDistribution = $this->buildRatingDistribution($detailMode ? $detailRows : $visibleReviews);
        $reviewPagination = [
            'page' => 1,
            'per_page' => max(1, count($detailRows)),
            'total' => count($detailRows),
            'total_pages' => 1,
            'has_prev' => false,
            'has_next' => false,
            'prev_page' => 1,
            'next_page' => 1,
            'offset' => 0,
        ];

        return [
            'redirectTo' => null,
            'view' => [
                'moderationReady' => $moderationReady,
                'replyReady' => $replyReady,
                'errors' => $errors,
                'successMessage' => $successMessage,
                'summary' => $summary,
                'courseOptions' => $courseOptions,
                'courseCards' => $allCourseCards,
                'allCourseCards' => $allCourseCards,
                'visibleReviews' => $visibleReviews,
                'allVisibleReviews' => $visibleReviews,
                'reviews' => $detailRows,
                'ratingDistribution' => $ratingDistribution,
                'reviewPagination' => $reviewPagination,
                'detailRows' => $detailRows,
                'detailMode' => $detailMode,
                'selectedCourseCard' => $selectedCourseCard,
                'filters' => $filters,
                'pagination' => $reviewPagination,
                'legacyPagination' => $reviewPagination,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $get
     * @return array{courseId:int,visibility:string,ratingMin:int,search:string,searchType:string,detail:bool,page:int}
     */
    private function normalizeFilters(array $get): array
    {
        $courseId = max(0, (int) ($get['course_id'] ?? 0));
        $visibility = strtolower(trim((string) ($get['visibility'] ?? 'all')));
        if (!in_array($visibility, ['all', 'visible', 'hidden'], true)) {
            $visibility = 'all';
        }

        $ratingMin = max(0, min(5, (int) ($get['rating_min'] ?? 0)));
        $searchType = strtolower(trim((string) ($get['search_type'] ?? 'all')));
        if (!in_array($searchType, ['all', 'course', 'comment', 'student'], true)) {
            $searchType = 'all';
        }

        $search = trim((string) ($get['q'] ?? ''));
        $searchLength = function_exists('mb_strlen') ? mb_strlen($search) : strlen($search);
        if ($searchLength > 140) {
            $search = function_exists('mb_substr') ? mb_substr($search, 0, 140) : substr($search, 0, 140);
        }
        $search = function_exists('mb_strtolower')
            ? mb_strtolower($search, 'UTF-8')
            : strtolower($search);

        $detailRaw = strtolower(trim((string) ($get['detail'] ?? '')));
        $detail = in_array($detailRaw, ['1', 'true', 'yes', 'oui'], true);
        if ($courseId <= 0) {
            $detail = false;
        }
        $page = max(1, (int) ($get['page'] ?? 1));

        return [
            'courseId' => $courseId,
            'visibility' => $visibility,
            'ratingMin' => $ratingMin,
            'search' => $search,
            'searchType' => $searchType,
            'detail' => $detail,
            'page' => $page,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function applySearchFilter(array $rows, string $search, string $searchType): array
    {
        if ($search === '') {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $row) use ($search, $searchType): bool {
            $courseText = trim((string) ($row['course_title'] ?? ''));
            $commentText = trim((string) ($row['comment'] ?? ''));
            $studentText = trim((string) ($row['reviewer_first_name'] ?? '') . ' ' . (string) ($row['reviewer_last_name'] ?? ''));
            $haystack = match ($searchType) {
                'course' => $courseText,
                'comment' => $commentText,
                'student' => $studentText,
                default => trim($courseText . ' ' . $commentText . ' ' . $studentText),
            };
            $haystack = function_exists('mb_strtolower')
                ? mb_strtolower($haystack, 'UTF-8')
                : strtolower($haystack);
            return str_contains($haystack, $search);
        }));
    }

    /**
     * @param array<int,array<string,mixed>> $courseOptions
     * @return array<int,array<string,mixed>>
     */
    private function filterPublishedCourses(array $courseOptions): array
    {
        return array_values(array_filter(
            $courseOptions,
            static fn (array $course): bool => strtolower(trim((string) ($course['status'] ?? ''))) === 'approved'
        ));
    }

    /**
     * @param array<int,array<string,mixed>> $courseOptions
     * @param array<int,array<string,mixed>> $rows
     * @param array<int,int> $studentCounts
     * @return array<int,array{
     *   course_id:int,
     *   course_title:string,
     *   course_status:string,
     *   review_count:int,
     *   avg_rating:float,
     *   latest_review_at:string,
     *   student_count:int,
     *   deleted_at:string,
     *   is_archived:bool
     * }>
     */
    private function buildCourseCards(array $courseOptions, array $rows, array $studentCounts): array
    {
        $cardsByCourseId = [];
        foreach ($courseOptions as $course) {
            $courseId = max(0, (int) ($course['id'] ?? 0));
            if ($courseId < 1) {
                continue;
            }
            $deletedAt = trim((string) ($course['deleted_at'] ?? ''));
            $cardsByCourseId[$courseId] = [
                'course_id' => $courseId,
                'course_title' => trim((string) ($course['title'] ?? ('Cours #' . $courseId))),
                'course_status' => trim((string) ($course['status'] ?? '')),
                'review_count' => 0,
                'rating_sum' => 0.0,
                'avg_rating' => 0.0,
                'latest_review_at' => '',
                'student_count' => max(0, (int) ($studentCounts[$courseId] ?? 0)),
                'deleted_at' => $deletedAt,
                'is_archived' => $deletedAt !== '',
            ];
        }

        foreach ($rows as $row) {
            $courseId = max(0, (int) ($row['course_id'] ?? 0));
            if ($courseId < 1) {
                continue;
            }
            if (!isset($cardsByCourseId[$courseId])) {
                $deletedAt = trim((string) ($row['course_deleted_at'] ?? ''));
                $cardsByCourseId[$courseId] = [
                    'course_id' => $courseId,
                    'course_title' => trim((string) ($row['course_title'] ?? ('Cours #' . $courseId))),
                    'course_status' => trim((string) ($row['course_status'] ?? '')),
                    'review_count' => 0,
                    'rating_sum' => 0.0,
                    'avg_rating' => 0.0,
                    'latest_review_at' => '',
                    'student_count' => max(0, (int) ($studentCounts[$courseId] ?? 0)),
                    'deleted_at' => $deletedAt,
                    'is_archived' => $deletedAt !== '',
                ];
            }

            $cardsByCourseId[$courseId]['review_count']++;
            $cardsByCourseId[$courseId]['rating_sum'] += (float) ((int) ($row['rating'] ?? 0));
            $createdAt = trim((string) ($row['created_at'] ?? ''));
            if (
                $createdAt !== '' &&
                (
                    $cardsByCourseId[$courseId]['latest_review_at'] === '' ||
                    strtotime($createdAt) > strtotime((string) $cardsByCourseId[$courseId]['latest_review_at'])
                )
            ) {
                $cardsByCourseId[$courseId]['latest_review_at'] = $createdAt;
            }
        }

        $cards = [];
        foreach ($cardsByCourseId as $courseId => $card) {
            $reviewCount = max(0, (int) ($card['review_count'] ?? 0));
            $ratingSum = (float) ($card['rating_sum'] ?? 0.0);
            $card['avg_rating'] = $reviewCount > 0 ? round($ratingSum / $reviewCount, 2) : 0.0;
            unset($card['rating_sum']);
            $cards[] = $card;
        }

        usort($cards, static function (array $a, array $b): int {
            $countCmp = ((int) ($b['review_count'] ?? 0)) <=> ((int) ($a['review_count'] ?? 0));
            if ($countCmp !== 0) {
                return $countCmp;
            }
            $avgCmp = ((float) ($b['avg_rating'] ?? 0.0)) <=> ((float) ($a['avg_rating'] ?? 0.0));
            if ($avgCmp !== 0) {
                return $avgCmp;
            }
            return strcmp((string) ($a['course_title'] ?? ''), (string) ($b['course_title'] ?? ''));
        });

        return $cards;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array{total:int,visible:int,hidden:int,avg:float,reply_count:int,response_rate:int,pending:int}
     */
    private function buildSummary(array $rows): array
    {
        $total = count($rows);
        $hidden = 0;
        $sum = 0.0;
        $replyCount = 0;

        foreach ($rows as $row) {
            $sum += (float) ((int) ($row['rating'] ?? 0));
            if ((int) ($row['is_hidden_by_teacher'] ?? 0) === 1) {
                $hidden++;
            }
            if (trim((string) ($row['teacher_reply_text'] ?? '')) !== '') {
                $replyCount++;
            }
        }

        $avg = $total > 0 ? round($sum / $total, 2) : 0.0;
        $responseRate = $total > 0 ? (int) round(($replyCount / $total) * 100) : 0;

        return [
            'total' => $total,
            'visible' => max(0, $total - $hidden),
            'hidden' => $hidden,
            'avg' => $avg,
            'reply_count' => $replyCount,
            'response_rate' => max(0, min(100, $responseRate)),
            'pending' => max(0, $total - $replyCount),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $cards
     * @return array<string,mixed>|null
     */
    private function findCourseCard(array $cards, int $courseId): ?array
    {
        if ($courseId < 1) {
            return null;
        }
        foreach ($cards as $card) {
            if ((int) ($card['course_id'] ?? 0) === $courseId) {
                return $card;
            }
        }
        return null;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function decorateDetailRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $reviewer = trim((string) ($row['reviewer_first_name'] ?? '') . ' ' . (string) ($row['reviewer_last_name'] ?? ''));
            $row['reviewer_label'] = $reviewer !== '' ? $reviewer : 'Utilisateur';
            $row['reviewer_initials'] = $this->makeInitials($row['reviewer_label']);
            $row['reviewer_avatar_url'] = $this->resolveMediaUrl(trim((string) ($row['reviewer_avatar_url'] ?? '')));
            $row['course_label'] = trim((string) ($row['course_title'] ?? ''));
            if ($row['course_label'] === '') {
                $row['course_label'] = 'Cours';
            }
            $row['course_status_label'] = trim((string) ($row['course_status'] ?? ''));

            $row['created_at_label'] = $this->formatDateLabel((string) ($row['created_at'] ?? ''), false, '-');
            $row['created_date_label'] = $this->formatDateLabel((string) ($row['created_at'] ?? ''), false, '-');

            $row['hidden_at_label'] = $this->formatDateLabel((string) ($row['hidden_at'] ?? ''), true, '');

            $hiddenBy = trim((string) ($row['hidden_by_first_name'] ?? '') . ' ' . (string) ($row['hidden_by_last_name'] ?? ''));
            $row['hidden_by_label'] = $hiddenBy !== '' ? $hiddenBy : '';

            $row['teacher_reply_at_label'] = $this->formatDateLabel((string) ($row['teacher_reply_at'] ?? ''), true, '');

            $replyBy = trim((string) ($row['teacher_reply_first_name'] ?? '') . ' ' . (string) ($row['teacher_reply_last_name'] ?? ''));
            $row['teacher_reply_by_label'] = $replyBy !== '' ? $replyBy : '';
            $row['teacher_reply_text'] = trim((string) ($row['teacher_reply_text'] ?? ''));
            $row['comment'] = trim((string) ($row['comment'] ?? ''));
            $row['rating_value'] = max(1, min(5, (int) ($row['rating'] ?? 0)));
            $row['rating_label'] = (string) $row['rating_value'] . '/5';
            $row['visibility_label'] = (int) ($row['is_hidden_by_teacher'] ?? 0) === 1 ? 'Masqué' : 'Visible';
            $row['is_hidden_by_teacher'] = (int) ($row['is_hidden_by_teacher'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array{courseId:int,visibility:string,ratingMin:int,search:string,searchType:string,detail:bool,page:int} $filters
     */
    private function buildRedirectUrl(array $filters): string
    {
        $query = [];
        if ($filters['courseId'] > 0) {
            $query['course_id'] = (string) $filters['courseId'];
        }
        if ($filters['visibility'] !== 'all') {
            $query['visibility'] = $filters['visibility'];
        }
        if ($filters['ratingMin'] > 0) {
            $query['rating_min'] = (string) $filters['ratingMin'];
        }
        if ($filters['search'] !== '') {
            $query['q'] = $filters['search'];
        }
        if ($filters['searchType'] !== 'all') {
            $query['search_type'] = $filters['searchType'];
        }
        if ($filters['courseId'] > 0) {
            $query['detail'] = '1';
        }
        if ((int) ($filters['page'] ?? 1) > 1) {
            $query['page'] = (string) ((int) $filters['page']);
        }

        $suffix = http_build_query($query);
        return $this->siteUrl('teacher/reviews' . ($suffix !== '' ? ('?' . $suffix) : ''));
    }

    private function formatDateLabel(string $value, bool $withTime = false, string $fallback = '—'): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return $fallback;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return $fallback;
        }

        $frMonths = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ];

        $monthIndex = (int) date('n', $ts);
        $month = (string) ($frMonths[$monthIndex] ?? date('m', $ts));
        $label = date('j', $ts) . ' ' . $month . ' ' . date('Y', $ts);

        if ($withTime) {
            $label .= ' ' . date('H:i', $ts);
        }

        return $label;
    }

    private function makeInitials(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        if ($value === '') {
            return 'ET';
        }

        $parts = preg_split('/\s+/', $value) ?: [];
        $initials = '';
        $added = 0;

        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            $initials .= function_exists('mb_substr')
                ? mb_substr($part, 0, 1, 'UTF-8')
                : substr($part, 0, 1);
            $added++;

            if ($added >= 2) {
                break;
            }
        }

        $initials = strtoupper(trim((string) $initials));
        return $initials !== '' ? $initials : 'ET';
    }

    private function resolveMediaUrl(string $path): string
    {
        $value = trim($path);
        if ($value === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '//') || str_starts_with($value, 'data:')) {
            return $value;
        }

        return $this->siteUrl(ltrim($value, '/'));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array{rating:int,count:int,percent:int,width:int,label:string}>
     */
    private function buildRatingDistribution(array $rows): array
    {
        $counts = array_fill(1, 5, 0);
        foreach ($rows as $row) {
            $rating = max(1, min(5, (int) ($row['rating'] ?? 0)));
            $counts[$rating] = ($counts[$rating] ?? 0) + 1;
        }

        $total = array_sum($counts);
        $distribution = [];
        for ($rating = 5; $rating >= 1; $rating--) {
            $count = (int) ($counts[$rating] ?? 0);
            $percent = $total > 0 ? (int) round(($count / $total) * 100) : 0;
            $distribution[] = [
                'rating' => $rating,
                'count' => $count,
                'percent' => $percent,
                'width' => $percent,
                'label' => $rating . ' ★',
            ];
        }

        return $distribution;
    }
}
