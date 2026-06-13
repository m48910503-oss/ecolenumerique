(() => {
  const allowedMime = ['image/jpeg', 'image/png', 'image/webp'];

  const setFeedback = (feedbackEl, message, tone = 'neutral') => {
    if (!feedbackEl) return;
    feedbackEl.textContent = message || '';
    feedbackEl.dataset.tone = tone;
  };

  const setInlineMessage = (el, message, tone = 'error') => {
    if (!el) return;
    const text = (message || '').trim();
    el.textContent = text;
    if (!text) {
      el.dataset.state = 'hidden';
      el.classList.add('hidden');
      return;
    }
    el.dataset.state = tone;
    el.classList.remove('hidden');
  };

  const setInputValidityState = (input, isValid) => {
    if (!input) return;
    input.setAttribute('aria-invalid', isValid ? 'false' : 'true');
    input.style.borderColor = isValid ? '' : 'rgb(251 113 133 / 1)';
  };

  const updatePreview = (previewEl, placeholderEl, file) => {
    if (!previewEl || !file) return;
    const objectUrl = URL.createObjectURL(file);
    previewEl.src = objectUrl;
    previewEl.classList.remove('hidden');
    if (placeholderEl) {
      placeholderEl.classList.add('hidden');
    }
  };

  const resetPreview = (previewEl, placeholderEl) => {
    if (!previewEl) return;
    const hasSrc = !!(previewEl.getAttribute('src') || '').trim();
    if (hasSrc) {
      previewEl.classList.remove('hidden');
      if (placeholderEl) {
        placeholderEl.classList.add('hidden');
      }
      return;
    }
    previewEl.classList.add('hidden');
    if (placeholderEl) {
      placeholderEl.classList.remove('hidden');
    }
  };

  const validateFile = (file, maxMb) => {
    if (!file) {
      return { ok: false, message: 'Aucun fichier sélectionné.' };
    }
    const maxBytes = Math.max(1, maxMb) * 1024 * 1024;
    if (file.size > maxBytes) {
      return { ok: false, message: `Fichier trop volumineux (max ${maxMb} Mo).` };
    }
    if (!allowedMime.includes(file.type)) {
      return { ok: false, message: 'Format non autorisé. Utilisez JPG, PNG ou WebP.' };
    }
    return { ok: true, message: 'Fichier valide.' };
  };

  const initUploadInput = (input) => {
    if (!input) return;
    const maxMb = Number(input.dataset.maxMb || 2);
    const feedbackId = input.dataset.uploadFeedback || '';
    const nameId = input.dataset.uploadName || '';
    const previewId = input.dataset.uploadPreview || '';
    const placeholderId = input.dataset.uploadPlaceholder || '';
    const feedbackEl = feedbackId ? document.getElementById(feedbackId) : null;
    const nameEl = nameId ? document.getElementById(nameId) : null;
    const previewEl = previewId ? document.getElementById(previewId) : null;
    const placeholderEl = placeholderId ? document.getElementById(placeholderId) : null;
    const stateEl = document.querySelector(`[data-upload-state="${input.id}"]`);

    resetPreview(previewEl, placeholderEl);
    setFeedback(feedbackEl, '');

    const initialHasPreview = !!(previewEl && !previewEl.classList.contains('hidden') && (previewEl.getAttribute('src') || '').trim() !== '');
    if (stateEl) {
      stateEl.textContent = initialHasPreview ? 'État: fichier actuel chargé.' : 'État: aucune sélection.';
    }

    const applyFile = (file) => {
      const validation = validateFile(file, maxMb);
      if (!validation.ok) {
        input.value = '';
        if (nameEl) {
          nameEl.textContent = 'Aucun nouveau fichier choisi';
        }
        if (stateEl) {
          stateEl.textContent = 'État: erreur de fichier.';
        }
        setFeedback(feedbackEl, validation.message, 'error');
        return false;
      }
      if (nameEl) {
        nameEl.textContent = file.name;
      }
      if (stateEl) {
        stateEl.textContent = 'État: prêt pour enregistrement.';
      }
      updatePreview(previewEl, placeholderEl, file);
      setFeedback(feedbackEl, 'Fichier prêt à être envoyé.', 'success');
      return true;
    };

    input.addEventListener('change', () => {
      const file = input.files && input.files.length > 0 ? input.files[0] : null;
      if (!file) {
        if (nameEl) {
          nameEl.textContent = 'Aucun nouveau fichier choisi';
        }
        if (stateEl) {
          stateEl.textContent = initialHasPreview ? 'État: fichier actuel chargé.' : 'État: aucune sélection.';
        }
        setFeedback(feedbackEl, '');
        return;
      }
      applyFile(file);
    });

    const trigger = document.querySelector(`[data-upload-trigger="${input.id}"]`);
    if (trigger) {
      trigger.addEventListener('click', () => input.click());
    }

    const dropzone = document.querySelector(`[data-upload-dropzone="${input.id}"]`);
    if (dropzone) {
      const enter = () => dropzone.classList.add('border-blue-400', 'bg-blue-50/60', 'dark:bg-blue-500/10');
      const leave = () => dropzone.classList.remove('border-blue-400', 'bg-blue-50/60', 'dark:bg-blue-500/10');
      ['dragenter', 'dragover'].forEach((evt) => {
        dropzone.addEventListener(evt, (event) => {
          event.preventDefault();
          enter();
        });
      });
      ['dragleave', 'dragend', 'drop'].forEach((evt) => {
        dropzone.addEventListener(evt, (event) => {
          event.preventDefault();
          leave();
        });
      });
      dropzone.addEventListener('drop', (event) => {
        const files = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files : null;
        const file = files && files.length > 0 ? files[0] : null;
        if (!file) {
          return;
        }
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        applyFile(file);
      });
    }
  };

  const initDirtyState = () => {
    const form = document.querySelector('[data-profile-form]');
    if (!form) return;
    const dirtyLabel = document.querySelector('[data-profile-dirty]');
    const cancelButton = document.querySelector('[data-profile-cancel]');
    const saveButton = document.querySelector('[data-profile-save]');
    const actionBar = document.querySelector('[data-profile-action-bar]');
    const pageBody = document.body || null;
    let isDirty = false;

    const renderDirty = () => {
      if (dirtyLabel) {
        dirtyLabel.textContent = isDirty
          ? 'Modifications non enregistrées.'
          : 'Aucune modification en attente.';
        dirtyLabel.dataset.state = isDirty ? 'dirty' : 'idle';
      }
      if (saveButton) {
        saveButton.disabled = !isDirty;
      }
      if (actionBar) {
        actionBar.classList.toggle('is-visible', isDirty);
        actionBar.setAttribute('aria-hidden', isDirty ? 'false' : 'true');
      }
      if (pageBody) {
        pageBody.classList.toggle('profile-mobile-actions-visible', isDirty);
      }
    };

    const markDirty = () => {
      if (isDirty) return;
      isDirty = true;
      renderDirty();
    };

    const clearDirty = () => {
      isDirty = false;
      renderDirty();
    };

    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', clearDirty);

    if (pageBody) {
      pageBody.classList.add('profile-actions-managed');
    }

    renderDirty();

    window.addEventListener('beforeunload', (event) => {
      if (!isDirty) return;
      event.preventDefault();
      event.returnValue = '';
    });

      if (cancelButton) {
      cancelButton.addEventListener('click', () => {
        const target = cancelButton.getAttribute('data-profile-cancel') || '/';
        if (!isDirty || window.confirm('Annuler les modifications non enregistrées ?')) {
          window.location.href = target;
        }
      });
    }

    renderDirty();
  };

  const initInlineValidation = () => {
    const profileForm = document.querySelector('[data-profile-form]');
    if (profileForm) {
      const emailInput = profileForm.querySelector('#email');
      const emailError = profileForm.querySelector('#email_inline_error');
      const phoneInput = profileForm.querySelector('#phone');
      const phoneError = profileForm.querySelector('#phone_inline_error');

      const validateEmail = () => {
        if (!emailInput) return true;
        const value = (emailInput.value || '').trim();
        if (!value) {
          setInputValidityState(emailInput, false);
          setInlineMessage(emailError, 'Email requis.');
          return false;
        }
        const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        setInputValidityState(emailInput, ok);
        setInlineMessage(emailError, ok ? 'Email valide.' : 'Format email invalide.', ok ? 'success' : 'error');
        return ok;
      };

      const validatePhone = () => {
        if (!phoneInput) return true;
        const rawPhone = (phoneInput.value || '').trim();
        const phoneDigits = rawPhone.replace(/\D+/g, '');
        let ok = true;

        if (rawPhone && phoneDigits.length < 6) {
          ok = false;
          setInlineMessage(phoneError, 'Numéro trop court (min. 6 chiffres).');
          setInputValidityState(phoneInput, false);
        } else {
          setInlineMessage(phoneError, rawPhone ? 'Numéro valide.' : '', rawPhone ? 'success' : 'neutral');
          setInputValidityState(phoneInput, true);
        }

        return ok;
      };

      emailInput && emailInput.addEventListener('input', validateEmail);
      phoneInput && phoneInput.addEventListener('input', validatePhone);

      profileForm.addEventListener('submit', (event) => {
        const okEmail = validateEmail();
        const okPhone = validatePhone();
        if (!okEmail || !okPhone) {
          event.preventDefault();
        }
      });
    }

    const passwordFormAction = document.querySelector('input[name="action"][value="password"]');
    const passwordForm = passwordFormAction ? passwordFormAction.closest('form') : null;
    if (passwordForm) {
      const currentInput = passwordForm.querySelector('#current_password');
      const newInput = passwordForm.querySelector('#new_password');
      const confirmInput = passwordForm.querySelector('#confirm_password');
      const currentErr = passwordForm.querySelector('#current_password_inline_error');
      const newErr = passwordForm.querySelector('#new_password_inline_error');
      const confirmErr = passwordForm.querySelector('#confirm_password_inline_error');

      const validatePasswords = () => {
        let ok = true;

        const currentValue = (currentInput?.value || '').trim();
        if (!currentValue) {
          ok = false;
          setInlineMessage(currentErr, 'Mot de passe actuel requis.');
          setInputValidityState(currentInput, false);
        } else {
          setInlineMessage(currentErr, '');
          setInputValidityState(currentInput, true);
        }

        const newValue = newInput?.value || '';
        if (newValue.length < 8) {
          ok = false;
          setInlineMessage(newErr, 'Minimum 8 caractères.');
          setInputValidityState(newInput, false);
        } else {
          setInlineMessage(newErr, 'Mot de passe valide.', 'success');
          setInputValidityState(newInput, true);
        }

        const confirmValue = confirmInput?.value || '';
        if (confirmValue !== newValue || !confirmValue) {
          ok = false;
          setInlineMessage(confirmErr, 'La confirmation ne correspond pas.');
          setInputValidityState(confirmInput, false);
        } else {
          setInlineMessage(confirmErr, 'Confirmation correcte.', 'success');
          setInputValidityState(confirmInput, true);
        }

        return ok;
      };

      currentInput && currentInput.addEventListener('input', validatePasswords);
      newInput && newInput.addEventListener('input', validatePasswords);
      confirmInput && confirmInput.addEventListener('input', validatePasswords);
      passwordForm.addEventListener('submit', (event) => {
        if (!validatePasswords()) {
          event.preventDefault();
        }
      });
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="file"][data-upload-feedback]').forEach((input) => {
      initUploadInput(input);
    });
    initDirtyState();
    initInlineValidation();
  });
})();
