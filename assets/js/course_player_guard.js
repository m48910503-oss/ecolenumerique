// assets/js/course_player_guard.js
// Reapplique le shield anti-interaction sur les lecteurs video
// sans bloquer les controles custom explicitement autorises.

document.addEventListener('DOMContentLoaded', () => {
  const ALLOW_SELECTOR = '[data-guard-allow="1"]';
  const guardPairs = [
    {
      wrap: document.getElementById('player-wrapper'),
      overlay: document.getElementById('player-overlay'),
    },
    {
      wrap: document.getElementById('public-preview-wrapper'),
      overlay: document.getElementById('public-preview-overlay'),
    },
  ].filter((pair) => pair.wrap && pair.overlay);

  if (!guardPairs.length) return;

  const events = [
    'contextmenu',
    'mousedown',
    'mouseup',
    'click',
    'dblclick',
    'dragstart',
    'selectstart',
    'copy',
    'cut',
    'paste',
  ];

  const isAllowed = (target) =>
    !!(
      target &&
      (target.closest?.(ALLOW_SELECTOR) || target.dataset?.guardAllow === '1')
    );

  const block = (e) => {
    if (isAllowed(e.target)) return;
    try {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation?.();
    } catch (_) {}
    return false;
  };

  const applyGuards = (target) => {
    if (!target || target.dataset.guardBound === '1') return;
    target.dataset.guardBound = '1';

    events.forEach((evt) => {
      target.addEventListener(evt, block, { capture: true });
    });

    target.addEventListener('touchstart', block, {
      passive: false,
      capture: true,
    });
  };

  guardPairs.forEach(({ wrap, overlay }) => {
    applyGuards(wrap);
    applyGuards(overlay);
  });
});
