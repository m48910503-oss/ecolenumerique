(function () {
  'use strict';

  const lockBodyScroll = (() => {
    let lockCount = 0;
    return {
      lock() {
        lockCount += 1;
        document.body.classList.add('overflow-hidden');
      },
      unlock() {
        lockCount = Math.max(0, lockCount - 1);
        if (lockCount === 0) {
          document.body.classList.remove('overflow-hidden');
        }
      },
    };
  })();

  const setTabState = (activeTab, inactiveTab, activeContent, inactiveContent) => {
    if (!activeTab || !inactiveTab || !activeContent || !inactiveContent) {
      return;
    }

    activeTab.classList.add('is-active');
    activeTab.classList.remove('is-inactive');
    activeTab.setAttribute('aria-pressed', 'true');
    activeTab.setAttribute('aria-current', 'page');
    inactiveTab.classList.remove('is-active');
    inactiveTab.classList.add('is-inactive');
    inactiveTab.setAttribute('aria-pressed', 'false');
    inactiveTab.removeAttribute('aria-current');
    activeContent.classList.remove('hidden');
    inactiveContent.classList.add('hidden');
  };

  const numberFormatter = new Intl.NumberFormat('fr-FR');
  const formatMoney = (value) => `${numberFormatter.format(Math.round(value))} F CFA`;
  const TOAST_DURATION_MS = 3200;
  const TOAST_FADE_MS = 220;

  const createLocalToast = (message, type = 'info') => {
    const text = String(message || '').trim();
    if (!text) {
      return;
    }

    const tone = String(type || 'info').toLowerCase();
    const palette = {
      success: {
        title: 'Succès',
        icon: 'fas fa-circle-check',
        variant: 'success',
      },
      error: {
        title: 'Erreur',
        icon: 'fas fa-circle-exclamation',
        variant: 'error',
      },
      warning: {
        title: 'Attention',
        icon: 'fas fa-triangle-exclamation',
        variant: 'warning',
      },
      info: {
        title: 'Info',
        icon: 'fas fa-circle-info',
        variant: 'info',
      },
    };
    const theme = palette[tone] || palette.info;
    const existing = document.querySelector('[data-notification-local-toast="1"]');
    if (existing && existing.parentNode) {
      existing.parentNode.removeChild(existing);
    }

    const toast = document.createElement('div');
    toast.dataset.notificationLocalToast = '1';
    toast.className = `teacher-withdrawal-toast teacher-withdrawal-toast--${theme.variant}`;
    toast.setAttribute('role', tone === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-live', tone === 'error' ? 'assertive' : 'polite');

    const copyWrap = document.createElement('div');
    copyWrap.className = 'teacher-withdrawal-toast__copy';

    const icon = document.createElement('span');
    icon.className = 'teacher-withdrawal-toast__icon';
    icon.setAttribute('aria-hidden', 'true');

    const iconEl = document.createElement('i');
    iconEl.className = theme.icon;
    icon.appendChild(iconEl);

    const body = document.createElement('div');
    body.className = 'min-w-0';

    const title = document.createElement('p');
    title.className = 'teacher-withdrawal-toast__title';
    title.textContent = theme.title;

    const messageEl = document.createElement('p');
    messageEl.className = 'teacher-withdrawal-toast__message';
    messageEl.textContent = text;

    body.appendChild(title);
    body.appendChild(messageEl);
    copyWrap.appendChild(icon);
    copyWrap.appendChild(body);

    const barWrap = document.createElement('span');
    barWrap.className = 'teacher-withdrawal-toast__bar';
    barWrap.setAttribute('aria-hidden', 'true');

    toast.appendChild(copyWrap);
    toast.appendChild(barWrap);
    document.body.appendChild(toast);

    window.setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, TOAST_DURATION_MS + TOAST_FADE_MS + 180);
  };

  const initPromotionFlash = () => {
    const flash = document.querySelector('[data-notification-toast]');
    if (!(flash instanceof HTMLElement)) {
      return;
    }

    const message = flash.getAttribute('data-notification-toast-message') || flash.textContent || '';
    const type = flash.getAttribute('data-notification-toast-type') || 'success';
    if (message.trim() !== '') {
      createLocalToast(message, type);
    }
    flash.remove();
  };

  const parseInputNumber = (input) => {
    if (!(input instanceof HTMLInputElement)) {
      return null;
    }
    const rawValue = input.value.trim();
    if (rawValue === '') {
      return null;
    }
    const parsed = Number(rawValue.replace(',', '.'));
    return Number.isFinite(parsed) ? parsed : null;
  };

  const readSelectedCoursePrice = (select) => {
    if (!(select instanceof HTMLSelectElement)) {
      return null;
    }
    const option = select.selectedOptions && select.selectedOptions[0];
    if (!option) {
      return null;
    }
    const priceRaw = option.getAttribute('data-course-price');
    if (!priceRaw) {
      return null;
    }
    const parsed = Number(priceRaw);
    return Number.isFinite(parsed) ? parsed : null;
  };

  const readSelectedCourseLabel = (select) => {
    if (!(select instanceof HTMLSelectElement)) {
      return '';
    }
    const option = select.selectedOptions && select.selectedOptions[0];
    const rawLabel = option && option.textContent ? option.textContent.trim() : '';
    if (!rawLabel) {
      return '';
    }
    return rawLabel.split('·')[0].trim();
  };

  const openModal = (modal) => {
    if (!modal || !modal.classList.contains('hidden')) {
      return;
    }
    const panel = modal.querySelector('[data-modal-panel]') || modal.firstElementChild;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    lockBodyScroll.lock();
    window.requestAnimationFrame(() => {
      modal.classList.remove('opacity-0');
      if (panel instanceof HTMLElement) {
        panel.classList.remove('opacity-0', 'translate-y-3', 'scale-95');
      }
      const firstField = modal.querySelector('input, select, textarea, button');
      if (firstField instanceof HTMLElement) {
        firstField.focus();
      }
    });
  };

  const closeModal = (modal) => {
    if (!modal || modal.classList.contains('hidden')) {
      return;
    }

    const panel = modal.querySelector('[data-modal-panel]') || modal.firstElementChild;
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.add('opacity-0');
    if (panel instanceof HTMLElement) {
      panel.classList.add('opacity-0', 'translate-y-3', 'scale-95');
    }

    window.setTimeout(() => {
      modal.classList.add('hidden');
      modal.classList.remove('flex', 'opacity-0');
      lockBodyScroll.unlock();
    }, 180);
  };

  const bindModal = (openBtnId, modalId) => {
    const openBtn = document.getElementById(openBtnId);
    const modal = document.getElementById(modalId);
    if (!(modal instanceof HTMLElement)) {
      return;
    }

    const openTriggers = new Set(Array.from(document.querySelectorAll(`[data-modal-open="${modalId}"]`)));
    if (openBtn) {
      openTriggers.add(openBtn);
    }
    openTriggers.forEach((button) => {
      button.addEventListener('click', () => openModal(modal));
    });

    modal.querySelectorAll('.close-modal').forEach((button) => {
      button.addEventListener('click', () => closeModal(modal));
    });

    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal(modal);
      }
    });

    if (modal.dataset.createOpenOnLoad === '1') {
      openModal(modal);
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    initPromotionFlash();

    document.querySelectorAll('[data-promo-form]').forEach((form) => {
      const typeSelect = form.querySelector('[data-promo-type]');
      const amountWrap = form.querySelector('[data-promo-amount]');
      const percentWrap = form.querySelector('[data-promo-percent]');

      if (typeSelect && amountWrap && percentWrap) {
        const amountInput = amountWrap.querySelector('input, select, textarea');
        const percentInput = percentWrap.querySelector('input, select, textarea');

        const togglePricingFields = () => {
          const useAmount = typeSelect.value === 'amount';
          amountWrap.classList.toggle('hidden', !useAmount);
          percentWrap.classList.toggle('hidden', useAmount);
          if (amountInput) {
            amountInput.disabled = !useAmount;
          }
          if (percentInput) {
            percentInput.disabled = useAmount;
          }
        };

        typeSelect.addEventListener('change', togglePricingFields);
        togglePricingFields();
      }

      const preview = form.querySelector('[data-promo-preview]');
      if (preview) {
        const courseSelect = form.querySelector('select[name="course_id"]');
        const amountInput = form.querySelector('input[name="promo_amount"]');
        const percentInput = form.querySelector('input[name="promo_percent"]');
        const previewCourse = preview.querySelector('[data-promo-preview-course]');
        const previewCurrent = preview.querySelector('[data-promo-preview-current]');
        const previewDiscount = preview.querySelector('[data-promo-preview-discount]');
        const previewFinal = preview.querySelector('[data-promo-preview-final]');
        const previewSavings = preview.querySelector('[data-promo-preview-savings]');
        const previewBadge = preview.querySelector('[data-promo-preview-badge]');
        const summaryCourse = preview.querySelector('[data-promo-summary-course]');
        const summaryType = preview.querySelector('[data-promo-summary-type]');
        const summaryFinal = preview.querySelector('[data-promo-summary-final]');

        const updateSummary = (courseLabel, typeLabel, finalLabel) => {
          if (summaryCourse) {
            summaryCourse.textContent = courseLabel;
          }
          if (summaryType) {
            summaryType.textContent = typeLabel;
          }
          if (summaryFinal) {
            summaryFinal.textContent = finalLabel;
          }
        };

        const updatePreview = () => {
          const currentPrice = readSelectedCoursePrice(courseSelect);
          const promoType = typeSelect instanceof HTMLSelectElement ? typeSelect.value : 'percent';
          const amountValue = parseInputNumber(amountInput);
          const percentValue = parseInputNumber(percentInput);

          if (currentPrice === null || currentPrice <= 0) {
            if (previewCourse) {
              previewCourse.textContent = 'Sélectionnez un cours pour voir l\'impact de la réduction.';
            }
            if (previewCurrent) {
              previewCurrent.textContent = '—';
            }
            if (previewDiscount) {
              previewDiscount.textContent = '—';
            }
            if (previewFinal) {
              previewFinal.textContent = '—';
            }
            if (previewSavings) {
              previewSavings.textContent = 'Choisissez un cours et une valeur de réduction.';
            }
            if (previewBadge) {
              previewBadge.textContent = 'En attente';
            }
            updateSummary(
              'Sélectionnez un cours pour voir l\'impact de la réduction.',
              '—',
              '—'
            );
            return;
          }

          const selectedCourseLabel = readSelectedCourseLabel(courseSelect);
          if (previewCourse) {
            previewCourse.textContent = selectedCourseLabel
              ? `${selectedCourseLabel} · ${formatMoney(currentPrice)}`
              : `Cours sélectionné : ${formatMoney(currentPrice)}`;
          }
          if (previewCurrent) {
            previewCurrent.textContent = formatMoney(currentPrice);
          }

          let finalPrice = null;
          let discountLabel = '—';
          let badgeLabel = 'En attente';

          if (promoType === 'amount') {
            if (amountValue !== null && amountValue >= 0) {
              finalPrice = amountValue;
              discountLabel = 'Prix fixe';
              badgeLabel = 'Prix final';
            }
          } else {
            if (percentValue !== null && percentValue > 0) {
              const discount = (currentPrice * percentValue) / 100;
              finalPrice = Math.max(0, Math.round(currentPrice - discount));
              discountLabel = `-${numberFormatter.format(percentValue)}%`;
              badgeLabel = percentValue >= 100 ? 'Offert' : 'Réduction';
            }
          }

          if (previewDiscount) {
            previewDiscount.textContent = discountLabel;
          }
          if (previewBadge) {
            previewBadge.textContent = badgeLabel;
          }

          const summaryTypeLabel = promoType === 'amount'
            ? (amountValue !== null && amountValue >= 0 ? formatMoney(amountValue) : 'Nouveau prix')
            : (percentValue !== null && percentValue > 0 ? `-${numberFormatter.format(percentValue)}%` : 'Pourcentage');

          updateSummary(
            selectedCourseLabel || `Cours sélectionné : ${formatMoney(currentPrice)}`,
            summaryTypeLabel,
            finalPrice === null ? '—' : formatMoney(finalPrice)
          );

          if (finalPrice === null) {
            if (previewFinal) {
              previewFinal.textContent = '—';
            }
            if (previewSavings) {
              previewSavings.textContent = 'Saisissez une valeur pour voir le prix final.';
            }
            return;
          }

          if (previewFinal) {
            previewFinal.textContent = formatMoney(finalPrice);
          }

          const savings = Math.max(0, currentPrice - finalPrice);
          if (finalPrice === 0) {
            if (previewSavings) {
              previewSavings.textContent = 'Le cours sera offert gratuitement pendant cette période.';
            }
          } else if (finalPrice > currentPrice) {
            if (previewSavings) {
              previewSavings.textContent = 'Le prix final est supérieur au tarif actuel.';
            }
          } else if (previewSavings) {
            previewSavings.textContent = `Économie estimée : ${formatMoney(savings)}.`;
          }
        };

        if (typeSelect) {
          typeSelect.addEventListener('change', updatePreview);
        }
        if (courseSelect) {
          courseSelect.addEventListener('change', updatePreview);
        }
        if (amountInput) {
          amountInput.addEventListener('input', updatePreview);
          amountInput.addEventListener('change', updatePreview);
        }
        if (percentInput) {
          percentInput.addEventListener('input', updatePreview);
          percentInput.addEventListener('change', updatePreview);
        }
        updatePreview();
      }

      const scheduleModeSelect = form.querySelector('[data-promo-schedule-mode]');
      const scheduleDurationWrap = form.querySelector('[data-promo-schedule-duration]');
      const scheduleCustomWrap = form.querySelector('[data-promo-schedule-custom]');
      if (scheduleModeSelect && scheduleDurationWrap && scheduleCustomWrap) {
        const durationInput = scheduleDurationWrap.querySelector('input, select, textarea');
        const customFields = Array.from(scheduleCustomWrap.querySelectorAll('input, select, textarea'));

        const toggleScheduleFields = () => {
          const useCustomSchedule = scheduleModeSelect.value === 'custom';
          scheduleDurationWrap.classList.toggle('hidden', useCustomSchedule);
          scheduleCustomWrap.classList.toggle('hidden', !useCustomSchedule);
          if (durationInput) {
            durationInput.disabled = useCustomSchedule;
            durationInput.required = !useCustomSchedule;
          }
          customFields.forEach((field) => {
            field.disabled = !useCustomSchedule;
            if (field.getAttribute('type') !== 'hidden') {
              field.required = useCustomSchedule;
            }
          });
        };

        scheduleModeSelect.addEventListener('change', toggleScheduleFields);
        toggleScheduleFields();
      }

      const couponModeSelect = form.querySelector('[data-coupon-code-mode]');
      const couponCodeWrap = form.querySelector('[data-coupon-code-wrap]');
      const couponCodeInput = form.querySelector('[data-coupon-code-input]');
      if (couponModeSelect && couponCodeWrap) {
        const toggleCouponCodeField = () => {
          const useManualCode = couponModeSelect.value === 'manual';
          couponCodeWrap.classList.toggle('hidden', !useManualCode);
          if (couponCodeInput) {
            couponCodeInput.disabled = !useManualCode;
            couponCodeInput.required = useManualCode;
          }
        };

        couponModeSelect.addEventListener('change', toggleCouponCodeField);
        toggleCouponCodeField();
      }

      const targetSelect = form.querySelector('[data-promo-target-type]');
      if (targetSelect) {
        const targetScopes = Array.from(form.querySelectorAll('[data-promo-target-scope]'));

        const toggleTargetFields = () => {
          const targetValue = targetSelect.value;
          targetScopes.forEach((scope) => {
            const scopeValue = scope.getAttribute('data-promo-target-scope') || '';
            const showScope = scopeValue === targetValue;
            scope.classList.toggle('hidden', !showScope);
            scope.querySelectorAll('input, select, textarea').forEach((field) => {
              field.disabled = !showScope;
            });
          });
        };

        targetSelect.addEventListener('change', toggleTargetFields);
        toggleTargetFields();
      }

      form.addEventListener('submit', (event) => {
        if (typeSelect instanceof HTMLSelectElement && typeSelect.value === 'percent') {
          const percentValue = parseInputNumber(form.querySelector('input[name="promo_percent"]'));
          if (percentValue !== null && percentValue >= 100) {
            const confirmed = window.confirm('Cette réduction rendra votre cours gratuit pendant la période sélectionnée. Confirmer ?');
            if (!confirmed) {
              event.preventDefault();
            }
          }
        }
      });
    });

    const tabPromotions = document.getElementById('tab-promotions');
    const tabCoupons = document.getElementById('tab-coupons');
    const contentPromotions = document.getElementById('content-promotions');
    const contentCoupons = document.getElementById('content-coupons');

    if (tabPromotions && tabCoupons && contentPromotions && contentCoupons) {
      tabPromotions.addEventListener('click', () => {
        setTabState(tabPromotions, tabCoupons, contentPromotions, contentCoupons);
      });
      tabCoupons.addEventListener('click', () => {
        setTabState(tabCoupons, tabPromotions, contentCoupons, contentPromotions);
      });
    }

    document.querySelectorAll('[data-copy-text]').forEach((button) => {
      button.addEventListener('click', async () => {
        const text = (button.getAttribute('data-copy-text') || '').trim();
        if (!text) {
          return;
        }

        const successMessage = button.getAttribute('data-copy-success') || 'Copié';

        try {
          if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            await navigator.clipboard.writeText(text);
          } else {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', 'true');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
          }

          createLocalToast(successMessage, 'success');
        } catch (error) {
          createLocalToast('Copie impossible', 'error');
        }
      });
    });

    bindModal('', 'delete-confirm-modal');

    const editModals = Array.from(document.querySelectorAll('[data-edit-modal="1"]'));

    const mountEditOverlay = (row) => {
      const overlay = row.querySelector('[data-edit-backdrop]');
      if (!(overlay instanceof HTMLElement)) {
        return null;
      }
      if (!row._editOverlayHome) {
        row._editOverlayHome = {
          parent: overlay.parentNode,
          next: overlay.nextSibling,
        };
      }
      row._editOverlay = overlay;
      if (overlay.parentNode !== document.body) {
        document.body.appendChild(overlay);
      }
      return overlay;
    };

    const restoreEditOverlay = (row) => {
      const home = row._editOverlayHome;
      if (!home || !(home.parent instanceof Node)) {
        return;
      }
      const overlay = row._editOverlay;
      if (!(overlay instanceof HTMLElement)) {
        return;
      }
      home.parent.insertBefore(overlay, home.next instanceof Node ? home.next : null);
    };

    const closeEditModal = (row) => {
      if (!(row instanceof HTMLElement) || row.classList.contains('hidden')) {
        return;
      }
      restoreEditOverlay(row);
      row.classList.add('hidden');
      lockBodyScroll.unlock();
    };

    const openEditModal = (row) => {
      if (!(row instanceof HTMLElement)) {
        return;
      }
      editModals.forEach((modalRow) => {
        if (modalRow !== row) {
          closeEditModal(modalRow);
        }
      });
      if (!row.classList.contains('hidden')) {
        return;
      }
      mountEditOverlay(row);
      row.classList.remove('hidden');
      lockBodyScroll.lock();
      window.requestAnimationFrame(() => {
        const firstField = document.body.querySelector(
          '[data-edit-backdrop] input:not([type="hidden"]):not([disabled]), [data-edit-backdrop] select:not([disabled]), [data-edit-backdrop] textarea:not([disabled]), [data-edit-backdrop] button:not([disabled])'
        );
        if (firstField instanceof HTMLElement) {
          firstField.focus();
        }
      });
    };

    document.querySelectorAll('[data-edit-toggle]').forEach((button) => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-edit-toggle');
        if (!targetId) {
          return;
        }
        const row = document.getElementById(targetId);
        if (!(row instanceof HTMLElement)) {
          return;
        }
        if (row.classList.contains('hidden')) {
          openEditModal(row);
        } else {
          closeEditModal(row);
        }
      });
    });

    editModals.forEach((row) => {
      const backdrop = row.querySelector('[data-edit-backdrop]');
      if (backdrop instanceof HTMLElement) {
        backdrop.addEventListener('click', (event) => {
          if (event.target === backdrop) {
            closeEditModal(row);
          }
        });
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') {
        return;
      }
      const opened = editModals.find((row) => row instanceof HTMLElement && !row.classList.contains('hidden'));
      if (opened) {
        closeEditModal(opened);
      }
    });

    document.querySelectorAll('[data-edit-open-on-load="1"]').forEach((row) => {
      openEditModal(row);
    });

    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const deleteConfirmTitle = deleteConfirmModal ? deleteConfirmModal.querySelector('[data-delete-modal-title]') : null;
    const deleteConfirmMessage = deleteConfirmModal ? deleteConfirmModal.querySelector('[data-delete-modal-message]') : null;
    const deleteConfirmButton = deleteConfirmModal ? deleteConfirmModal.querySelector('[data-delete-modal-confirm]') : null;
    const deleteConfirmButtonLabel = deleteConfirmModal ? deleteConfirmModal.querySelector('[data-delete-modal-confirm-label]') : null;
    let pendingDeleteForm = null;

    const resetDeleteModalState = () => {
      pendingDeleteForm = null;
      if (deleteConfirmButton instanceof HTMLButtonElement) {
        deleteConfirmButton.disabled = false;
      }
      if (deleteConfirmButtonLabel instanceof HTMLElement) {
        deleteConfirmButtonLabel.textContent = 'Archiver';
      }
    };

    if (deleteConfirmModal instanceof HTMLElement) {
      deleteConfirmModal.querySelectorAll('.close-modal').forEach((button) => {
        button.addEventListener('click', resetDeleteModalState);
      });
      deleteConfirmModal.addEventListener('click', (event) => {
        if (event.target === deleteConfirmModal) {
          resetDeleteModalState();
        }
      });
    }

    if (deleteConfirmButton instanceof HTMLButtonElement) {
      deleteConfirmButton.addEventListener('click', () => {
        if (!(pendingDeleteForm instanceof HTMLFormElement)) {
          closeModal(deleteConfirmModal);
          resetDeleteModalState();
          return;
        }

        deleteConfirmButton.disabled = true;
        if (deleteConfirmButtonLabel instanceof HTMLElement) {
          deleteConfirmButtonLabel.textContent = 'Archivage...';
        }
        pendingDeleteForm.submit();
      });
    }

    document.querySelectorAll('form[data-delete-confirm]').forEach((form) => {
      form.addEventListener('submit', (event) => {
        if (!(deleteConfirmModal instanceof HTMLElement)) {
          return;
        }

        event.preventDefault();
        pendingDeleteForm = form;

        const title = form.getAttribute('data-delete-title')
          || `${form.getAttribute('data-delete-action') || 'Supprimer'} ${form.getAttribute('data-delete-label') || 'cet élément'} ?`;
        const message = form.getAttribute('data-delete-message')
          || `${form.getAttribute('data-delete-label') || 'Cet élément'} sera retiré de la liste active.`;
        const confirmLabel = form.getAttribute('data-delete-confirm-label') || 'Confirmer';

        if (deleteConfirmTitle instanceof HTMLElement) {
          deleteConfirmTitle.textContent = title;
        }
        if (deleteConfirmMessage instanceof HTMLElement) {
          deleteConfirmMessage.textContent = message;
        }
        if (deleteConfirmButtonLabel instanceof HTMLElement) {
          deleteConfirmButtonLabel.textContent = confirmLabel;
        }
        if (deleteConfirmButton instanceof HTMLButtonElement) {
          deleteConfirmButton.disabled = false;
        }

        openModal(deleteConfirmModal);
      });
    });
  });
})();
