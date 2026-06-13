// assets/js/course_notes.js
// Bloc-notes local par cours (stocké dans localStorage) + export Word/PDF

(function () {
  document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const courseId = body.dataset.courseId || null;
    const courseTitle = body.dataset.courseTitle || 'cours';
    if (!courseId) return;

    let noteFields = Array.from(document.querySelectorAll('[data-course-notes-textarea]'));
    if (noteFields.length === 0) {
      const fallbackTextarea = document.getElementById('lesson-notes');
      if (fallbackTextarea) {
        noteFields = [fallbackTextarea];
      }
    }
    if (noteFields.length === 0) return;

    let statusNodes = Array.from(document.querySelectorAll('[data-course-notes-status]'));
    if (statusNodes.length === 0) {
      const fallbackStatus = document.getElementById('notes-status');
      if (fallbackStatus) {
        statusNodes = [fallbackStatus];
      }
    }

    let docButtons = Array.from(document.querySelectorAll('[data-course-notes-export="doc"]'));
    if (docButtons.length === 0) {
      const fallbackDoc = document.getElementById('btn-notes-doc');
      if (fallbackDoc) {
        docButtons = [fallbackDoc];
      }
    }

    let pdfButtons = Array.from(document.querySelectorAll('[data-course-notes-export="pdf"]'));
    if (pdfButtons.length === 0) {
      const fallbackPdf = document.getElementById('btn-notes-pdf');
      if (fallbackPdf) {
        pdfButtons = [fallbackPdf];
      }
    }

    let saveTimer = null;
    let isSyncing = false;
    let currentNotes = '';
    const storageKey = `course_notes_${courseId}`;
    const legacyPrefix = `course_notes_${courseId}_`;

    const setStatus = (msg) => {
      statusNodes.forEach((node) => {
        node.textContent = msg || '';
      });
    };

    const syncEditors = (value) => {
      isSyncing = true;
      noteFields.forEach((field) => {
        if (field.value !== value) {
          field.value = value;
        }
      });
      isSyncing = false;
    };

    const readLegacyNotes = () => {
      const legacyValues = [];
      const seen = new Set();
      const currentLessonId = body.dataset.currentLessonId || '';
      if (currentLessonId) {
        const legacyCurrentKey = `${legacyPrefix}${currentLessonId}`;
        const legacyCurrentValue = (localStorage.getItem(legacyCurrentKey) || '').trim();
        if (legacyCurrentValue) {
          legacyValues.push(legacyCurrentValue);
          seen.add(legacyCurrentValue);
        }
      }

      for (let i = 0; i < localStorage.length; i += 1) {
        const key = localStorage.key(i) || '';
        if (!key.startsWith(legacyPrefix)) continue;
        const value = (localStorage.getItem(key) || '').trim();
        if (value && !seen.has(value)) {
          legacyValues.push(value);
          seen.add(value);
        }
      }

      return legacyValues.join('\n\n---\n\n');
    };

    const loadNotes = () => {
      let val = localStorage.getItem(storageKey);
      if (val === null) {
        val = readLegacyNotes();
        if (val) {
          try {
            localStorage.setItem(storageKey, val);
          } catch (_) {}
        }
      }

      currentNotes = val || '';
      syncEditors(currentNotes);
      setStatus(currentNotes.trim() ? 'Chargé' : 'Vide');
    };

    const saveNotes = () => {
      try {
        localStorage.setItem(storageKey, currentNotes || '');
        setStatus(currentNotes.trim() ? 'Sauvegardé' : 'Vide');
      } catch (_) {
        setStatus('Stockage indisponible');
      }
    };

    const scheduleSave = () => {
      setStatus('Sauvegarde...');
      if (saveTimer) clearTimeout(saveTimer);
      saveTimer = setTimeout(() => {
        saveTimer = null;
        saveNotes();
      }, 400);
    };

    noteFields.forEach((field) => {
      field.addEventListener('input', () => {
        if (isSyncing) return;
        currentNotes = field.value || '';
        syncEditors(currentNotes);
        scheduleSave();
      });
    });

    const download = (blob, filename) => {
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      setTimeout(() => {
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }, 0);
    };

    const getCourseLabel = () => courseTitle.trim() || 'cours';

    const makeFilename = (ext) => {
      const safe = getCourseLabel().replace(/[^a-z0-9-_]+/gi, '_').slice(0, 60) || 'notes';
      return `${safe}.${ext}`;
    };

    const escapeHtml = (value) => (value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    const exportDoc = () => {
      const html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">
        <head><meta charset="UTF-8"><title>Notes</title></head>
        <body><h2>${escapeHtml(getCourseLabel())}</h2><pre style="font-family:Segoe UI, sans-serif; white-space:pre-wrap;">${escapeHtml(currentNotes)}</pre></body>
        </html>`;
      const blob = new Blob([html], { type: 'application/msword' });
      download(blob, makeFilename('doc'));
      setStatus('Export Word prêt');
    };

    const exportPdf = () => {
      const w = window.open('', '_blank', 'width=800,height=900');
      if (!w) {
        setStatus('Impossible d’ouvrir la fenêtre PDF');
        return;
      }

      w.document.write(`
        <html><head><title>Notes PDF</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 24px; }
          h2 { margin-top: 0; }
          pre { white-space: pre-wrap; word-break: break-word; }
        </style>
        </head>
        <body>
          <h2>${escapeHtml(getCourseLabel())}</h2>
          <pre>${escapeHtml(currentNotes)}</pre>
        </body></html>`);
      w.document.close();
      w.focus();
      setTimeout(() => {
        try {
          w.print();
        } catch (_) {}
      }, 300);
      setStatus('Fenêtre PDF ouverte (imprimer en PDF)');
    };

    docButtons.forEach((button) => button.addEventListener('click', exportDoc));
    pdfButtons.forEach((button) => button.addEventListener('click', exportPdf));

    const flushSave = () => {
      if (saveTimer) {
        clearTimeout(saveTimer);
        saveTimer = null;
      }
      saveNotes();
    };

    window.addEventListener('pagehide', flushSave);
    window.addEventListener('beforeunload', flushSave);

    // Une seule note pour tout le cours, synchronisée entre les zones d'édition.
    loadNotes();
  });
})();
