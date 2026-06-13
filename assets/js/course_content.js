(() => {
  function byId(id) {
    return document.getElementById(id);
  }

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function extractYoutubeId(url) {
    if (!url) {
      return '';
    }
    const clean = String(url).trim();
    const match = clean.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i);
    return match ? match[1] : '';
  }

  const modalRestoreTargets = new WeakMap();

  function courseModals() {
    return Array.from(document.querySelectorAll('[data-course-modal]'));
  }

  function syncBodyScrollLock() {
    const hasOpenModal = courseModals().some((modal) => modal.getAttribute('aria-hidden') === 'false');
    document.body.classList.toggle('overflow-hidden', hasOpenModal);
  }

  function getFirstFocusable(container) {
    if (!container) {
      return null;
    }
    return container.querySelector('button:not([disabled]), [href], input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
  }

  function openCourseModal(modal, options = {}) {
    if (!modal) {
      return;
    }
    const trigger = options.trigger instanceof HTMLElement
      ? options.trigger
      : (document.activeElement instanceof HTMLElement ? document.activeElement : null);
    modalRestoreTargets.set(modal, trigger);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    modal.removeAttribute('inert');
    syncBodyScrollLock();

    const focusTarget = options.focusTarget instanceof HTMLElement ? options.focusTarget : getFirstFocusable(modal);
    if (focusTarget) {
      window.requestAnimationFrame(() => {
        focusTarget.focus();
      });
    }
  }

  function closeCourseModal(modal, options = {}) {
    if (!modal) {
      return;
    }

    const activeElement = document.activeElement;
    if (activeElement instanceof HTMLElement && modal.contains(activeElement)) {
      activeElement.blur();
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
    modal.setAttribute('inert', '');
    syncBodyScrollLock();

    if (options.restoreFocus === false) {
      modalRestoreTargets.delete(modal);
      return;
    }

    const restoreTarget = modalRestoreTargets.get(modal);
    modalRestoreTargets.delete(modal);
    if (restoreTarget instanceof HTMLElement && document.contains(restoreTarget)) {
      window.requestAnimationFrame(() => {
        restoreTarget.focus();
      });
    }
  }

  function initModalState() {
    courseModals().forEach((modal) => {
      if (modal.getAttribute('aria-hidden') === 'false') {
        modal.removeAttribute('inert');
      } else {
        modal.setAttribute('inert', '');
      }
    });
    syncBodyScrollLock();
  }

  function resolveFormAction(form) {
    if (!(form instanceof HTMLFormElement)) {
      return window.location.href;
    }
    const action = String(form.getAttribute('action') || '').trim();
    if (!action) {
      return window.location.href;
    }
    try {
      return new URL(action, window.location.href).toString();
    } catch (_e) {
      return window.location.href;
    }
  }

  function initTabs() {
    const buttons = Array.from(document.querySelectorAll('.tab-button[data-tab], [data-tab-button][data-tab]'));
    if (!buttons.length) {
      return;
    }

    const root = document.querySelector('[data-course-content-root]');
    const courseId = root?.dataset?.courseId || '';
    const storageKey = courseId ? `course_content_active_tab_${courseId}` : '';

    function panelFor(tabName) {
      return byId(`tab-${tabName}`) || document.querySelector(`[data-tab-panel="${tabName}"]`);
    }

    function show(tabName) {
      buttons.forEach((btn) => {
        const isActive = btn.dataset.tab === tabName;
        btn.classList.toggle('border-emerald-500', isActive);
        btn.classList.toggle('text-emerald-300', isActive);
      });
      buttons.forEach((btn) => {
        const panel = panelFor(btn.dataset.tab || '');
        if (!panel) {
          return;
        }
        panel.classList.toggle('hidden', btn.dataset.tab !== tabName);
      });
      if (storageKey) {
        try {
          localStorage.setItem(storageKey, tabName);
        } catch (_e) {
          // ignore
        }
      }
    }

    let initial = root?.dataset?.initialTab || '';
    if (!initial && storageKey) {
      try {
        initial = localStorage.getItem(storageKey) || '';
      } catch (_e) {
        initial = '';
      }
    }
    if (!initial && buttons.length) {
      initial = buttons[0].dataset.tab || 'chapter';
    }
    show(initial || 'chapter');

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        show(btn.dataset.tab || 'chapter');
      });
    });
  }

  function initCourseSteps() {
    const root = document.querySelector('[data-course-content-root]');
    if (!root) {
      return;
    }

    const buttons = Array.from(document.querySelectorAll('[data-course-step-button]'));
    const panels = Array.from(document.querySelectorAll('[data-course-step-panel]'));
    if (!buttons.length || !panels.length) {
      return;
    }

    const panelNames = panels
      .map((panel) => String(panel.getAttribute('data-course-step-panel') || '').trim())
      .filter(Boolean);
    const validSteps = new Set(panelNames);
    const courseId = root.dataset.courseId || '';
    const storageKey = courseId ? `course_content_step_${courseId}` : '';

    function setButtonActiveState(button, isActive) {
      button.classList.toggle('opacity-75', !isActive);
      button.classList.toggle('opacity-100', isActive);
      const spans = button.querySelectorAll('span');
      const badge = spans[0] instanceof HTMLElement ? spans[0] : null;
      const label = spans[1] instanceof HTMLElement ? spans[1] : null;

      if (badge) {
        badge.classList.toggle('bg-blue-600', isActive);
        badge.classList.toggle('text-white', isActive);
        badge.classList.toggle('shadow-lg', isActive);
        badge.classList.toggle('shadow-blue-900/30', isActive);
        badge.classList.toggle('bg-slate-700', !isActive);
        badge.classList.toggle('text-slate-300', !isActive);
      }

      if (label) {
        label.classList.toggle('font-semibold', isActive);
        label.classList.toggle('text-blue-300', isActive);
        label.classList.toggle('font-medium', !isActive);
        label.classList.toggle('text-slate-400', !isActive);
      }
    }

    function show(stepName, options = {}) {
      const nextStep = validSteps.has(stepName) ? stepName : panelNames[0];
      panels.forEach((panel) => {
        const active = panel.getAttribute('data-course-step-panel') === nextStep;
        panel.classList.toggle('hidden', !active);
      });
      buttons.forEach((button) => {
        setButtonActiveState(button, button.getAttribute('data-course-step-button') === nextStep);
      });
      if (storageKey) {
        try {
          localStorage.setItem(storageKey, nextStep);
        } catch (_e) {
          // ignore storage failures
        }
      }
      if (options.scroll !== false) {
        root.scrollIntoView({ block: 'start', behavior: 'smooth' });
      }
    }

    let initial = root.dataset.courseStepDefault || 'content';
    let urlStep = '';
    try {
      urlStep = new URLSearchParams(window.location.search).get('step') || '';
    } catch (_e) {
      urlStep = '';
    }
    if (urlStep && validSteps.has(urlStep)) {
      initial = urlStep;
    } else if (storageKey) {
      try {
        initial = localStorage.getItem(storageKey) || initial;
      } catch (_e) {
        initial = root.dataset.courseStepDefault || 'content';
      }
    }
    if (!validSteps.has(initial)) {
      initial = panelNames[0];
    }
    show(initial, { scroll: false });

    buttons.forEach((button) => {
      button.addEventListener('click', () => {
        show(String(button.getAttribute('data-course-step-button') || 'content'));
      });
    });

    document.querySelectorAll('[data-course-step-next], [data-course-step-prev]').forEach((control) => {
      control.addEventListener('click', () => {
        const next = control.getAttribute('data-course-step-next') || control.getAttribute('data-course-step-prev') || '';
        if (!next) {
          return;
        }
        show(next);
      });
    });
  }

  function initConfirmModal() {
    const modal = byId('content-confirm-modal');
    if (!modal) {
      return;
    }

    const titleEl = byId('content-confirm-modal-title');
    const messageEl = byId('content-confirm-modal-message');
    const confirmBtn = byId('content-confirm-modal-confirm');
    const cancelBtn = byId('content-confirm-modal-cancel');
    if (!titleEl || !messageEl || !confirmBtn || !cancelBtn) {
      return;
    }

    const variantClass = {
      danger: 'app-btn-danger',
      success: 'app-btn-success',
      default: 'app-btn-primary',
    };

    let pending = null;

    function close() {
      pending = null;
      closeCourseModal(modal);
      confirmBtn.className = 'px-4 py-2 rounded-lg font-semibold transition app-btn-primary';
      confirmBtn.textContent = 'Confirmer';
    }

    function open(cfg, trigger) {
      pending = cfg;
      titleEl.textContent = cfg.title || 'Confirmation';
      messageEl.textContent = cfg.message || 'Confirmez-vous cette action ?';
      confirmBtn.textContent = cfg.label || 'Confirmer';
      const variant = variantClass[cfg.variant] ? cfg.variant : 'default';
      confirmBtn.className = `px-4 py-2 rounded-lg font-semibold transition ${variantClass[variant]}`;
      openCourseModal(modal, { trigger, focusTarget: confirmBtn });
    }

    function runPending() {
      if (!pending) {
        close();
        return;
      }
      const current = pending;
      close();

      if (current.type === 'link' && current.href) {
        window.location.assign(current.href);
        return;
      }

      if (current.type === 'form' && current.form) {
        if (typeof current.form.requestSubmit === 'function' && current.submitter) {
          current.form.requestSubmit(current.submitter);
        } else {
          current.form.submit();
        }
      }
    }

    document.addEventListener('click', (event) => {
      const closeBtn = event.target.closest('[data-modal-close]');
      if (closeBtn) {
        event.preventDefault();
        close();
        return;
      }

      if (event.target === modal) {
        close();
        return;
      }

      const trigger = event.target.closest('[data-confirm-action]');
      if (!trigger) {
        return;
      }
      if (trigger.disabled) {
        return;
      }

      event.preventDefault();
      const action = trigger.getAttribute('data-confirm-action') || 'form';
      const cfg = {
        title: trigger.getAttribute('data-confirm-title') || 'Confirmation',
        message: trigger.getAttribute('data-confirm-message') || 'Confirmez-vous cette action ?',
        label: trigger.getAttribute('data-confirm-label') || 'Confirmer',
        variant: trigger.getAttribute('data-confirm-variant') || 'default',
      };

      if (action === 'link') {
        open({ ...cfg, type: 'link', href: trigger.getAttribute('href') || '' }, trigger);
        return;
      }

      const form = trigger.closest('form');
      if (!form) {
        return;
      }
      open({ ...cfg, type: 'form', form, submitter: trigger }, trigger);
    });

    confirmBtn.addEventListener('click', (event) => {
      event.preventDefault();
      runPending();
    });
    cancelBtn.addEventListener('click', (event) => {
      event.preventDefault();
      close();
    });
    document.addEventListener('keydown', (event) => {
      if (modal.classList.contains('hidden')) {
        return;
      }
      if (event.key === 'Escape') {
        close();
      }
    });
  }

  function initEditModals() {
    const modalIds = [
      'edit-chapter-modal',
      'edit-lesson-modal',
      'edit-doc-modal',
      'edit-meeting-modal',
      'add-lesson-modal',
      'add-doc-modal',
      'add-meeting-modal',
    ];

    function openModal(id, trigger) {
      const modal = byId(id);
      if (!modal) {
        return;
      }
      openCourseModal(modal, { trigger });
    }

    function closeModal(id) {
      const modal = byId(id);
      if (!modal) {
        return;
      }
      closeCourseModal(modal);
    }

    function resetForm(form) {
      if (!form) {
        return;
      }
      form.reset();
      form.querySelectorAll('[data-live-validate]').forEach((field) => {
        field.setCustomValidity('');
        const targetId = field.getAttribute('data-error-target');
        if (!targetId) {
          return;
        }
        const target = byId(targetId);
        if (target) {
          target.textContent = '';
        }
      });
    }

    function setChapterContext(fieldId, labelId, chapterId, chapterTitle) {
      const field = byId(fieldId);
      const label = byId(labelId);
      const currentValue = field ? String(field.value || "").trim() : "";
      const defaultValue = field && "defaultValue" in field ? String(field.defaultValue || "").trim() : "";
      const currentLabel = field ? String(field.dataset.chapterLabel || "").trim() : "";
      const defaultLabel = field ? String(field.dataset.defaultChapterLabel || "").trim() : "";
      const nextValue = chapterId || currentValue;
      let nextLabel = chapterTitle || "";
      if (!nextLabel && nextValue && defaultValue && nextValue === defaultValue && defaultLabel) {
        nextLabel = defaultLabel;
      }
      if (!nextLabel) {
        nextLabel = currentLabel || defaultLabel || (label ? String(label.textContent || "").trim() : "");
      }
      if (field) {
        field.value = nextValue;
        field.dataset.chapterLabel = nextLabel;
        field.dispatchEvent(new Event('change', { bubbles: true }));
      }
      if (label) {
        label.textContent = nextLabel || 'Chapitre non defini';
      }
    }

    function ensureHiddenChapterValue(fieldId, labelId) {
      const field = byId(fieldId);
      if (!(field instanceof HTMLInputElement)) {
        return '';
      }
      const currentValue = String(field.value || '').trim();
      if (currentValue !== '') {
        return currentValue;
      }
      const defaultValue = String(field.defaultValue || '').trim();
      const defaultLabel = String(field.dataset.defaultChapterLabel || '').trim();
      if (defaultValue !== '') {
        setChapterContext(fieldId, labelId, defaultValue, defaultLabel);
        return String(field.value || '').trim();
      }
      return '';
    }

    function ensureSelectHasValue(select) {
      if (!(select instanceof HTMLSelectElement)) {
        return '';
      }
      const currentValue = String(select.value || '').trim();
      if (currentValue !== '') {
        return currentValue;
      }
      const fallbackOption = Array.from(select.options).find((option) => {
        return String(option.value || '').trim() !== '' && !option.disabled && !option.hidden;
      });
      if (!fallbackOption) {
        return '';
      }
      select.value = String(fallbackOption.value || '').trim();
      return String(select.value || '').trim();
    }

    function syncMeetingChapterLabel() {
      const field = byId('meeting_chapter_id');
      const label = byId('meeting_chapter_label');
      if (!(field instanceof HTMLSelectElement) || !label) {
        return;
      }
      ensureSelectHasValue(field);
      const selected = field.options[field.selectedIndex] || null;
      const text = selected && selected.value
        ? String(selected.textContent || '').trim()
        : 'Selectionnez un chapitre depuis le plan.';
      field.dataset.chapterLabel = selected && selected.value
        ? String(selected.textContent || '').trim()
        : '';
      label.textContent = text;
    }

    document.querySelectorAll('[data-close-modal]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-close-modal');
        if (id) {
          closeModal(id);
        }
      });
    });

    modalIds.forEach((id) => {
      const modal = byId(id);
      if (!modal) {
        return;
      }
      modal.addEventListener('click', (event) => {
        if (event.target === modal) {
          closeModal(id);
        }
      });
    });

    const meetingChapterField = byId('meeting_chapter_id');
    if (meetingChapterField) {
      meetingChapterField.addEventListener('change', syncMeetingChapterLabel);
      syncMeetingChapterLabel();
    }

    const addMeetingForm = byId('form-add-meeting');
    if (addMeetingForm) {
      addMeetingForm.addEventListener('submit', () => {
        const field = byId('meeting_chapter_id');
        if (!(field instanceof HTMLSelectElement)) {
          return;
        }
        ensureSelectHasValue(field);
        syncMeetingChapterLabel();
      });
    }

    const addLessonForm = byId('form-add-lesson');
    if (addLessonForm) {
      addLessonForm.addEventListener('submit', () => {
        ensureHiddenChapterValue('lesson_chapter_id', 'lesson_chapter_label');
      });
    }

    const addDocForm = byId('form-add-doc');
    if (addDocForm) {
      addDocForm.addEventListener('submit', () => {
        ensureHiddenChapterValue('doc_chapter_id', 'doc_chapter_label');
      });
    }

    document.querySelectorAll('[data-open-edit]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const type = btn.getAttribute('data-open-edit');
        if (type === 'chapter') {
          const id = byId('edit_chapter_id');
          const title = byId('edit_chapter_title');
          const desc = byId('edit_chapter_description');
          const position = byId('edit_chapter_position');
          if (id) id.value = btn.getAttribute('data-chapter-id') || '';
          if (title) title.value = btn.getAttribute('data-chapter-title') || '';
          if (desc) desc.value = btn.getAttribute('data-chapter-description') || '';
          if (position) position.value = btn.getAttribute('data-chapter-position') || '';
          openModal('edit-chapter-modal', btn);
          return;
        }

        if (type === 'lesson') {
          const id = byId('edit_lesson_id');
          const title = byId('edit_lesson_title');
          const desc = byId('edit_lesson_description');
          const position = byId('edit_lesson_position');
          const dur = byId('edit_lesson_duration');
          const yt = byId('edit_lesson_youtube_url');
          if (id) id.value = btn.getAttribute('data-lesson-id') || '';
          if (title) title.value = btn.getAttribute('data-lesson-title') || '';
          if (desc) desc.value = btn.getAttribute('data-lesson-description') || '';
          if (position) position.value = btn.getAttribute('data-lesson-position') || '';
          if (dur) dur.value = btn.getAttribute('data-lesson-duration') || '1';
          if (yt) yt.value = btn.getAttribute('data-lesson-youtube-url') || '';
          openModal('edit-lesson-modal', btn);
          return;
        }

        if (type === 'doc') {
          const id = byId('edit_doc_id');
          const title = byId('edit_doc_title');
          const docType = byId('edit_doc_type');
          const scope = byId('edit_doc_scope');
          const chapter = byId('edit_doc_chapter_id');
          const lesson = byId('edit_doc_lesson_id');
          const position = byId('edit_doc_position');
          const currentFileWrap = byId('edit_doc_current_file_wrap');
          const currentFile = byId('edit_doc_current_file');
          const currentFilePath = btn.getAttribute('data-doc-file-path') || '';
          if (id) id.value = btn.getAttribute('data-doc-id') || '';
          if (title) title.value = btn.getAttribute('data-doc-title') || '';
          if (docType) docType.value = btn.getAttribute('data-doc-type') || 'document';
          if (scope) scope.value = btn.getAttribute('data-doc-scope') || 'chapter';
          if (chapter) chapter.value = btn.getAttribute('data-doc-chapter-id') || '';
          if (lesson) lesson.value = btn.getAttribute('data-doc-lesson-id') || '';
          if (position) position.value = btn.getAttribute('data-doc-position') || '';
          if (currentFile) {
            currentFile.href = currentFilePath || '#';
          }
          if (currentFileWrap) {
            currentFileWrap.classList.toggle('hidden', currentFilePath === '');
          }
          if (typeof window.applyDocSelectVisibility === 'function') {
            window.applyDocSelectVisibility('edit_');
          }
          openModal('edit-doc-modal', btn);
          return;
        }

        if (type === 'meeting') {
          const id = byId('edit_meeting_id');
          const title = byId('edit_meeting_title');
          const mode = byId('edit_meeting_mode');
          const duration = byId('edit_meeting_duration_min');
          const chapter = byId('edit_meeting_chapter_id');
          if (id) id.value = btn.getAttribute('data-meeting-id') || '';
          if (title) title.value = btn.getAttribute('data-meeting-title') || '';
          if (mode) mode.value = btn.getAttribute('data-meeting-mode') || 'online';
          if (duration) duration.value = btn.getAttribute('data-meeting-duration') || '60';
          if (chapter) chapter.value = btn.getAttribute('data-meeting-chapter-id') || '';
          openModal('edit-meeting-modal', btn);
        }
      });
    });

    document.querySelectorAll('[data-open-add]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const type = btn.getAttribute('data-open-add');
        const chapterId = btn.getAttribute('data-chapter-id') || '';
        const chapterTitle = btn.getAttribute('data-chapter-title') || '';

        if (type === 'lesson') {
          const form = byId('form-add-lesson');
          resetForm(form);
          setChapterContext('lesson_chapter_id', 'lesson_chapter_label', chapterId, chapterTitle);
          const youtube = byId('lesson_youtube_url');
          if (youtube) {
            youtube.dispatchEvent(new Event('input', { bubbles: true }));
          }
          openModal('add-lesson-modal', btn);
          return;
        }

        if (type === 'doc') {
          const form = byId('form-add-doc');
          resetForm(form);
          const scope = byId('doc_scope');
          if (scope) {
            scope.value = 'chapter';
          }
          setChapterContext('doc_chapter_id', 'doc_chapter_label', chapterId, chapterTitle);
          const lesson = byId('doc_lesson_id');
          if (lesson) {
            lesson.value = '';
          }
          if (typeof window.applyDocSelectVisibility === 'function') {
            window.applyDocSelectVisibility('');
          }
          openModal('add-doc-modal', btn);
          return;
        }

        if (type === 'meeting') {
          const form = byId('form-add-meeting');
          resetForm(form);
          setChapterContext('meeting_chapter_id', 'meeting_chapter_label', chapterId, chapterTitle);
          openModal('add-meeting-modal', btn);
        }
      });
    });
  }

  function initDocBindings() {
    function filterLessonOptions(chapterSelect, lessonSelect) {
      if (!chapterSelect || !lessonSelect) {
        return;
      }
      const chapterId = chapterSelect.value || '';
      Array.from(lessonSelect.options).forEach((opt) => {
        if (!opt.value) {
          return;
        }
        const belongs = opt.dataset.chapterId === chapterId;
        opt.hidden = !belongs;
        opt.disabled = !belongs;
      });
      if (lessonSelect.value) {
        const current = lessonSelect.querySelector(`option[value="${lessonSelect.value}"]`);
        if (!current || current.disabled) {
          lessonSelect.value = '';
        }
      }
    }

    function apply(prefix) {
      const type = byId(`${prefix}doc_type`);
      const scope = byId(`${prefix}doc_scope`);
      const scopeWrapper = byId(`${prefix}doc_scope_wrapper`);
      const chapter = byId(`${prefix}doc_chapter_id`);
      const lesson = byId(`${prefix}doc_lesson_id`);
      const wrapper = byId(`${prefix}doc_lesson_wrapper`);
      if (!type || !scope) {
        return;
      }

      const allowsLessonScope = type.value === 'file';
      if (!allowsLessonScope && scope.value !== 'chapter') {
        scope.value = 'chapter';
      }
      const lessonOption = scope.querySelector('option[value="lesson"]');
      if (lessonOption) {
        lessonOption.disabled = !allowsLessonScope;
      }
      if (scopeWrapper) {
        scopeWrapper.classList.toggle('hidden', !allowsLessonScope);
      }
      scope.required = allowsLessonScope;
      scope.disabled = !allowsLessonScope;

      const isLessonScope = allowsLessonScope && scope.value === 'lesson';
      if (wrapper) {
        wrapper.classList.toggle('hidden', !isLessonScope);
      }
      if (lesson) {
        if (!isLessonScope) {
          lesson.value = '';
        }
        filterLessonOptions(chapter, lesson);
        lesson.required = isLessonScope;
        lesson.disabled = !isLessonScope || !(chapter?.value || '');
      }
    }

    function bind(prefix) {
      const type = byId(`${prefix}doc_type`);
      const scope = byId(`${prefix}doc_scope`);
      const chapter = byId(`${prefix}doc_chapter_id`);
      const lesson = byId(`${prefix}doc_lesson_id`);
      if (!type || !scope) {
        return;
      }
      const refresh = () => apply(prefix);
      type.addEventListener('change', refresh);
      scope.addEventListener('change', refresh);
      if (chapter) {
        chapter.addEventListener('change', refresh);
      }
      if (lesson) {
        lesson.addEventListener('change', refresh);
      }
      refresh();
    }

    window.applyDocSelectVisibility = apply;
    bind('');
    bind('edit_');
  }

  function initLiveValidation() {
    const fields = Array.from(document.querySelectorAll('[data-live-validate]'));
    if (!fields.length) {
      return;
    }

    function setError(field, message) {
      field.setCustomValidity(message || '');
      const targetId = field.getAttribute('data-error-target');
      if (!targetId) {
        return;
      }
      const target = byId(targetId);
      if (target) {
        target.textContent = message || '';
      }
    }

    function validate(field) {
      const key = field.getAttribute('data-live-validate') || field.id || field.name || '';
      const value = String(field.value || '').trim();
      let error = '';

      if (field.required && value === '') {
        error = 'Ce champ est obligatoire.';
      }

      if (!error && ['chapter_title', 'lesson_title', 'edit_chapter_title', 'edit_lesson_title', 'doc_title', 'edit_doc_title', 'meeting_title', 'edit_meeting_title'].includes(key) && value.length < 3) {
        error = 'Minimum 3 caract\u00e8res.';
      }

      if (!error && ['lesson_duration', 'edit_lesson_duration', 'meeting_duration_min', 'edit_meeting_duration_min'].includes(key)) {
        const n = Number(value);
        if (!Number.isFinite(n) || n < 1) {
          error = 'Valeur minimale: 1.';
        }
      }

      if (!error && ['youtube_url', 'edit_lesson_youtube_url'].includes(key) && value !== '' && !extractYoutubeId(value)) {
        error = 'Lien YouTube invalide.';
      }

      if (!error && key === 'doc_file' && field.required) {
        if (!field.files || field.files.length === 0) {
          error = 'Le fichier est requis.';
        }
      }

      setError(field, error);
      return !error;
    }

    fields.forEach((field) => {
      const handler = () => validate(field);
      field.addEventListener('input', handler);
      field.addEventListener('change', handler);
      validate(field);
    });

    document.querySelectorAll('form').forEach((form) => {
      form.addEventListener('submit', (event) => {
        const scoped = Array.from(form.querySelectorAll('[data-live-validate]'));
        let ok = true;
        scoped.forEach((field) => {
          if (!validate(field)) {
            ok = false;
          }
        });
        if (!ok) {
          event.preventDefault();
        }
      });
    });
  }

  function initYoutubePreview() {
    const input = byId('lesson_youtube_url');
    const wrap = byId('youtube-preview-wrap');
    const thumb = byId('youtube-preview-thumb');
    const link = byId('youtube-preview-link');
    if (!input || !wrap || !thumb || !link) {
      return;
    }

    function refresh() {
      const id = extractYoutubeId(input.value || '');
      if (!id) {
        wrap.classList.add('hidden');
        thumb.removeAttribute('src');
        link.setAttribute('href', '#');
        return;
      }
      wrap.classList.remove('hidden');
      thumb.src = `https://i.ytimg.com/vi/${id}/hqdefault.jpg`;
      link.href = `https://www.youtube.com/watch?v=${id}`;
    }

    input.addEventListener('input', refresh);
    input.addEventListener('change', refresh);
    refresh();
  }

  function initAccordion() {
    const chapters = Array.from(document.querySelectorAll('[data-sort-chapter-id]'));
    if (!chapters.length) {
      return;
    }

    function setState(chapter, open) {
      const body = chapter.querySelector('[data-accordion-body]');
      const toggle = chapter.querySelector('[data-accordion-toggle]');
      if (!body || !toggle) {
        return;
      }
      body.classList.toggle('hidden', !open);
      toggle.textContent = open ? 'Fermer' : 'Ouvrir';
    }

    const toggleAll = byId('toggle-all-chapters');
    const labelOpen = toggleAll ? toggleAll.dataset.openLabel || 'Tout ouvrir' : 'Tout ouvrir';
    const labelClose = toggleAll ? toggleAll.dataset.closeLabel || 'Tout fermer' : 'Tout fermer';

    function areAllOpen() {
      return chapters.every((chapter) => {
        const body = chapter.querySelector('[data-accordion-body]');
        return body && !body.classList.contains('hidden');
      });
    }

    function updateToggleAll() {
      if (!toggleAll) {
        return;
      }
      const label = areAllOpen() ? labelClose : labelOpen;
      const labelEl = toggleAll.querySelector('[data-toggle-label]');
      if (labelEl) {
        labelEl.textContent = label;
      } else {
        toggleAll.textContent = label;
      }
    }

    chapters.forEach((chapter) => {
      const toggle = chapter.querySelector('[data-accordion-toggle]');
      if (!toggle) {
        return;
      }
      toggle.addEventListener('click', () => {
        const body = chapter.querySelector('[data-accordion-body]');
        setState(chapter, body ? body.classList.contains('hidden') : false);
        updateToggleAll();
      });
    });

    if (toggleAll) {
      toggleAll.addEventListener('click', () => {
        const open = !areAllOpen();
        chapters.forEach((chapter) => setState(chapter, open));
        updateToggleAll();
      });
      updateToggleAll();
    }
  }

  function initPlanSearch() {
    const input = byId('plan-search');
    const empty = byId('plan-empty-state');
    const chapters = Array.from(document.querySelectorAll('[data-sort-chapter-id]'));
    if (!input || !chapters.length) {
      return;
    }

    const run = () => {
      const term = String(input.value || '').trim().toLowerCase();
      let visible = 0;
      chapters.forEach((chapter) => {
        const text = (chapter.getAttribute('data-plan-text') || chapter.textContent || '').toLowerCase();
        const show = term === '' || text.includes(term);
        chapter.classList.toggle('hidden', !show);
        if (show) {
          visible += 1;
        }
      });
      if (empty) {
        empty.classList.toggle('hidden', visible > 0);
      }
    };

    input.addEventListener('input', run);
    run();
  }

  function enableDragSort(container, selector, onSorted) {
    if (!container) {
      return;
    }
    let dragged = null;

    container.addEventListener('dragstart', (event) => {
      const item = event.target.closest(selector);
      if (!item || item.getAttribute('draggable') !== 'true') {
        return;
      }
      dragged = item;
      item.classList.add('opacity-50');
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
      }
    });

    container.addEventListener('dragend', () => {
      if (dragged) {
        dragged.classList.remove('opacity-50');
      }
      dragged = null;
      onSorted();
    });

    container.addEventListener('dragover', (event) => {
      if (!dragged) {
        return;
      }
      const target = event.target.closest(selector);
      if (!target || target === dragged) {
        return;
      }
      event.preventDefault();
      const rect = target.getBoundingClientRect();
      const after = event.clientY - rect.top > rect.height / 2;
      if (after) {
        target.after(dragged);
      } else {
        target.before(dragged);
      }
    });

    container.addEventListener('drop', (event) => {
      if (!dragged) {
        return;
      }
      event.preventDefault();
      onSorted();
    });
  }

  function initDragAndReorder() {
    const chapterContainer = byId('chapters-sortable');
    const chapterInput = byId('chapter-order-input');
    const chapterSave = byId('save-chapter-order-btn');

    if (chapterContainer && chapterInput) {
      const refreshChapterOrder = () => {
        const ids = Array.from(chapterContainer.querySelectorAll('[data-sort-chapter-id]'))
          .map((el) => el.getAttribute('data-sort-chapter-id'))
          .filter(Boolean);
        chapterInput.value = ids.join(',');
        if (chapterSave) {
          chapterSave.disabled = false;
        }
      };
      enableDragSort(chapterContainer, '[data-sort-chapter-id]', refreshChapterOrder);
    }

    document.querySelectorAll('[data-sort-lessons-chapter]').forEach((list) => {
      const inputId = list.getAttribute('data-order-input') || '';
      const input = inputId ? byId(inputId) : null;
      const form = list.closest('[data-lesson-order-form]') || list.parentElement?.querySelector('[data-lesson-order-form]');
      const save = form ? form.querySelector('[data-lesson-order-submit]') : null;
      if (!input) {
        return;
      }

      const refresh = () => {
        const ids = Array.from(list.querySelectorAll('[data-sort-lesson-id]'))
          .map((el) => el.getAttribute('data-sort-lesson-id'))
          .filter(Boolean);
        input.value = ids.join(',');
        if (save) {
          save.disabled = false;
        }
      };

      enableDragSort(list, '[data-sort-lesson-id]', refresh);
    });
  }

  function initAutosave() {
    const root = document.querySelector('[data-course-content-root]');
    if (!root) {
      return;
    }
    const courseId = root.dataset.courseId || '';
    if (!courseId) {
      return;
    }

    const forms = Array.from(document.querySelectorAll('[data-autosave-form]'));
    if (!forms.length) {
      return;
    }

    const status = byId('course-content-autosave-status');
    const banner = byId('course-content-restore-banner');
    const restoreBtn = byId('course-content-restore-btn');
    const discardBtn = byId('course-content-discard-btn');

    function keyFor(scope) {
      return `course_content_${courseId}_${scope}`;
    }

    function serialize(form) {
      const out = {};
      Array.from(form.elements).forEach((el) => {
        if (!el.name || el.disabled) {
          return;
        }
        if (el.type === 'file' || el.type === 'submit' || el.type === 'button') {
          return;
        }
        if (el.name === '_csrf' || el.name === 'action') {
          return;
        }
        if (el.type === 'checkbox') {
          out[el.name] = !!el.checked;
          return;
        }
        out[el.name] = el.value;
      });
      return out;
    }

    function hasData(data) {
      return Object.values(data).some((value) => {
        if (typeof value === 'boolean') {
          return value;
        }
        return String(value || '').trim() !== '';
      });
    }

    function save(form) {
      const scope = form.getAttribute('data-autosave-form') || '';
      if (!scope) {
        return;
      }
      const data = serialize(form);
      try {
        if (hasData(data)) {
          localStorage.setItem(keyFor(scope), JSON.stringify(data));
        } else {
          localStorage.removeItem(keyFor(scope));
        }
        if (status) {
          status.classList.remove('hidden');
          status.textContent = `Brouillon local enregistr\u00e9 (${new Date().toLocaleTimeString()}).`;
        }
      } catch (_e) {
        // ignore
      }
    }

    function restore(form) {
      const scope = form.getAttribute('data-autosave-form') || '';
      if (!scope) {
        return;
      }
      let raw = null;
      try {
        raw = localStorage.getItem(keyFor(scope));
      } catch (_e) {
        raw = null;
      }
      if (!raw) {
        return;
      }
      let parsed = {};
      try {
        parsed = JSON.parse(raw);
      } catch (_e) {
        parsed = {};
      }
      Array.from(form.elements).forEach((el) => {
        if (!el.name || !(el.name in parsed)) {
          return;
        }
        if (el.type === 'checkbox') {
          el.checked = !!parsed[el.name];
        } else if (el.type !== 'file' && el.type !== 'submit' && el.type !== 'button') {
          el.value = parsed[el.name];
        }
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }

    let hasDraft = false;
    forms.forEach((form) => {
      const scope = form.getAttribute('data-autosave-form') || '';
      if (!scope) {
        return;
      }
      try {
        if (localStorage.getItem(keyFor(scope))) {
          hasDraft = true;
        }
      } catch (_e) {
        // ignore
      }

      const saveHandler = () => save(form);
      form.addEventListener('input', saveHandler);
      form.addEventListener('change', saveHandler);
      form.addEventListener('submit', () => {
        try {
          localStorage.removeItem(keyFor(scope));
        } catch (_e) {
          // ignore
        }
      });
    });

    if (banner) {
      banner.classList.toggle('hidden', !hasDraft);
    }

    if (restoreBtn) {
      restoreBtn.addEventListener('click', () => {
        forms.forEach((form) => restore(form));
        if (banner) {
          banner.classList.add('hidden');
        }
      });
    }

    if (discardBtn) {
      discardBtn.addEventListener('click', () => {
        forms.forEach((form) => {
          const scope = form.getAttribute('data-autosave-form') || '';
          if (!scope) {
            return;
          }
          try {
            localStorage.removeItem(keyFor(scope));
          } catch (_e) {
            // ignore
          }
        });
        if (banner) {
          banner.classList.add('hidden');
        }
      });
    }
  }

  function initRealtimePreview() {
    const card = document.querySelector('[data-course-live-preview]');
    const payloadEl = byId('course-content-preview-data');
    if (!card || !payloadEl) {
      return;
    }

    let baseState = null;
    try {
      baseState = JSON.parse(payloadEl.textContent || '{}');
    } catch (_e) {
      baseState = null;
    }
    if (!baseState || typeof baseState !== 'object') {
      return;
    }

    const titleEl = byId('course-preview-title');
    const descriptionEl = byId('course-preview-description');
    const outlineEl = byId('course-preview-outline');
    const outlineCountEl = byId('course-preview-outline-count');
    const noteEl = byId('course-preview-note');
    const statEls = {
      chapters: byId('course-preview-stat-chapters'),
      lessons: byId('course-preview-stat-lessons'),
      resources: byId('course-preview-stat-resources'),
      meetings: byId('course-preview-stat-meetings'),
      exams: byId('course-preview-stat-exams'),
    };

    if (!titleEl || !descriptionEl || !outlineEl || !outlineCountEl || !noteEl) {
      return;
    }

    function cloneState() {
      return JSON.parse(JSON.stringify(baseState));
    }

    function cleanText(value) {
      return String(value || '').trim();
    }

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function modalIsOpen(id) {
      const modal = byId(id);
      return !!modal && !modal.classList.contains('hidden');
    }

    function chapterLabelFromSelect(selectId, targetValue) {
      const select = byId(selectId);
      if (!select || !targetValue) {
        return '';
      }
      if (select.dataset && select.dataset.chapterLabel) {
        return cleanText(select.dataset.chapterLabel);
      }
      const options = Array.from(select.options || []);
      const match = options.find((option) => option.value === String(targetValue));
      return cleanText(match ? match.textContent : '');
    }

    function ensureChapter(state, chapterId, fallbackTitle) {
      const normalizedId = String(chapterId || '');
      let chapter = state.chapters.find((item) => String(item.id) === normalizedId);
      if (chapter) {
        return chapter;
      }
      chapter = {
        id: normalizedId || `draft-${Date.now()}`,
        title: cleanText(fallbackTitle) || 'Chapitre en cours',
        description: 'Contenu en cours de préparation.',
        lessons: 0,
        resources: 0,
        meetings: 0,
        draft: true,
      };
      state.chapters.push(chapter);
      state.stats.chapters = state.chapters.length;
      return chapter;
    }

    function addNote(notes, text) {
      const message = cleanText(text);
      if (message && !notes.includes(message)) {
        notes.push(message);
      }
    }

    function buildState() {
      const state = cloneState();
      state.stats = state.stats || {};
      state.chapters = Array.isArray(state.chapters) ? state.chapters : [];
      const notes = [];

      const newChapterTitle = cleanText(byId('chapter_title')?.value);
      const newChapterDescription = cleanText(byId('chapter_description')?.value);
      if (newChapterTitle || newChapterDescription) {
        state.chapters.push({
          id: 'draft-chapter',
          title: newChapterTitle || 'Nouveau chapitre',
          description: newChapterDescription || 'Description en cours de saisie.',
          lessons: 0,
          resources: 0,
          meetings: 0,
          draft: true,
        });
        state.stats.chapters = state.chapters.length;
        addNote(notes, 'Nouveau chapitre en préparation.');
      }

      if (modalIsOpen('edit-chapter-modal')) {
        const chapterId = cleanText(byId('edit_chapter_id')?.value);
        const chapterTitle = cleanText(byId('edit_chapter_title')?.value);
        const chapterDescription = cleanText(byId('edit_chapter_description')?.value);
        if (chapterId || chapterTitle || chapterDescription) {
          const chapter = ensureChapter(state, chapterId, chapterTitle);
          if (chapterTitle) {
            chapter.title = chapterTitle;
          }
          if (chapterDescription) {
            chapter.description = chapterDescription;
          }
          addNote(notes, `Chapitre en modification : ${chapter.title}.`);
        }
      }

      if (modalIsOpen('add-lesson-modal')) {
        const chapterId = cleanText(byId('lesson_chapter_id')?.value);
        const lessonTitle = cleanText(byId('lesson_title')?.value);
        if (chapterId || lessonTitle) {
          const chapter = ensureChapter(state, chapterId, chapterLabelFromSelect('lesson_chapter_id', chapterId));
          chapter.lessons = Number(chapter.lessons || 0) + 1;
          state.stats.lessons = Number(state.stats.lessons || 0) + 1;
          addNote(notes, `Leçon en préparation : ${lessonTitle || 'Nouvelle leçon'}.`);
        }
      }

      if (modalIsOpen('edit-lesson-modal')) {
        const lessonTitle = cleanText(byId('edit_lesson_title')?.value);
        if (lessonTitle) {
          addNote(notes, `Leçon en modification : ${lessonTitle}.`);
        }
      }

      if (modalIsOpen('add-doc-modal')) {
        const chapterId = cleanText(byId('doc_chapter_id')?.value);
        const docTitle = cleanText(byId('doc_title')?.value);
        if (chapterId || docTitle) {
          const chapter = ensureChapter(state, chapterId, chapterLabelFromSelect('doc_chapter_id', chapterId));
          chapter.resources = Number(chapter.resources || 0) + 1;
          state.stats.resources = Number(state.stats.resources || 0) + 1;
          addNote(notes, `Ressource en préparation : ${docTitle || 'Nouvelle ressource'}.`);
        }
      }

      if (modalIsOpen('edit-doc-modal')) {
        const docTitle = cleanText(byId('edit_doc_title')?.value);
        if (docTitle) {
          addNote(notes, `Ressource en modification : ${docTitle}.`);
        }
      }

      if (modalIsOpen('add-meeting-modal')) {
        const chapterId = cleanText(byId('meeting_chapter_id')?.value);
        const meetingTitle = cleanText(byId('meeting_title')?.value);
        if (chapterId || meetingTitle) {
          const chapter = ensureChapter(state, chapterId, chapterLabelFromSelect('meeting_chapter_id', chapterId));
          chapter.meetings = Number(chapter.meetings || 0) + 1;
          state.stats.meetings = Number(state.stats.meetings || 0) + 1;
          addNote(notes, `Rendez-vous en préparation : ${meetingTitle || 'Nouveau rendez-vous'}.`);
        }
      }

      if (modalIsOpen('edit-meeting-modal')) {
        const meetingTitle = cleanText(byId('edit_meeting_title')?.value);
        if (meetingTitle) {
          addNote(notes, `Rendez-vous en modification : ${meetingTitle}.`);
        }
      }

      return { state, notes };
    }

    function renderOutline(chapters) {
      if (!chapters.length) {
        outlineEl.innerHTML = "<li class=\"course-content-preview-item course-content-preview-item--empty\">Aucun chapitre pour l'instant.</li>";
        return;
      }

      const visible = chapters.slice(0, 6);
      const items = visible.map((chapter) => {
        const title = escapeHtml(cleanText(chapter.title) || 'Chapitre');
        const description = escapeHtml(cleanText(chapter.description) || 'Description à venir.');
        const draftClass = chapter.draft ? ' is-draft' : '';
        return `<li class="course-content-preview-item${draftClass}"><div><p class="course-content-preview-item__title">${title}</p><p class="course-content-preview-item__description">${description}</p></div><div class="course-content-preview-item__meta"><span>${Number(chapter.lessons || 0)} leçon(s)</span><span>${Number(chapter.resources || 0)} ressource(s)</span><span>${Number(chapter.meetings || 0)} rendez-vous</span></div></li>`;
      });

      if (chapters.length > visible.length) {
        items.push(`<li class="course-content-preview-item course-content-preview-item--empty">+ ${chapters.length - visible.length} autre(s) chapitre(s)</li>`);
      }
      outlineEl.innerHTML = items.join('');
    }

    function render() {
      const { state, notes } = buildState();
      titleEl.textContent = cleanText(state.title) || 'Cours sans titre';
      descriptionEl.textContent = cleanText(state.description) || "La description du cours apparaîtra ici pour l'apprenant.";
      outlineCountEl.textContent = `${state.chapters.length} chapitre(s)`;
      renderOutline(state.chapters);

      Object.entries(statEls).forEach(([key, el]) => {
        if (el) {
          el.textContent = String(Number(state.stats[key] || 0));
        }
      });

      noteEl.textContent = notes.length ? notes.join(' ') : 'Aucune modification locale en cours.';
    }

    function scheduleRender() {
      window.requestAnimationFrame(render);
    }

    document.addEventListener('input', (event) => {
      if (event.target.closest('#form-add-chapter, #form-add-lesson, #form-add-doc, #form-add-meeting, #edit-chapter-form, #edit-lesson-form, #edit-doc-form, #edit-meeting-form')) {
        scheduleRender();
      }
    });

    document.addEventListener('change', (event) => {
      if (event.target.closest('#form-add-chapter, #form-add-lesson, #form-add-doc, #form-add-meeting, #edit-chapter-form, #edit-lesson-form, #edit-doc-form, #edit-meeting-form')) {
        scheduleRender();
      }
    });

    document.addEventListener('click', (event) => {
      if (event.target.closest('[data-open-add], [data-open-edit], [data-close-modal], #course-content-restore-btn, #course-content-discard-btn')) {
        window.setTimeout(render, 0);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        window.setTimeout(render, 0);
      }
    });

    render();
  }

  function initDocUploadProgress() {
    const form = byId('form-add-doc');
    const fileInput = byId('doc_file');
    const wrap = byId('doc-upload-progress');
    const bar = byId('doc-upload-progress-bar');
    const label = byId('doc-upload-progress-label');
    const text = byId('doc-upload-progress-text');
    const hint = byId('doc-upload-progress-hint');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    const submitInitiallyDisabled = submitBtn ? submitBtn.disabled : false;
    const defaultSubmitLabel = submitBtn ? submitBtn.textContent.trim() || 'Ajouter' : 'Ajouter';
    if (!form || !fileInput || !wrap || !bar || !text || !label || !hint) {
      return;
    }

    function updateSubmitState(isBusy, busyLabel = 'Validation en cours...') {
      if (!submitBtn) {
        return;
      }
      submitBtn.disabled = isBusy ? true : submitInitiallyDisabled;
      submitBtn.classList.toggle('opacity-70', isBusy);
      submitBtn.classList.toggle('cursor-wait', isBusy);
      submitBtn.textContent = isBusy ? busyLabel : defaultSubmitLabel;
    }

    function setProgressState(nextLabel, nextText, nextHint) {
      label.textContent = nextLabel;
      text.textContent = nextText;
      hint.textContent = nextHint;
    }

    function resetProgressState() {
      wrap.classList.add('hidden');
      bar.style.width = '0%';
      setProgressState(
        "Préparation de l'envoi...",
        '0%',
        "Le document peut prendre quelques instants à être validé après l'envoi."
      );
      form.dataset.uploading = '0';
      updateSubmitState(false);
    }

    fileInput.addEventListener('change', () => {
      if (form.dataset.uploading !== '1') {
        resetProgressState();
      }
    });

    form.addEventListener('reset', () => {
      window.setTimeout(resetProgressState, 0);
    });

    form.addEventListener('submit', (event) => {
      if (!fileInput.files || fileInput.files.length === 0) {
        return;
      }
      if (form.dataset.uploading === '1') {
        event.preventDefault();
        return;
      }

      event.preventDefault();
      form.dataset.uploading = '1';
      wrap.classList.remove('hidden');
      bar.style.width = '0%';
      setProgressState(
        'Téléversement du document...',
        '0%',
        'Envoi du fichier vers le serveur en cours.'
      );
      updateSubmitState(true, 'Envoi en cours...');

      const xhr = new XMLHttpRequest();
      xhr.open('POST', resolveFormAction(form), true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      xhr.upload.addEventListener('progress', (e) => {
        if (!e.lengthComputable) {
          return;
        }
        const pct = Math.round((e.loaded / e.total) * 100);
        bar.style.width = `${pct}%`;
        setProgressState(
          pct >= 100 ? 'Téléversement terminé.' : 'Téléversement du document...',
          `${pct}%`,
          pct >= 100
            ? 'Validation du document en cours. Cela peut prendre quelques secondes.'
            : 'Envoi du fichier vers le serveur en cours.'
        );
      });

      xhr.upload.addEventListener('loadend', () => {
        if (form.dataset.uploading !== '1') {
          return;
        }
        bar.style.width = '100%';
        setProgressState(
          'Validation du document...',
          '100%',
          'Le fichier a été envoyé. Vérification et enregistrement en cours.'
        );
        updateSubmitState(true, 'Validation en cours...');
      });

      xhr.addEventListener('load', () => {
        form.dataset.uploading = '0';
        const contentType = String(xhr.getResponseHeader('Content-Type') || '');
        let payload = null;
        if (contentType.includes('application/json')) {
          try {
            payload = JSON.parse(xhr.responseText || '{}');
          } catch (_e) {
            payload = null;
          }
        }

        if (payload && payload.ok === true && payload.redirectTo) {
          setProgressState(
            'Document validé. Redirection...',
            '100%',
            'Le document a été ajouté au cours.'
          );
          window.location.assign(payload.redirectTo);
          return;
        }

        if (payload && payload.ok === false) {
          const message = Array.isArray(payload.errors) && payload.errors.length > 0
            ? String(payload.errors[0] || '')
            : "Le serveur a refusé le document ou a rencontré une erreur.";
          updateSubmitState(false);
          setProgressState(
            'Échec de validation',
            'Erreur',
            message
          );
          return;
        }

        if (xhr.status >= 200 && xhr.status < 400) {
          setProgressState(
            'Document validé. Redirection...',
            '100%',
            'Le document a été ajouté au cours.'
          );
          window.location.assign(xhr.responseURL || window.location.href);
          return;
        }
        updateSubmitState(false);
        setProgressState(
          'Échec de validation',
          'Erreur',
          'Le serveur a refusé le document ou a rencontré une erreur.'
        );
      });

      xhr.addEventListener('error', () => {
        form.dataset.uploading = '0';
        updateSubmitState(false);
        setProgressState(
          'Erreur réseau',
          'Erreur',
          "La connexion a échoué pendant l'envoi du document."
        );
      });

      xhr.send(new FormData(form));
    });

    resetProgressState();
  }

  function initPedagogyLists() {
    const form = document.querySelector('[data-course-pedagogy-form]');
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    const lists = Array.from(form.querySelectorAll('.course-list[id][data-name]'));
    if (!lists.length) {
      return;
    }

    function clean(value) {
      return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function listValues(list) {
      return Array.from(list.querySelectorAll('input[data-list-input]'))
        .map((input) => clean(input.value))
        .filter(Boolean);
    }

    function updateCounts() {
      form.querySelectorAll('[data-list-count]').forEach((el) => {
        const listId = el.getAttribute('data-list-count') || '';
        const list = listId ? document.getElementById(listId) : null;
        if (!list) {
          return;
        }
        const max = parseInt(el.getAttribute('data-list-max') || '0', 10);
        const count = listValues(list).length;
        el.textContent = max > 0 ? `${count}/${max}` : String(count);
        el.classList.toggle('is-full', max > 0 && count >= max);
      });
    }

    function createRow(list, value) {
      const item = clean(value);
      if (!item) {
        return;
      }

      const max = parseInt(form.querySelector(`[data-list-count="${list.id}"]`)?.getAttribute('data-list-max') || '0', 10);
      if (max > 0 && listValues(list).length >= max) {
        return;
      }

      const row = document.createElement('div');
      row.className = 'course-list-row';
      row.setAttribute('data-list-row', '1');
      const locked = !!form.querySelector('[data-add-target]:disabled');

      const input = document.createElement('input');
      input.type = 'text';
      input.name = list.getAttribute('data-name') || '';
      input.value = item;
      input.placeholder = list.getAttribute('data-placeholder') || '';
      input.className = 'course-list-input';
      input.setAttribute('data-list-input', '1');
      input.disabled = locked;

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'course-list-remove';
      remove.setAttribute('aria-label', 'Retirer');
      remove.textContent = '×';
      remove.disabled = locked;

      row.appendChild(input);
      row.appendChild(remove);
      list.appendChild(row);
      updateCounts();
    }

    function addFromInput(targetId) {
      const list = document.getElementById(targetId);
      const input = form.querySelector(`[data-list-quick-input][data-list-target="${targetId}"]`);
      if (!list || !(input instanceof HTMLInputElement)) {
        return;
      }
      createRow(list, input.value);
      input.value = '';
      input.focus();
    }

    lists.forEach((list) => {
      let initial = [];
      try {
        initial = JSON.parse(list.getAttribute('data-initial') || '[]');
      } catch (_e) {
        initial = [];
      }
      if (Array.isArray(initial)) {
        initial.forEach((item) => createRow(list, item));
      }
    });

    form.addEventListener('click', (event) => {
      const target = event.target instanceof Element ? event.target : null;
      if (!target) {
        return;
      }

      const remove = target.closest('.course-list-remove');
      if (remove) {
        event.preventDefault();
        remove.closest('[data-list-row]')?.remove();
        updateCounts();
        return;
      }

      const add = target.closest('[data-add-target]');
      if (add) {
        event.preventDefault();
        addFromInput(add.getAttribute('data-add-target') || '');
      }
    });

    form.querySelectorAll('[data-list-quick-input]').forEach((input) => {
      input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
          return;
        }
        event.preventDefault();
        addFromInput(input.getAttribute('data-list-target') || '');
      });
    });

    form.addEventListener('submit', (event) => {
      form.querySelectorAll('[data-list-quick-input]').forEach((input) => {
        if (input instanceof HTMLInputElement && clean(input.value) !== '') {
          addFromInput(input.getAttribute('data-list-target') || '');
        }
      });

      const objectives = document.getElementById('objectives-list');
      const objectiveInput = form.querySelector('[data-list-quick-input][data-list-target="objectives-list"]');
      if (objectives && listValues(objectives).length < 1 && objectiveInput instanceof HTMLInputElement) {
        event.preventDefault();
        objectiveInput.setCustomValidity('Ajoutez au moins un objectif pédagogique.');
        objectiveInput.reportValidity();
        objectiveInput.focus();
        window.setTimeout(() => objectiveInput.setCustomValidity(''), 1200);
      }
    });

    updateCounts();
  }

  onReady(() => {
    initModalState();
    initCourseSteps();
    initPedagogyLists();
    initTabs();
    initConfirmModal();
    initEditModals();
    initDocBindings();
    initLiveValidation();
    initYoutubePreview();
    initAccordion();
    initPlanSearch();
    initDragAndReorder();
    initAutosave();
    initRealtimePreview();
    initDocUploadProgress();
  });
})();
