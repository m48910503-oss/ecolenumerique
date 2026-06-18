// assets/js/course_progress.js
// Gestion de la progression du cours :
// - mise à jour temps réel de la barre "Progression" pendant la vidéo
// - enregistrement en BDD quand une leçon est complétée
// - mise à jour des badges "Vu" dans la playlist.

(function () {
  const body = document.body;
  const basePath = body.dataset.basePath || '';
  const courseId = body.dataset.courseId || null;
  const csrf =
    document.querySelector('meta[name="csrf-token"]')?.content || '';

  if (!courseId) return;

  const totalLessons = Math.max(0, parseInt(body.dataset.totalLessons || '0', 10) || 0);

  function clampDone(value) {
    const parsed = Math.max(0, parseInt(value || '0', 10) || 0);
    return totalLessons > 0 ? Math.min(parsed, totalLessons) : 0;
  }

  function clampPercent(value) {
    return Math.max(0, Math.min(100, parseFloat(value || 0) || 0));
  }

  let doneLessons = clampDone(body.dataset.doneLessons || '0');
  let baseProgress = clampPercent(body.dataset.baseProgress || '0');

  const perLessonPct = totalLessons > 0 ? 100 / totalLessons : 0;
  let currentProgress = baseProgress;

  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));

  function updateProgressCount() {
    const counter = document.getElementById('progress-counter');
    if (counter) counter.textContent = `${doneLessons} / ${totalLessons}`;
  }

  function progressSummaryText() {
    return `${doneLessons} / ${totalLessons}`;
  }

  function getCurrentLessonId() {
    const active =
      document.querySelector('.queue-item.active') ||
      document.querySelector('.queue-item.border-emerald-400');

    if (active && active.dataset.lessonId) {
      return active.dataset.lessonId;
    }

    const btn = document.getElementById('btn-mark');
    return btn ? btn.dataset.lessonId || null : null;
  }

  function updateProgressUI(percent) {
    percent = parseFloat(percent || 0);

    if (percent < currentProgress) {
      percent = currentProgress;
    }
    currentProgress = percent;

    const bar = $('#progress-bar');
    const span = $('#progress-percent');
    const label = $('#progress-label');
    const sidebarBar = $('#sidebar-progress-bar');
    const sidebarLabel = $('#sidebar-progress');
    const completionBadge = $('#course-completion-badge');
    const completionPanel = $('#course-completion-panel');
    const progressState = $('#course-progress-state');
    const roundedPercent = Math.max(0, Math.min(100, Math.floor(percent)));
    const summary = progressSummaryText();
    const complete = totalLessons > 0 && doneLessons >= totalLessons;

    if (bar) bar.style.width = `${percent}%`;
    if (span) span.textContent = `${roundedPercent}% · ${summary}`;
    if (label) label.textContent = `${roundedPercent}% complété`;
    if (sidebarBar) sidebarBar.style.width = `${percent}%`;
    if (sidebarLabel) sidebarLabel.textContent = `${roundedPercent}% · ${summary}`;
    if (completionBadge) completionBadge.classList.toggle('hidden', !complete);
    if (completionPanel) completionPanel.classList.toggle('hidden', !complete);
    if (progressState) {
      progressState.innerHTML = complete
        ? '<i class="fas fa-check" aria-hidden="true"></i> Cours terminé'
        : '<i class="fas fa-play" aria-hidden="true"></i> En cours';
      progressState.classList.toggle('border-emerald-200', complete);
      progressState.classList.toggle('bg-emerald-50', complete);
      progressState.classList.toggle('text-emerald-700', complete);
      progressState.classList.toggle('dark:border-emerald-900/50', complete);
      progressState.classList.toggle('dark:bg-emerald-900/25', complete);
      progressState.classList.toggle('dark:text-emerald-200', complete);
      progressState.classList.toggle('border-primary-200', !complete);
      progressState.classList.toggle('bg-primary-50', !complete);
      progressState.classList.toggle('text-primary-700', !complete);
      progressState.classList.toggle('dark:border-primary-900/50', !complete);
      progressState.classList.toggle('dark:bg-primary-900/25', !complete);
      progressState.classList.toggle('dark:text-primary-200', !complete);
    }

    body.dataset.courseCompleted = complete ? '1' : '0';
    body.dataset.currentProgress = String(roundedPercent);
    window.dispatchEvent(new CustomEvent('course:progressChanged', {
      detail: {
        courseId: courseId,
        percent: roundedPercent,
        rawPercent: percent,
        doneLessons: doneLessons,
        totalLessons: totalLessons,
      },
    }));
  }

  function markLessonLocallyAsDone(lessonId) {
    if (!lessonId) return;

    const btnTop = document.getElementById('btn-mark');
    if (btnTop) {
      const label = 'Marquée comme vue';
      const labelNode = btnTop.querySelector('[data-mark-label]');
      btnTop.setAttribute('aria-label', label);
      btnTop.setAttribute('title', label);
      btnTop.dataset.done = '1';
      if (labelNode) {
        labelNode.textContent = label;
      } else {
        btnTop.textContent = label;
      }
      btnTop.classList.remove('text-gray-600', 'dark:text-gray-400');
      btnTop.classList.add('text-emerald-600', 'dark:text-emerald-300');
    }

    const btnBottom = document.querySelector('[data-action="mark-done"]');
    if (btnBottom) {
      btnBottom.textContent = 'Marquée comme vue';
    }

    const item = document.querySelector(
      `.queue-item[data-lesson-id="${lessonId}"]`
    );
    const wasDone = item && item.dataset.done === '1';
    if (item) {
      item.dataset.done = '1';
      const chip = item.querySelector('[data-lesson-status-chip]');
      const icon = item.querySelector('[data-lesson-status-icon]');
      const title = item.querySelector('[data-lesson-title-node]');
      if (icon && !item.classList.contains('active')) {
        icon.className = 'fas fa-check text-[10px] text-white';
      }
      if (chip && !item.classList.contains('active')) {
        chip.className =
          'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-green-500 bg-green-500 text-white dark:border-green-500 dark:bg-green-500 dark:text-white';
      }
      if (title && !item.classList.contains('active')) {
        title.classList.remove('font-medium', 'text-primary-700', 'dark:text-primary-300');
        title.classList.add('text-gray-900', 'dark:text-white');
      }
    }

    if (!wasDone) {
      doneLessons = Math.min(totalLessons, doneLessons + 1);
      updateProgressCount();
    }
  }

  async function sendCompletion(lessonId) {
    if (!lessonId) return;
    const currentItem = document.querySelector(
      `.queue-item[data-lesson-id="${lessonId}"]`
    );
    const btnTop = document.getElementById('btn-mark');
    if (
      (currentItem && currentItem.dataset.done === '1') ||
      (btnTop && btnTop.dataset.done === '1')
    ) {
      updateProgressUI(baseProgress);
      updateProgressCount();
      return;
    }

    try {
      const res = await fetch(basePath + '/api/lesson_complete', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf,
        },
        body: JSON.stringify({
          course_id: parseInt(courseId, 10),
          lesson_id: parseInt(lessonId, 10),
        }),
      });

      const data = await res.json();

      if (!res.ok || data.status !== 'ok') {
        console.error('Progress error', data);
        const msg =
          data.message ||
          "Impossible d'enregistrer la progression pour cette leçon.";
        if (window.showToast) window.showToast(msg, 'error');
        else alert(msg);
        return;
      }

      markLessonLocallyAsDone(lessonId);

      if (typeof data.progress_percent !== 'undefined') {
        baseProgress = clampPercent(data.progress_percent);
        doneLessons = clampDone(data.done_count ?? doneLessons);
        currentProgress = baseProgress;
        updateProgressUI(baseProgress);
        updateProgressCount();
      }
    } catch (e) {
      console.error(e);
      const msg = "Erreur lors de l'enregistrement de la progression.";
      if (window.showToast) window.showToast(msg, 'error');
      else alert(msg);
    }
  }

  // Clic sur "Marquer comme vu" (haut)
  const btnTop = $('#btn-mark');
  if (btnTop) {
    btnTop.addEventListener('click', () => {
      const lessonId = getCurrentLessonId();
      if (!lessonId) return;
      sendCompletion(lessonId);
    });
  }

  // Clic sur le bouton bas "Marquer comme vu"
  const btnBottom = document.querySelector('[data-action="mark-done"]');
  if (btnBottom) {
    btnBottom.addEventListener('click', (e) => {
      e.preventDefault(); // course_player gère le lock et simule le clic top
      const lessonId = getCurrentLessonId();
      if (!lessonId) return;
      sendCompletion(lessonId);
    });
  }

  // Quand on change de vidéo dans la playlist, on met à jour le data-lesson-id
  $$('.queue-item').forEach((item) => {
    item.addEventListener('click', () => {
      const lessonId = item.dataset.lessonId;
      if (!lessonId) return;
      const btn = document.getElementById('btn-mark');
      if (btn) btn.dataset.lessonId = lessonId;
    });
  });

  // ➜ Progression temps réel envoyée par course_player.js
  window.addEventListener('lesson:watchProgress', (e) => {
    if (!totalLessons) return;

    const ratio = Math.max(0, Math.min(1, e.detail?.ratio ?? 0)); // 0 → 1

    const currentItem =
      document.querySelector('.queue-item.active') ||
      document.querySelector('.queue-item.border-emerald-400');
    const isDone = currentItem && currentItem.dataset.done === '1';

    let percent = baseProgress;

    if (!isDone && perLessonPct > 0) {
      percent =
        doneLessons * perLessonPct + ratio * perLessonPct;
    }

    updateProgressUI(percent);
  });

  // Au chargement, on affiche la progression de base
  updateProgressUI(baseProgress);
  updateProgressCount();
})();
