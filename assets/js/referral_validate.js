(() => {
  const form = document.getElementById('checkout-form');
  if (!form) {
    return;
  }
  const endpoint = form.dataset.referralEndpoint || '';
  if (!endpoint) {
    return;
  }
  const submitBtn = form.querySelector('button[type="submit"]');
  const csrfInput = form.querySelector('input[name="_csrf"]');
  const csrfToken = csrfInput ? csrfInput.value : '';
  const inputs = Array.from(document.querySelectorAll('[data-referral-code]'));
  if (!inputs.length) {
    return;
  }

  const timers = new Map();
  const reqSeq = new Map();

  const updateSubmit = () => {
    if (!submitBtn) {
      return;
    }
    const hasInvalid = inputs.some((input) => {
      const status = input.dataset.refStatus || '';
      return status === 'invalid' || status === 'pending';
    });
    submitBtn.disabled = hasInvalid;
    submitBtn.classList.toggle('opacity-60', hasInvalid);
    submitBtn.classList.toggle('cursor-not-allowed', hasInvalid);
  };

  const setStatus = (input, status, message) => {
    const wrap = input.closest('[data-referral-wrap]');
    const statusEl = wrap ? wrap.querySelector('.js-referral-status') : null;
    const badgeEl = wrap ? wrap.querySelector('.js-referral-badge') : null;
    if (statusEl) {
      const defaultText = statusEl.dataset.default || '';
      const okText = statusEl.dataset.ok || 'Code valide.';
      const pendingText = statusEl.dataset.pending || 'V\u{00e9}rification...';
      let text = defaultText;
      if (status === 'valid') {
        text = message || okText;
      } else if (status === 'invalid') {
        text = message || 'Code de parrainage invalide.';
      } else if (status === 'pending') {
        text = pendingText;
      }
      statusEl.textContent = text;
      statusEl.classList.remove('text-slate-500', 'text-emerald-400', 'text-rose-400');
      if (status === 'valid') {
        statusEl.classList.add('text-emerald-400');
      } else if (status === 'invalid') {
        statusEl.classList.add('text-rose-400');
      } else {
        statusEl.classList.add('text-slate-500');
      }
    }
    if (badgeEl) {
      badgeEl.classList.add('hidden');
      badgeEl.classList.remove(
        'bg-emerald-500/20',
        'text-emerald-200',
        'border-emerald-400/40',
        'bg-rose-500/20',
        'text-rose-200',
        'border-rose-400/40',
        'bg-slate-700/60',
        'text-slate-200',
        'border-slate-500/40'
      );
      if (status === 'valid') {
        badgeEl.textContent = 'OK';
        badgeEl.classList.remove('hidden');
        badgeEl.classList.add('bg-emerald-500/20', 'text-emerald-200', 'border-emerald-400/40');
      } else if (status === 'invalid') {
        badgeEl.textContent = 'INVALIDE';
        badgeEl.classList.remove('hidden');
        badgeEl.classList.add('bg-rose-500/20', 'text-rose-200', 'border-rose-400/40');
      } else if (status === 'pending') {
        badgeEl.textContent = 'VERIF';
        badgeEl.classList.remove('hidden');
        badgeEl.classList.add('bg-slate-700/60', 'text-slate-200', 'border-slate-500/40');
      }
    }
    input.dataset.refStatus = status;
    updateSubmit();
  };

  const localValidate = (input, raw) => {
    const parts = raw.split('.', 2);
    if (parts.length === 2) {
      if (!parts[0] || !parts[1]) {
        return 'Format incomplet: CODECOURS.CODEPARRAIN.';
      }
      const token = input.dataset.courseToken || '';
      if (token && parts[0] !== token) {
        return 'Ce code ne correspond pas a ce cours.';
      }
      return '';
    }
    if (raw.length < 6) {
      return 'Code trop court.';
    }
    return '';
  };

  const runRemoteValidation = async (input, raw) => {
    const courseId = input.dataset.courseId || '';
    const seq = (reqSeq.get(input) || 0) + 1;
    reqSeq.set(input, seq);
    setStatus(input, 'pending');
    try {
      const payload = new FormData();
      payload.append('_csrf', csrfToken);
      payload.append('course_id', courseId);
      payload.append('code', raw);
      const response = await fetch(endpoint, {
        method: 'POST',
        body: payload,
        credentials: 'same-origin',
      });
      const data = await response.json().catch(() => ({}));
      if (reqSeq.get(input) !== seq) {
        return;
      }
      if (response.ok && data.ok) {
        setStatus(input, 'valid', data.message || '');
      } else {
        setStatus(input, 'invalid', data.error || 'Code de parrainage invalide.');
      }
    } catch (error) {
      if (reqSeq.get(input) !== seq) {
        return;
      }
      setStatus(input, 'invalid', 'V\u{00e9}rification impossible. Veuillez r\u{00e9}essayer.');
    }
  };

  const scheduleValidation = (input) => {
    if (timers.has(input)) {
      clearTimeout(timers.get(input));
    }
    timers.set(
      input,
      setTimeout(() => {
        const raw = input.value.trim();
        if (!raw) {
          setStatus(input, '', '');
          return;
        }
        const localError = localValidate(input, raw);
        if (localError) {
          setStatus(input, 'invalid', localError);
          return;
        }
        runRemoteValidation(input, raw);
      }, 350)
    );
  };

  inputs.forEach((input) => {
    input.addEventListener('input', () => scheduleValidation(input));
    input.addEventListener('blur', () => scheduleValidation(input));
    if (input.value.trim() !== '') {
      scheduleValidation(input);
    }
  });

  updateSubmit();
})();
