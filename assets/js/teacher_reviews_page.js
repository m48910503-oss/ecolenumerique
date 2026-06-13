(function () {
  'use strict';

  var data = parseData();
  var overviewView = document.getElementById('overview-view');
  var detailView = document.getElementById('detail-view');
  var detailContent = document.getElementById('detail-content');
  var backBtn = document.getElementById('back-btn');
  var pageTitle = document.getElementById('page-title');
  var detailSubtitle = document.getElementById('detail-subtitle');
  var coursesGrid = document.getElementById('courses-grid');
  var overviewSearchInput = document.getElementById('overview-course-search');
  var scrollRoot = document.querySelector('[data-scroll-top-root]');
  var state = {
    currentCourseId: 0,
    currentFilters: {
      rating: normalizeRatingFilter(data.ratingFilter),
      search: normalizeSearchFilter(data.searchFilter),
    },
    overviewFilter: 'all',
    overviewSearch: '',
    initialCourseId: normalizeCourseId(data.initialCourseId),
  };

  var coursesById = buildCourseIndex(data.courses || []);
  var reviewsByCourse = buildReviewIndex(data.reviews || []);

  function parseData() {
    var container = document.getElementById('teacher-reviews-data');
    if (!(container instanceof HTMLScriptElement)) {
      return {};
    }

    try {
      return JSON.parse(container.textContent || '{}') || {};
    } catch (_error) {
      return {};
    }
  }

  function normalizeCourseId(value) {
    var parsed = parseInt(String(value || '0'), 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
  }

  function normalizeRatingFilter(value) {
    var parsed = parseInt(String(value || '0'), 10);
    if (!Number.isFinite(parsed) || parsed < 1 || parsed > 5) {
      return 'all';
    }
    return String(parsed);
  }

  function normalizeSearchFilter(value) {
    return String(value || '').trim().toLowerCase();
  }

  function buildCourseIndex(courses) {
    var index = {};

    courses.forEach(function (course) {
      var courseId = normalizeCourseId(course && course.course_id);
      if (!courseId) {
        return;
      }

      index[courseId] = {
        courseId: courseId,
        title: String(course.course_title || ('Cours #' + courseId)),
        status: String(course.course_status || ''),
        reviewCount: Math.max(0, parseInt(String(course.review_count || 0), 10) || 0),
        avgRating: Number(course.avg_rating || 0),
        pendingReplyCount: Math.max(0, parseInt(String(course.pending_reply_count || 0), 10) || 0),
        latestReviewAt: String(course.latest_review_at || ''),
        studentCount: Math.max(0, parseInt(String(course.student_count || 0), 10) || 0),
        detailUrl: String(course.detail_url || (data.detailBaseUrl + courseId)),
      };
    });

    return index;
  }

  function buildReviewIndex(reviews) {
    var index = {};

    reviews.forEach(function (review) {
      var courseId = normalizeCourseId(review && review.course_id);
      if (!courseId) {
        return;
      }

      if (!index[courseId]) {
        index[courseId] = [];
      }

      index[courseId].push({
        reviewId: normalizeCourseId(review.review_id),
        courseId: courseId,
        courseTitle: String(review.course_title || ''),
        reviewerLabel: String(review.reviewer_label || 'Utilisateur'),
        reviewerInitials: String(review.reviewer_initials || 'ET'),
        reviewerAvatarUrl: String(review.reviewer_avatar_url || ''),
        createdAtLabel: String(review.created_at_label || '—'),
        ratingValue: Math.max(1, Math.min(5, parseInt(String(review.rating_value || 0), 10) || 0)),
        comment: String(review.comment || ''),
        teacherReplyText: String(review.teacher_reply_text || ''),
        teacherReplyAtLabel: String(review.teacher_reply_at_label || ''),
        teacherReplyByLabel: String(review.teacher_reply_by_label || ''),
        searchBlob: String(review.search_blob || '').toLowerCase(),
      });
    });

    return index;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function nl2br(value) {
    return escapeHtml(value).replace(/\r\n|\r|\n/g, '<br>');
  }

  function formatNumber(value) {
    var parsed = Number(value);
    if (!Number.isFinite(parsed)) {
      parsed = 0;
    }
    return new Intl.NumberFormat('fr-FR').format(parsed);
  }

  function renderStars(rating, wrapperClass) {
    var safeRating = Math.max(0, Math.min(5, Number(rating) || 0));
    var fullStars = Math.floor(safeRating);
    var hasHalf = safeRating - fullStars >= 0.5 && fullStars < 5;
    var html = '<span class="' + escapeHtml(wrapperClass || 'text-yellow-400 text-sm') + '">';

    for (var i = 1; i <= 5; i += 1) {
      if (i <= fullStars) {
        html += '<i class="fas fa-star" aria-hidden="true"></i>';
        continue;
      }

      if (hasHalf && i === fullStars + 1) {
        html += '<i class="fas fa-star-half-stroke" aria-hidden="true"></i>';
        continue;
      }

      html += '<i class="far fa-star text-gray-300" aria-hidden="true"></i>';
    }

    html += '</span>';
    return html;
  }

  function buildRatingDistribution(reviews) {
    var counts = [0, 0, 0, 0, 0, 0];
    reviews.forEach(function (review) {
      var rating = Math.max(1, Math.min(5, parseInt(String(review.ratingValue || 0), 10) || 0));
      counts[rating] += 1;
    });

    var total = reviews.length;
    var buckets = [];
    for (var rating = 5; rating >= 1; rating -= 1) {
      var count = counts[rating];
      var percent = total > 0 ? Math.round((count / total) * 100) : 0;
      buckets.push({
        rating: rating,
        count: count,
        percent: percent,
      });
    }
    return buckets;
  }

  function renderReviewCard(review, course) {
    var hasReply = String(review.teacherReplyText || '').trim() !== '';
    var replyFormId = 'review-reply-' + review.reviewId;
    var searchBlob = normalizeSearchFilter(review.reviewerLabel + ' ' + review.courseTitle + ' ' + review.comment);
    var courseTitle = review.courseTitle || (course ? course.title : 'Cours');
    var html = '';

    html += '<article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800"';
    html += ' data-review-card';
    html += ' data-review-rating="' + String(review.ratingValue || 0) + '"';
    html += ' data-review-search-text="' + escapeHtml(searchBlob) + '">';
    html += '<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_14rem]">';
    html += '<div class="min-w-0">';
    html += '<div class="flex items-start gap-3">';
    if (String(review.reviewerAvatarUrl || '').trim() !== '') {
      html += '<img src="' + escapeHtml(review.reviewerAvatarUrl) + '" alt="" class="w-10 h-10 rounded-full object-cover">';
    } else {
      html += '<div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-600 to-accent-500 flex items-center justify-center text-white text-xs font-bold">';
      html += escapeHtml(review.reviewerInitials || 'ET');
      html += '</div>';
    }
    html += '<div class="min-w-0">';
    html += '<p class="font-semibold text-sm text-gray-900 dark:text-white truncate">' + escapeHtml(review.reviewerLabel || 'Utilisateur') + '</p>';
    html += '<p class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-700/60 dark:text-slate-200">' + escapeHtml(courseTitle) + '</p>';
    html += '</div>';
    html += '</div>';

    html += '<div class="mt-3 flex text-yellow-400 text-sm">' + renderStars(review.ratingValue, 'text-yellow-400 text-sm') + '</div>';
    html += '<p class="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300">' + (review.comment ? nl2br(review.comment) : '<span class="text-gray-400">Aucun commentaire.</span>') + '</p>';

    if (hasReply) {
      html += '<div class="mt-4 rounded-xl border border-primary-100 bg-primary-50/70 p-3 dark:border-primary-500/30 dark:bg-primary-500/10">';
      html += '<div class="flex flex-wrap items-center justify-between gap-2">';
      html += '<p class="text-xs font-semibold text-primary-700 dark:text-primary-200">Votre réponse</p>';
      if (String(review.teacherReplyAtLabel || '').trim() !== '') {
        html += '<span class="text-[11px] text-gray-500 dark:text-gray-400">';
        html += escapeHtml(review.teacherReplyAtLabel);
        if (String(review.teacherReplyByLabel || '').trim() !== '') {
          html += ' · ' + escapeHtml(review.teacherReplyByLabel);
        }
        html += '</span>';
      }
      html += '</div>';
      html += '<p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-300">' + nl2br(review.teacherReplyText) + '</p>';
      html += '</div>';
    }

    html += '</div>';
    html += '<aside class="flex flex-col gap-3 border-t border-gray-100 pt-4 text-sm dark:border-gray-700 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">';
    html += '<div><p class="text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Date</p>';
    html += '<p class="mt-1 font-medium text-gray-800 dark:text-gray-100">' + escapeHtml(review.createdAtLabel || '—') + '</p></div>';
    html += '<div><p class="text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Statut</p>';
    if (hasReply) {
      html += '<span class="mt-1 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">Répondu</span>';
    } else {
      html += '<span class="mt-1 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">Non répondu</span>';
    }
    html += '</div>';
    html += '<div class="mt-auto flex flex-wrap gap-2">';
    if (!hasReply) {
      html += '<button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-700 reply-btn" data-review-reply-toggle aria-controls="' + escapeHtml(replyFormId) + '" aria-expanded="false">';
      html += '<i class="fas fa-reply" aria-hidden="true"></i> Répondre';
      html += '</button>';
    }
    html += '<details class="review-action-menu">';
    html += '<summary class="inline-flex cursor-pointer list-none items-center justify-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700/60">Actions</summary>';
    html += '<div class="review-action-menu__list">';
    if (data.moderationReady) {
      html += '<form method="post">';
      html += '<input type="hidden" name="_csrf" value="' + escapeHtml(data.csrfToken || '') + '">';
      html += '<input type="hidden" name="action" value="hide_review">';
      html += '<input type="hidden" name="review_id" value="' + String(review.reviewId || 0) + '">';
      html += '<input type="hidden" name="course_id" value="' + String(review.courseId || 0) + '">';
      html += '<button type="submit" class="review-action-menu__item text-rose-600 dark:text-rose-300">';
      html += '<i class="fas fa-eye-slash" aria-hidden="true"></i> Masquer l’avis';
      html += '</button>';
      html += '</form>';
    } else {
      html += '<span class="review-action-menu__item text-gray-400"><i class="fas fa-eye-slash" aria-hidden="true"></i> Masquage indisponible</span>';
    }
    html += '</div></details>';
    html += '</div></aside>';
    html += '</div>';

    if (!hasReply) {
      html += '<form method="post" id="' + escapeHtml(replyFormId) + '" class="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-3 hidden reply-form dark:border-gray-700 dark:bg-gray-700/30" data-review-reply-form>';
      html += '<input type="hidden" name="_csrf" value="' + escapeHtml(data.csrfToken || '') + '">';
      html += '<input type="hidden" name="action" value="save_reply">';
      html += '<input type="hidden" name="review_id" value="' + String(review.reviewId || 0) + '">';
      html += '<input type="hidden" name="course_id" value="' + String(review.courseId || 0) + '">';
      html += '<textarea class="review-reply-textarea px-3 py-2 text-xs" name="reply_text" rows="2" placeholder="Votre réponse..."></textarea>';
      html += '<div class="flex flex-wrap justify-end gap-2 mt-2">';
      html += '<button type="button" class="text-xs text-gray-500 cancel-reply" data-review-reply-cancel>Annuler</button>';
      if (data.replyReady) {
        html += '<button type="submit" class="text-xs bg-primary-600 text-white px-3 py-1 rounded-lg">Envoyer</button>';
      } else {
        html += '<span class="text-xs text-gray-400">Réponses indisponibles</span>';
      }
      html += '</div></form>';
    }

    html += '</article>';
    return html;
  }

  function renderDetailMarkup(course, reviews) {
    var avgRating = Number(course.avgRating || 0);
    var pendingReplyCount = reviews.filter(function (review) {
      return String(review.teacherReplyText || '').trim() === '';
    }).length;
    var latestReviewLabel = reviews.length ? String(reviews[0].createdAtLabel || 'Aucun avis') : 'Aucun avis';
    var distribution = buildRatingDistribution(reviews);
    var ratingValue = state.currentFilters.rating;
    var searchValue = state.currentFilters.search;

    var html = '';
    html += '<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">';
    html += '<div class="review-detail-surface rounded-2xl p-5 shadow-card border">';
    html += '<p class="text-sm text-gray-500 dark:text-gray-400">Note moyenne</p>';
    html += '<div class="flex items-center gap-1 mt-2">';
    html += '<span class="text-3xl font-extrabold text-gray-900 dark:text-white">' + avgRating.toFixed(1) + '</span>';
    html += '<span class="text-yellow-400 text-xl" aria-hidden="true">★</span>';
    html += '</div>';
    html += '<div class="mt-1 text-sm">' + renderStars(avgRating, 'text-yellow-400 text-sm') + '</div>';
    html += '</div>';
    html += '<div class="review-detail-surface rounded-2xl p-5 shadow-card border">';
    html += '<p class="text-sm text-gray-500 dark:text-gray-400">Nombre d’avis</p>';
    html += '<p class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">' + formatNumber(reviews.length) + '</p>';
    html += '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">' + (reviews.length === 1 ? 'avis reçu' : 'avis reçus') + '</p>';
    html += '</div>';
    html += '<div class="review-detail-surface rounded-2xl p-5 shadow-card border">';
    html += '<p class="text-sm text-gray-500 dark:text-gray-400">Avis sans réponse</p>';
    html += '<p class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">' + formatNumber(pendingReplyCount) + '</p>';
    html += '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">' + (pendingReplyCount > 0 ? 'à traiter en priorité' : 'tout est traité') + '</p>';
    html += '</div>';
    html += '<div class="review-detail-surface rounded-2xl p-5 shadow-card border">';
    html += '<p class="text-sm text-gray-500 dark:text-gray-400">Dernier avis reçu</p>';
    html += '<p class="mt-2 text-lg font-bold text-gray-900 dark:text-white">' + escapeHtml(latestReviewLabel || 'Aucun avis') + '</p>';
    html += '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">historique du cours</p>';
    html += '</div>';
    html += '</div>';

    html += '<div class="review-detail-surface rounded-2xl p-6 shadow-card border mb-6">';
    html += '<h4 class="font-semibold mb-3 text-gray-900 dark:text-white">Distribution des notes</h4>';
    html += '<div class="space-y-2">';
    distribution.forEach(function (bucket) {
      html += '<div class="flex items-center gap-2">';
      html += '<span class="text-xs w-8 text-gray-600 dark:text-gray-300">' + bucket.rating + ' ★</span>';
      html += '<div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">';
      html += '<div class="bg-yellow-400 h-2.5 rounded-full" style="width: ' + bucket.percent + '%"></div>';
      html += '</div>';
      html += '<span class="text-xs text-gray-500 w-10 text-right">' + bucket.percent + '%</span>';
      html += '</div>';
    });
    html += '</div></div>';

    html += '<div class="flex flex-wrap gap-4 items-center mb-4">';
    html += '<select id="rating-filter" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm">';
    html += '<option value="all"' + (ratingValue === 'all' ? ' selected' : '') + '>Toutes les notes</option>';
    html += '<option value="5"' + (ratingValue === '5' ? ' selected' : '') + '>5 étoiles</option>';
    html += '<option value="4"' + (ratingValue === '4' ? ' selected' : '') + '>4 étoiles</option>';
    html += '<option value="3"' + (ratingValue === '3' ? ' selected' : '') + '>3 étoiles</option>';
    html += '<option value="2"' + (ratingValue === '2' ? ' selected' : '') + '>2 étoiles</option>';
    html += '<option value="1"' + (ratingValue === '1' ? ' selected' : '') + '>1 étoile</option>';
    html += '</select>';
    html += '<input type="text" id="search-review" value="' + escapeHtml(searchValue) + '" placeholder="Rechercher un avis..." class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm w-64">';
    html += '</div>';

    html += '<div class="max-w-5xl" id="reviews-list">';
    if (!reviews.length) {
      html += '<p class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-review-empty-state>Aucun avis visible pour le moment.</p>';
      html += '</div>';
      return html;
    }

    html += '<p class="hidden rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-review-empty-state>Aucun avis trouvé.</p>';
    html += '<div class="space-y-4">';
    reviews.forEach(function (review) {
      html += renderReviewCard(review, course);
    });
    html += '</div></div>';
    return html;
  }

  function findCourse(courseId) {
    return coursesById[normalizeCourseId(courseId)] || null;
  }

  function setView(detailVisible) {
    if (!(overviewView instanceof HTMLElement) || !(detailView instanceof HTMLElement)) {
      return;
    }

    overviewView.classList.toggle('hidden', detailVisible);
    detailView.classList.toggle('hidden', !detailVisible);
    if (backBtn instanceof HTMLElement) {
      backBtn.classList.toggle('hidden', !detailVisible);
    }
  }

  function scrollToTop() {
    if (scrollRoot instanceof HTMLElement) {
      scrollRoot.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function updateHistory(view, courseId) {
    if (!window.history || typeof window.history.pushState !== 'function') {
      return;
    }

    if (view === 'overview') {
      window.history.pushState({ view: 'overview' }, '', data.overviewUrl || window.location.pathname);
      return;
    }

    var course = findCourse(courseId);
    if (!course) {
      return;
    }

    window.history.pushState(
      { view: 'detail', courseId: course.courseId },
      '',
      course.detailUrl || (data.detailBaseUrl + course.courseId)
    );
  }

  function renderDetail(courseId, options) {
    var course = findCourse(courseId);
    if (!course || !(detailContent instanceof HTMLElement)) {
      return;
    }

    state.currentCourseId = course.courseId;
    if (!options || options.preserveFilters !== true) {
      state.currentFilters = { rating: 'all', search: '' };
    }

    var reviews = reviewsByCourse[course.courseId] || [];
    detailContent.innerHTML = renderDetailMarkup(course, reviews);
    setView(true);

    if (pageTitle instanceof HTMLElement) {
      pageTitle.textContent = 'Avis du cours : ' + course.title;
    }

    if (detailSubtitle instanceof HTMLElement) {
      detailSubtitle.textContent = formatNumber(reviews.length) + ' avis reçu' + (reviews.length > 1 ? 's' : '') + ' — note moyenne ' + Number(course.avgRating || 0).toFixed(1) + '/5.';
    }

    if (backBtn instanceof HTMLElement) {
      backBtn.classList.remove('hidden');
    }

    if (!options || options.pushState !== false) {
      updateHistory('detail', course.courseId);
    }

    applyFilters();

    if (!options || options.scroll !== false) {
      scrollToTop();
    }
  }

  function renderOverview(options) {
    state.currentCourseId = 0;
    setView(false);
    if (pageTitle instanceof HTMLElement) {
      pageTitle.textContent = 'Avis & Notes';
    }
    if (detailSubtitle instanceof HTMLElement) {
      detailSubtitle.textContent = 'Sélectionnez un cours pour consulter ses avis.';
    }

    if (!options || options.pushState !== false) {
      updateHistory('overview');
    }

    if (!options || options.scroll !== false) {
      scrollToTop();
    }
  }

  function applyFilters() {
    if (!(detailContent instanceof HTMLElement)) {
      return;
    }

    var ratingFilter = detailContent.querySelector('#rating-filter');
    var searchInput = detailContent.querySelector('#search-review');
    var emptyState = detailContent.querySelector('[data-review-empty-state]');
    var cards = detailContent.querySelectorAll('[data-review-card]');

    if (!(ratingFilter instanceof HTMLSelectElement) || !(searchInput instanceof HTMLInputElement)) {
      return;
    }

    state.currentFilters = {
      rating: ratingFilter.value === '' ? 'all' : ratingFilter.value,
      search: normalizeSearchFilter(searchInput.value),
    };

    var visibleCount = 0;
    cards.forEach(function (card) {
      if (!(card instanceof HTMLElement)) {
        return;
      }

      var rating = parseInt(card.dataset.reviewRating || '0', 10) || 0;
      var blob = normalizeSearchFilter(card.dataset.reviewSearchText || '');
      var ratingMatch = state.currentFilters.rating === 'all' || rating === parseInt(state.currentFilters.rating, 10);
      var searchMatch = state.currentFilters.search === '' || blob.indexOf(state.currentFilters.search) !== -1;
      var showCard = ratingMatch && searchMatch;

      card.classList.toggle('hidden', !showCard);
      if (showCard) {
        visibleCount += 1;
      }
    });

    if (emptyState instanceof HTMLElement) {
      emptyState.classList.toggle('hidden', visibleCount > 0);
      if (visibleCount === 0) {
        emptyState.textContent = cards.length > 0 ? 'Aucun avis trouvé.' : 'Aucun avis visible pour le moment.';
      }
    }
  }

  function applyOverviewFilters() {
    if (!(coursesGrid instanceof HTMLElement)) {
      return;
    }

    var cards = coursesGrid.querySelectorAll('[data-course-card]');
    var sections = coursesGrid.querySelectorAll('[data-course-section]');
    var activeFilter = state.overviewFilter || 'all';
    var search = normalizeSearchFilter(state.overviewSearch || '');

    cards.forEach(function (card) {
      if (!(card instanceof HTMLElement)) {
        return;
      }

      var reviewCount = parseInt(card.dataset.courseReviewCount || '0', 10) || 0;
      var pendingReplies = parseInt(card.dataset.coursePendingReplies || '0', 10) || 0;
      var avgRating = Number(card.dataset.courseAvgRating || '0') || 0;
      var blob = normalizeSearchFilter(card.dataset.courseSearchText || '');
      var filterMatch = true;

      if (activeFilter === 'reviewed') {
        filterMatch = reviewCount > 0;
      } else if (activeFilter === 'no_reviews') {
        filterMatch = reviewCount === 0;
      } else if (activeFilter === 'unanswered') {
        filterMatch = pendingReplies > 0;
      } else if (activeFilter === 'low_rating') {
        filterMatch = reviewCount > 0 && avgRating > 0 && avgRating < 4;
      }

      var searchMatch = search === '' || blob.indexOf(search) !== -1;
      card.classList.toggle('hidden', !(filterMatch && searchMatch));
    });

    sections.forEach(function (section) {
      if (!(section instanceof HTMLElement)) {
        return;
      }

      var sectionCards = section.querySelectorAll('[data-course-card]');
      var visibleCards = 0;
      sectionCards.forEach(function (card) {
        if (card instanceof HTMLElement && !card.classList.contains('hidden')) {
          visibleCards += 1;
        }
      });

      var filteredEmpty = section.querySelector('[data-section-empty-filter]');
      if (filteredEmpty instanceof HTMLElement) {
        filteredEmpty.classList.toggle('hidden', visibleCards > 0 || sectionCards.length === 0);
      }
    });
  }

  function toggleReplyForm(button, open) {
    if (!(button instanceof HTMLElement)) {
      return;
    }

    var card = button.closest('[data-review-card]');
    if (!(card instanceof HTMLElement)) {
      return;
    }

    var form = card.querySelector('[data-review-reply-form]');
    if (!(form instanceof HTMLElement)) {
      return;
    }

    var shouldOpen = typeof open === 'boolean' ? open : form.classList.contains('hidden');
    form.classList.toggle('hidden', !shouldOpen);
    button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

    if (shouldOpen) {
      var textarea = form.querySelector('textarea');
      if (textarea instanceof HTMLTextAreaElement) {
        window.setTimeout(function () {
          textarea.focus({ preventScroll: true });
        }, 40);
      }
    }
  }

  function closeReplyForm(button) {
    if (!(button instanceof HTMLElement)) {
      return;
    }

    var card = button.closest('[data-review-card]');
    if (!(card instanceof HTMLElement)) {
      return;
    }

    var form = card.querySelector('[data-review-reply-form]');
    var toggleButton = card.querySelector('[data-review-reply-toggle]');
    if (form instanceof HTMLElement) {
      form.classList.add('hidden');
    }
    if (toggleButton instanceof HTMLElement) {
      toggleButton.setAttribute('aria-expanded', 'false');
    }
  }

  function bindStaticEvents() {
    if (coursesGrid instanceof HTMLElement) {
      coursesGrid.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
          return;
        }

        var button = target.closest('.view-reviews-btn');
        if (!(button instanceof HTMLElement)) {
          return;
        }

        event.preventDefault();
        var courseCard = button.closest('[data-course-card]');
        var fallbackCourseId = '';
        if (courseCard instanceof HTMLElement) {
          fallbackCourseId = courseCard.dataset.courseId || '';
        }
        var courseId = normalizeCourseId(button.dataset.courseId || fallbackCourseId);
        if (!courseId) {
          return;
        }
        renderDetail(courseId, { pushState: true, preserveFilters: false });
      });
    }

    document.querySelectorAll('[data-review-course-filter]').forEach(function (button) {
      if (!(button instanceof HTMLButtonElement)) {
        return;
      }

      button.addEventListener('click', function () {
        state.overviewFilter = String(button.dataset.reviewCourseFilter || 'all');
        document.querySelectorAll('[data-review-course-filter]').forEach(function (candidate) {
          if (candidate instanceof HTMLElement) {
            candidate.classList.toggle('is-active', candidate === button);
          }
        });
        applyOverviewFilters();
      });
    });

    if (overviewSearchInput instanceof HTMLInputElement) {
      overviewSearchInput.addEventListener('input', function () {
        state.overviewSearch = overviewSearchInput.value;
        applyOverviewFilters();
      });
    }

    if (backBtn instanceof HTMLElement) {
      backBtn.addEventListener('click', function (event) {
        event.preventDefault();
        renderOverview({ pushState: true });
      });
    }

    if (detailContent instanceof HTMLElement) {
      detailContent.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
          return;
        }

        var replyToggle = target.closest('[data-review-reply-toggle]');
        if (replyToggle instanceof HTMLElement) {
          event.preventDefault();
          toggleReplyForm(replyToggle);
          return;
        }

        var replyCancel = target.closest('[data-review-reply-cancel]');
        if (replyCancel instanceof HTMLElement) {
          event.preventDefault();
          closeReplyForm(replyCancel);
        }
      });

      detailContent.addEventListener('input', function (event) {
        var target = event.target;
        if (target instanceof HTMLInputElement && target.id === 'search-review') {
          applyFilters();
        }
      });

      detailContent.addEventListener('change', function (event) {
        var target = event.target;
        if (target instanceof HTMLSelectElement && target.id === 'rating-filter') {
          applyFilters();
        }
      });
    }

    window.addEventListener('popstate', function (event) {
      var nextState = event.state || {};
      if (nextState.view === 'detail' && normalizeCourseId(nextState.courseId) > 0) {
        renderDetail(normalizeCourseId(nextState.courseId), { pushState: false, preserveFilters: false, scroll: false });
        return;
      }

      renderOverview({ pushState: false, scroll: false });
    });
  }

  function initialize() {
    bindStaticEvents();
    applyOverviewFilters();

    var params = new URLSearchParams(window.location.search || '');
    var locationCourseId = normalizeCourseId(params.get('course_id'));
    var initialCourseId = locationCourseId || state.initialCourseId;
    var initialCourse = findCourse(initialCourseId);
    var shouldShowDetail = initialCourseId > 0 && initialCourse && (params.has('detail') || state.initialCourseId > 0 || locationCourseId > 0);

    if (initialCourseId > 0 && !initialCourse) {
      renderOverview({ pushState: false, scroll: false });
      window.history.replaceState({ view: 'overview' }, '', data.overviewUrl || window.location.pathname);
      return;
    }

    if (shouldShowDetail) {
      renderDetail(initialCourseId, { pushState: false, preserveFilters: true, scroll: false });
      window.history.replaceState({ view: 'detail', courseId: initialCourseId }, '', window.location.href);
      return;
    }

    renderOverview({ pushState: false, scroll: false });
    window.history.replaceState({ view: 'overview' }, '', window.location.href);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
  } else {
    initialize();
  }
})();
