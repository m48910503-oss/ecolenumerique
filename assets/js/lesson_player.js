// Lecteur vidéo custom pour les leçons (sans inline, compatible CSP).
// Fonctions : play/pause, +/-10s, volume/mute, vitesse, fullscreen/PiP,
// timeline avec marqueurs de chapitres, autosave/reprise, raccourcis clavier.

document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('lesson-video');
    if (!video) return;

    const playerCard = document.getElementById('player-shell') || document.getElementById('main-player-card');
    const progressRange = document.getElementById('progress-range');
    const timeLabel = document.getElementById('time-label');
    const speedSelect = document.getElementById('speed-select') || document.getElementById('speed');
    const volumeRange = document.getElementById('volume-range') || document.getElementById('volume');
    const btnPlay = document.getElementById('btn-play');
    const btnBack10 = document.querySelector('[data-action="back10"]') || document.getElementById('btn-prev');
    const btnFwd10 = document.querySelector('[data-action="fwd10"]') || document.getElementById('btn-next');
    const btnMute = document.getElementById('btn-mute');
    const btnFull = document.getElementById('btn-fullscreen') || document.querySelector('[data-action="fs"]');
    const btnPip = document.getElementById('btn-pip') || document.querySelector('[data-action="pip"]');
    const btnComplete = document.getElementById('btn-complete') || document.getElementById('btn-mark-top');
    const btnResume = document.getElementById('btn-resume');
    const chapterMarkers = document.getElementById('chapter-markers');
    const chapterList = document.getElementById('chapter-list');
    const lessonId = (playerCard?.dataset.currentLessonId) || (playerCard?.dataset.lessonId) || video.dataset.lessonId;
    const courseId = document.body.dataset.courseId || '';
    const basePath = document.body.dataset.basePath || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Chapitres injectés en JSON (optionnel)
    let chaptersData = [];
    try {
        const raw = document.getElementById('chapters-json')?.textContent || '';
        if (raw) chaptersData = JSON.parse(raw);
    } catch (_) {}

    // Notes/signets (optionnel)
    const noteForm = document.getElementById('note-form');
    const noteList = document.getElementById('note-list');
    const bmForm = document.getElementById('bm-form');
    const bmList = document.getElementById('bm-list');

    const positionUrl = `${basePath}/api/progress_position`;
    const notesUrl = `${basePath}/api/notes`;
    const bmUrl = `${basePath}/api/bookmarks`;

    let saveTimer = null;
    let uiTimer = null;
    let lastDuration = 0;
    let resumeTime = 0;
    let completed = false;

    const storageKey = (id) => `lesson:${id}:pos`;

    const formatTime = (sec) => {
        const s = Math.max(0, Math.floor(sec || 0));
        const m = Math.floor(s / 60);
        const r = s % 60;
        return `${m.toString().padStart(2, '0')}:${r.toString().padStart(2, '0')}`;
    };

    function updateUi() {
        const d = video.duration || lastDuration || 0;
        const c = video.currentTime || 0;
        if (progressRange && d > 0) {
            progressRange.value = Math.min(1000, Math.floor((c / d) * 1000));
        }
        if (timeLabel) timeLabel.textContent = `${formatTime(c)} / ${d ? formatTime(d) : '00:00'}`;
    }

    function saveLocalPosition(sec) {
        if (!lessonId) return;
        localStorage.setItem(storageKey(lessonId), String(Math.floor(sec)));
    }

    function fetchLocalPosition() {
        if (!lessonId) return 0;
        const raw = localStorage.getItem(storageKey(lessonId));
        const sec = raw ? parseInt(raw, 10) : 0;
        return Number.isFinite(sec) ? sec : 0;
    }

    function postPosition(reason = 'auto') {
        if (!lessonId) return;
        const d = video.duration || 0;
        const c = video.currentTime || 0;
        const payload = new FormData();
        payload.append('lesson_id', lessonId);
        payload.append('seconds', Math.floor(c));
        payload.append('duration', Math.floor(d));
        const percent = d > 0 ? Math.round((c / d) * 100) : 0;
        payload.append('percent', percent);
        if (percent >= 90 && !completed) {
            payload.append('completed_at', Date.now());
            completed = true;
        }
        payload.append('_csrf', csrfToken);
        fetch(positionUrl, { method: 'POST', body: payload }).catch(() => {});
    }

    function loadResume() {
        const localSec = fetchLocalPosition();
        resumeTime = localSec;
        if (localSec > 10 && btnResume) {
            btnResume.classList.remove('hidden');
            btnResume.textContent = `Reprendre à ${formatTime(localSec)}`;
            btnResume.addEventListener('click', () => {
                video.currentTime = localSec;
                btnResume.classList.add('hidden');
            }, { once: true });
        }
    }

    function bindControls() {
        if (btnPlay) {
            btnPlay.addEventListener('click', () => {
                if (video.paused) video.play(); else video.pause();
            });
        }
        if (btnBack10) btnBack10.addEventListener('click', () => { video.currentTime = Math.max(0, video.currentTime - 10); });
        if (btnFwd10) btnFwd10.addEventListener('click', () => { video.currentTime = Math.min(video.duration || Infinity, video.currentTime + 10); });
        if (volumeRange) volumeRange.addEventListener('input', (e) => { video.volume = Math.min(1, Math.max(0, (parseInt(e.target.value, 10) || 0) / 100)); });
        if (speedSelect) speedSelect.addEventListener('change', (e) => { const r = parseFloat(e.target.value) || 1; video.playbackRate = r; });
        if (btnMute) btnMute.addEventListener('click', () => { video.muted = !video.muted; });
        if (btnFull && playerCard) {
            btnFull.addEventListener('click', () => {
                if (!document.fullscreenElement) playerCard.requestFullscreen?.(); else document.exitFullscreen?.();
            });
        }
        if (btnPip && document.pictureInPictureEnabled) {
            btnPip.addEventListener('click', async () => { try { if (document.pictureInPictureElement) await document.exitPictureInPicture(); else await video.requestPictureInPicture(); } catch (_) {} });
        }
        if (progressRange) {
            progressRange.addEventListener('input', (e) => {
                const d = video.duration || 0;
                const ratio = parseInt(e.target.value, 10) / 1000;
                if (d > 0) video.currentTime = d * ratio;
            });
        }
        video.addEventListener('contextmenu', (e) => e.preventDefault());
    }

    function bindKeyboard() {
        document.addEventListener('keydown', (e) => {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
            switch (e.key.toLowerCase()) {
                case ' ': e.preventDefault(); video.paused ? video.play() : video.pause(); break;
                case 'arrowleft': video.currentTime = Math.max(0, video.currentTime - 10); break;
                case 'arrowright': video.currentTime = Math.min(video.duration || Infinity, video.currentTime + 10); break;
                case 'arrowup': video.volume = Math.min(1, video.volume + 0.05); break;
                case 'arrowdown': video.volume = Math.max(0, video.volume - 0.05); break;
                case 'f': if (!document.fullscreenElement) playerCard?.requestFullscreen(); else document.exitFullscreen?.(); break;
                case 'm': video.muted = !video.muted; break;
            }
        });
    }

    function setupChapters() {
        if (!chaptersData.length) return;
        if (chapterList) {
            chapterList.innerHTML = '';
            chaptersData.forEach(ch => {
                const btn = document.createElement('button');
                btn.className = 'w-full text-left px-3 py-2 rounded border border-slate-800 bg-slate-900 hover:border-emerald-400 text-sm';
                btn.textContent = `${formatTime(ch.time || 0)} · ${ch.title}`;
                btn.addEventListener('click', () => { video.currentTime = ch.time || 0; video.play(); });
                chapterList.appendChild(btn);
            });
        }
        if (chapterMarkers && video.duration) {
            chapterMarkers.innerHTML = '';
            chaptersData.forEach(ch => {
                const mark = document.createElement('button');
                mark.className = 'absolute -top-1 h-3 w-1 rounded bg-emerald-400';
                const ratio = (ch.time || 0) / video.duration;
                mark.style.left = `${ratio * 100}%`;
                mark.title = ch.title;
                mark.addEventListener('click', () => { video.currentTime = ch.time || 0; });
                chapterMarkers.appendChild(mark);
            });
        }
    }

    async function loadPositionFromServer() {
        if (!lessonId) return 0;
        try {
            const res = await fetch(`${positionUrl}?lesson_id=${encodeURIComponent(lessonId)}`);
            const data = await res.json();
            if (data && data.ok) return parseInt(data.seconds, 10) || 0;
        } catch (_) {}
        return 0;
    }

    async function initResume() {
        const serverSec = await loadPositionFromServer();
        const localSec = fetchLocalPosition();
        const start = Math.max(serverSec, localSec);
        if (start > 10) {
            resumeTime = start;
            video.currentTime = start;
        }
        if (btnResume && resumeTime > 10) {
            btnResume.classList.remove('hidden');
            btnResume.textContent = `Reprendre à ${formatTime(resumeTime)}`;
            btnResume.addEventListener('click', () => {
                video.currentTime = resumeTime;
                btnResume.classList.add('hidden');
            }, { once: true });
        }
    }

    function setupNotes() {
        if (!noteForm || !noteList) return;
        noteForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(noteForm);
            fd.append('lesson_id', lessonId);
            fd.append('timestamp', Math.floor(video.currentTime || 0));
            fd.append('_csrf', csrfToken);
            fetch(notesUrl, { method: 'POST', body: fd })
                .then(res => res.json()).then(() => noteForm.reset()).then(refreshNotes).catch(() => {});
        });
    }

    function setupBookmarks() {
        if (!bmForm || !bmList) return;
        bmForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(bmForm);
            fd.append('lesson_id', lessonId);
            fd.append('timestamp', Math.floor(video.currentTime || 0));
            fd.append('_csrf', csrfToken);
            fetch(bmUrl, { method: 'POST', body: fd })
                .then(res => res.json()).then(() => bmForm.reset()).then(refreshBookmarks).catch(() => {});
        });
    }

    function refreshNotes() {
        if (!noteList) return;
        fetch(`${notesUrl}?lesson_id=${encodeURIComponent(lessonId)}`)
            .then(res => res.json())
            .then(data => {
                noteList.innerHTML = '';
                (data.notes || []).forEach((n) => {
                    const item = document.createElement('div');
                    item.className = 'rounded border border-slate-800 bg-slate-900 px-3 py-2 text-sm flex justify-between gap-2';
                    const content = document.createElement('div');
                    const title = document.createElement('p');
                    title.className = 'font-semibold text-slate-100';
                    title.textContent = formatTime(n.timestamp);
                    const body = document.createElement('p');
                    body.className = 'text-slate-300';
                    body.textContent = n.text || '';
                    content.appendChild(title);
                    content.appendChild(body);
                    item.appendChild(content);
                    const btn = document.createElement('button');
                    btn.className = 'text-xs text-emerald-300 underline';
                    btn.textContent = 'Aller';
                    btn.addEventListener('click', () => { video.currentTime = n.timestamp; video.play(); });
                    item.appendChild(btn);
                    noteList.appendChild(item);
                });
            }).catch(() => {});
    }

    function refreshBookmarks() {
        if (!bmList) return;
        fetch(`${bmUrl}?lesson_id=${encodeURIComponent(lessonId)}`)
            .then(res => res.json())
            .then(data => {
                bmList.innerHTML = '';
                (data.bookmarks || []).forEach((b) => {
                    const item = document.createElement('div');
                    item.className = 'rounded border border-slate-800 bg-slate-900 px-3 py-2 text-sm flex justify-between gap-2';
                    const content = document.createElement('div');
                    const title = document.createElement('p');
                    title.className = 'font-semibold text-slate-100';
                    title.textContent = formatTime(b.timestamp);
                    const body = document.createElement('p');
                    body.className = 'text-slate-300';
                    body.textContent = b.label || '';
                    content.appendChild(title);
                    content.appendChild(body);
                    item.appendChild(content);
                    const btn = document.createElement('button');
                    btn.className = 'text-xs text-emerald-300 underline';
                    btn.textContent = 'Aller';
                    btn.addEventListener('click', () => { video.currentTime = b.timestamp; video.play(); });
                    item.appendChild(btn);
                    bmList.appendChild(item);
                });
            }).catch(() => {});
    }

    function bindTabs() {
        const tabs = document.querySelectorAll('.tab');
        const panes = document.querySelectorAll('.pane');
        tabs.forEach((t) => {
            t.addEventListener('click', () => {
                const target = t.dataset.tab;
                tabs.forEach(x => x.classList.remove('active'));
                panes.forEach(p => p.classList.add('hidden'));
                t.classList.add('active');
                document.getElementById(`pane-${target}`)?.classList.remove('hidden');
            });
        });
    }

    video.addEventListener('loadedmetadata', () => {
        lastDuration = video.duration || 0;
        setupChapters();
    });

    video.addEventListener('play', () => {
        clearInterval(saveTimer);
        saveTimer = setInterval(() => { postPosition('auto'); saveLocalPosition(video.currentTime); }, 5000);
    });
    video.addEventListener('pause', () => { clearInterval(saveTimer); postPosition('pause'); saveLocalPosition(video.currentTime); });
    video.addEventListener('ended', () => { clearInterval(saveTimer); postPosition('ended'); saveLocalPosition(0); });
    video.addEventListener('timeupdate', () => { updateUi(); });
    window.addEventListener('beforeunload', () => { postPosition('unload'); });

    bindControls();
    bindKeyboard();
    bindTabs();
    setupNotes(); refreshNotes();
    setupBookmarks(); refreshBookmarks();
    initResume().then(() => updateUi());
    uiTimer = setInterval(updateUi, 1000);
});
