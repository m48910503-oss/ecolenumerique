// assets/js/course_favorites.js
// Favoris de lecture (timestamp + label) synchronises en base, par lecon.

(function () {
  document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const basePath = body.dataset.basePath || '';
    const csrfToken =
      document.querySelector('meta[name="csrf-token"]')?.content || '';

    const card = document.getElementById('main-player-card');
    let currentLessonId = card?.dataset.currentLessonId || '';

    const form = document.getElementById('bookmark-form');
    const list = document.getElementById('bookmark-list');
    const empty = document.getElementById('bookmark-empty');
    const labelInput = document.getElementById('bookmark-label');

    if (!form || !list || !empty) return;

    const notify = (message, type = 'info') => {
      if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
      }
      if (type === 'error') {
        console.error(message);
        return;
      }
      console.log(message);
    };

    const toInt = (value, fallback = 0) => {
      const parsed = parseInt(String(value ?? ''), 10);
      return Number.isFinite(parsed) ? parsed : fallback;
    };

    const formatTime = (seconds) => {
      const safe = Math.max(0, toInt(seconds));
      const hours = Math.floor(safe / 3600);
      const mins = Math.floor((safe % 3600) / 60);
      const secs = safe % 60;
      if (hours > 0) {
        return `${String(hours).padStart(2, '0')}:${String(mins).padStart(
          2,
          '0'
        )}:${String(secs).padStart(2, '0')}`;
      }
      return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    const getCurrentSeconds = () => {
      const api = window.coursePlayerApi;
      if (api && typeof api.getCurrentTime === 'function') {
        return Math.max(0, toInt(api.getCurrentTime(), 0));
      }
      return 0;
    };

    const jumpTo = (seconds) => {
      const api = window.coursePlayerApi;
      if (!api || typeof api.seekTo !== 'function') return false;
      return api.seekTo(Math.max(0, toInt(seconds)), true);
    };

    const render = (bookmarks) => {
      list.innerHTML = '';
      const rows = Array.isArray(bookmarks) ? bookmarks : [];

      rows.forEach((bookmark) => {
        const row = document.createElement('div');
        row.className =
          'rounded-lg border border-slate-800 bg-slate-950/80 px-3 py-2 text-xs';

        const top = document.createElement('div');
        top.className = 'flex items-center justify-between gap-2';

        const time = document.createElement('p');
        time.className = 'font-semibold text-emerald-200';
        time.textContent = formatTime(bookmark.timestamp ?? 0);

        const actions = document.createElement('div');
        actions.className = 'flex items-center gap-2';

        const goBtn = document.createElement('button');
        goBtn.type = 'button';
        goBtn.className =
          'px-2 py-1 rounded border border-slate-700 text-slate-200 hover:border-emerald-400 transition';
        goBtn.textContent = 'Aller';
        goBtn.addEventListener('click', () => {
          const ok = jumpTo(bookmark.timestamp ?? 0);
          if (!ok) notify('Lecteur indisponible pour ce favori.', 'error');
        });

        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className =
          'px-2 py-1 rounded border border-rose-700 text-rose-200 hover:border-rose-500 transition';
        delBtn.textContent = 'Supprimer';
        delBtn.addEventListener('click', () => {
          deleteBookmark(bookmark.id);
        });

        actions.appendChild(goBtn);
        actions.appendChild(delBtn);
        top.appendChild(time);
        top.appendChild(actions);

        row.appendChild(top);

        const label = String(bookmark.label || '').trim();
        if (label !== '') {
          const text = document.createElement('p');
          text.className = 'mt-1 text-slate-300';
          text.textContent = label;
          row.appendChild(text);
        }

        list.appendChild(row);
      });

      empty.classList.toggle('hidden', rows.length > 0);
    };

    const fetchBookmarks = async () => {
      if (!currentLessonId) {
        render([]);
        return;
      }
      try {
        const response = await fetch(
          `${basePath}/api/bookmarks?lesson_id=${encodeURIComponent(
            currentLessonId
          )}`
        );
        const data = await response.json();
        if (!response.ok || !data?.ok) {
          render([]);
          return;
        }
        render(data.bookmarks || []);
      } catch (_) {
        render([]);
      }
    };

    const createBookmark = async (label) => {
      if (!currentLessonId) {
        notify('Le\u00e7on introuvable.', 'error');
        return;
      }
      const payload = new FormData();
      payload.append('action', 'create');
      payload.append('lesson_id', String(currentLessonId));
      payload.append('timestamp', String(getCurrentSeconds()));
      payload.append('label', label);
      payload.append('_csrf', csrfToken);

      try {
        const response = await fetch(`${basePath}/api/bookmarks`, {
          method: 'POST',
          body: payload,
        });
        const data = await response.json();
        if (!response.ok || !data?.ok) {
          notify("Impossible d'ajouter le favori.", 'error');
          return;
        }
        if (labelInput) labelInput.value = '';
        await fetchBookmarks();
        notify('Favori ajout\u00e9.', 'info');
      } catch (_) {
        notify("Erreur r\u00e9seau pendant l'ajout du favori.", 'error');
      }
    };

    const deleteBookmark = async (bookmarkId) => {
      const id = toInt(bookmarkId, 0);
      if (id < 1 || !currentLessonId) return;
      const payload = new FormData();
      payload.append('action', 'delete');
      payload.append('lesson_id', String(currentLessonId));
      payload.append('bookmark_id', String(id));
      payload.append('_csrf', csrfToken);

      try {
        const response = await fetch(`${basePath}/api/bookmarks`, {
          method: 'POST',
          body: payload,
        });
        const data = await response.json();
        if (!response.ok || !data?.ok) {
          notify('Suppression impossible.', 'error');
          return;
        }
        await fetchBookmarks();
        notify('Favori supprim\u00e9.', 'info');
      } catch (_) {
        notify('Erreur r\u00e9seau pendant la suppression.', 'error');
      }
    };

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const label = String(labelInput?.value || '').trim();
      createBookmark(label);
    });

    window.addEventListener('lesson:change', (event) => {
      const lessonId = event?.detail?.lessonId;
      if (!lessonId) return;
      currentLessonId = String(lessonId);
      if (card) {
        card.dataset.currentLessonId = currentLessonId;
      }
      fetchBookmarks();
    });

    fetchBookmarks();
  });
})();
