document.addEventListener('DOMContentLoaded', () => {
    let modalLockCount = 0;
    const lockBodyScroll = () => {
        modalLockCount += 1;
        document.body.classList.add('overflow-hidden');
    };
    const unlockBodyScroll = () => {
        modalLockCount = Math.max(0, modalLockCount - 1);
        if (modalLockCount === 0) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    const forms = document.querySelectorAll('[data-promo-form]');
    forms.forEach((form) => {
        const select = form.querySelector('[data-promo-type]');
        const amountWrap = form.querySelector('[data-promo-amount]');
        const percentWrap = form.querySelector('[data-promo-percent]');
        if (!select || !amountWrap || !percentWrap) {
            return;
        }
        const amountInput = amountWrap.querySelector('input, select, textarea');
        const percentInput = percentWrap.querySelector('input, select, textarea');
        const toggle = () => {
            const value = select.value;
            const showAmount = value === 'amount';
            const showPercent = value === 'percent';
            amountWrap.classList.toggle('hidden', !showAmount);
            percentWrap.classList.toggle('hidden', !showPercent);
            if (amountInput) amountInput.disabled = !showAmount;
            if (percentInput) percentInput.disabled = !showPercent;
        };
        select.addEventListener('change', toggle);
        toggle();

        const targetSelect = form.querySelector('[data-promo-target-type]');
        if (targetSelect) {
            const targetScopes = Array.from(form.querySelectorAll('[data-promo-target-scope]'));
            const toggleTargets = () => {
                const value = targetSelect.value;
                targetScopes.forEach((scope) => {
                    const scopeValue = scope.getAttribute('data-promo-target-scope') || '';
                    const show = scopeValue === value;
                    scope.classList.toggle('hidden', !show);
                    const fields = scope.querySelectorAll('input, select, textarea');
                    fields.forEach((field) => {
                        field.disabled = !show;
                    });
                });
            };
            targetSelect.addEventListener('change', toggleTargets);
            toggleTargets();
        }
    });

    const createModals = Array.from(document.querySelectorAll('[data-promo-create-modal]'));
    const createTriggers = Array.from(document.querySelectorAll('[data-create-trigger][data-create-modal-id]'));
    const createCloseTimers = new WeakMap();
    const createLastTriggers = new WeakMap();

    const openCreateModal = (modal, trigger = null) => {
        if (!modal || !modal.classList.contains('hidden')) {
            return;
        }
        const backdrop = modal.querySelector('[data-create-backdrop]');
        const panel = modal.querySelector('[data-create-panel]');

        if (trigger) {
            createLastTriggers.set(modal, trigger);
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        lockBodyScroll();

        const closeTimer = createCloseTimers.get(modal);
        if (closeTimer) {
            window.clearTimeout(closeTimer);
            createCloseTimers.delete(modal);
        }

        window.requestAnimationFrame(() => {
            if (backdrop) {
                backdrop.classList.remove('opacity-0');
            }
            if (panel) {
                panel.classList.remove('opacity-0', 'translate-y-3', 'scale-95');
            }
            const firstField = modal.querySelector('form [name="course_id"], form input, form select, form textarea');
            if (firstField instanceof HTMLElement) {
                firstField.focus();
            }
        });
    };

    const closeCreateModal = (modal, restoreFocus = true) => {
        if (!modal || modal.classList.contains('hidden')) {
            return;
        }
        const backdrop = modal.querySelector('[data-create-backdrop]');
        const panel = modal.querySelector('[data-create-panel]');

        const existingTimer = createCloseTimers.get(modal);
        if (existingTimer) {
            window.clearTimeout(existingTimer);
            createCloseTimers.delete(modal);
        }

        if (backdrop) {
            backdrop.classList.add('opacity-0');
        }
        if (panel) {
            panel.classList.add('opacity-0', 'translate-y-3', 'scale-95');
        }
        modal.setAttribute('aria-hidden', 'true');

        const timer = window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            unlockBodyScroll();
            if (restoreFocus) {
                const lastTrigger = createLastTriggers.get(modal);
                if (lastTrigger instanceof HTMLElement) {
                    lastTrigger.focus();
                }
            }
            createCloseTimers.delete(modal);
        }, 200);
        createCloseTimers.set(modal, timer);
    };

    if (createModals.length) {
        createTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const modalId = trigger.getAttribute('data-create-modal-id');
                if (!modalId) {
                    return;
                }
                const modal = document.getElementById(modalId);
                if (!modal) {
                    return;
                }
                openCreateModal(modal, trigger);
            });
        });

        createModals.forEach((modal) => {
            const cancelBtns = modal.querySelectorAll('[data-create-cancel]');
            const backdrop = modal.querySelector('[data-create-backdrop]');
            cancelBtns.forEach((btn) => {
                btn.addEventListener('click', () => closeCreateModal(modal));
            });
            if (backdrop) {
                backdrop.addEventListener('click', () => closeCreateModal(modal));
            }

            if (modal.getAttribute('data-create-open-on-load') === '1') {
                openCreateModal(modal);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }
            const openedModal = createModals.find((modal) => !modal.classList.contains('hidden'));
            if (openedModal) {
                closeCreateModal(openedModal);
            }
        });
    }

    const genericEditModal = document.querySelector('[data-promo-edit-modal-generic]');
    const genericEditTriggers = Array.from(document.querySelectorAll('[data-edit-source-id]'));
    let movedEditForm = null;
    let movedEditPlaceholder = null;
    let genericEditCloseTimer = null;

    const closeGenericEditModal = () => {
        if (!genericEditModal || genericEditModal.classList.contains('hidden')) {
            return;
        }
        const backdrop = genericEditModal.querySelector('[data-edit-generic-backdrop]');
        const panel = genericEditModal.querySelector('[data-edit-generic-panel]');
        if (genericEditCloseTimer) {
            window.clearTimeout(genericEditCloseTimer);
            genericEditCloseTimer = null;
        }
        if (backdrop) {
            backdrop.classList.add('opacity-0');
        }
        if (panel) {
            panel.classList.add('opacity-0', 'translate-y-3', 'scale-95');
        }
        genericEditModal.setAttribute('aria-hidden', 'true');
        genericEditCloseTimer = window.setTimeout(() => {
            genericEditModal.classList.add('hidden');
            genericEditModal.classList.remove('flex');
            if (movedEditForm && movedEditPlaceholder && movedEditPlaceholder.parentNode) {
                movedEditPlaceholder.parentNode.insertBefore(movedEditForm, movedEditPlaceholder);
                movedEditPlaceholder.remove();
            }
            movedEditForm = null;
            movedEditPlaceholder = null;
            unlockBodyScroll();
        }, 200);
    };

    if (genericEditModal && genericEditTriggers.length) {
        const editBody = genericEditModal.querySelector('[data-edit-generic-body]');
        const editBackdrop = genericEditModal.querySelector('[data-edit-generic-backdrop]');
        const editPanel = genericEditModal.querySelector('[data-edit-generic-panel]');
        const editCancelButtons = genericEditModal.querySelectorAll('[data-edit-generic-cancel]');

        const openGenericEditModal = (sourceId) => {
            if (!editBody) {
                return;
            }
            const source = document.getElementById(sourceId || '');
            if (!source) {
                return;
            }
            const sourceForm = source.querySelector('form[data-promo-form]');
            if (!sourceForm) {
                return;
            }

            if (movedEditForm && movedEditPlaceholder && movedEditPlaceholder.parentNode) {
                movedEditPlaceholder.parentNode.insertBefore(movedEditForm, movedEditPlaceholder);
                movedEditPlaceholder.remove();
            }

            movedEditPlaceholder = document.createElement('div');
            movedEditPlaceholder.className = 'hidden';
            sourceForm.parentNode.insertBefore(movedEditPlaceholder, sourceForm);
            movedEditForm = sourceForm;
            editBody.appendChild(sourceForm);

            if (source instanceof HTMLDetailsElement) {
                source.open = false;
            }

            genericEditModal.classList.remove('hidden');
            genericEditModal.classList.add('flex');
            genericEditModal.setAttribute('aria-hidden', 'false');
            lockBodyScroll();
            if (genericEditCloseTimer) {
                window.clearTimeout(genericEditCloseTimer);
                genericEditCloseTimer = null;
            }
            window.requestAnimationFrame(() => {
                if (editBackdrop) {
                    editBackdrop.classList.remove('opacity-0');
                }
                if (editPanel) {
                    editPanel.classList.remove('opacity-0', 'translate-y-3', 'scale-95');
                }
                const firstField = sourceForm.querySelector('input, select, textarea, button');
                if (firstField instanceof HTMLElement) {
                    firstField.focus();
                }
            });
        };

        genericEditTriggers.forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                const tagName = trigger.tagName.toUpperCase();
                if (tagName === 'SUMMARY' || tagName === 'A' || tagName === 'BUTTON') {
                    event.preventDefault();
                }
                if (tagName === 'SUMMARY') {
                    event.stopPropagation();
                }
                const sourceId = trigger.getAttribute('data-edit-source-id') || '';
                openGenericEditModal(sourceId);
            });
        });

        editCancelButtons.forEach((button) => {
            button.addEventListener('click', closeGenericEditModal);
        });
        if (editBackdrop) {
            editBackdrop.addEventListener('click', closeGenericEditModal);
        }
        const sourceToOpen = document.querySelector('[data-promo-edit-source][data-edit-open-on-load="1"]');
        if (sourceToOpen && sourceToOpen.id) {
            openGenericEditModal(sourceToOpen.id);
        }
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !genericEditModal.classList.contains('hidden')) {
                closeGenericEditModal();
            }
        });
    }

    const listFilterWrap = document.querySelector('[data-promo-list-filters]');
    if (listFilterWrap) {
        const listFilterButtons = Array.from(listFilterWrap.querySelectorAll('[data-promo-list-filter]'));
        const listSections = Array.from(document.querySelectorAll('[data-promo-list-section][data-promo-list-type]'));
        const createSections = Array.from(document.querySelectorAll('[data-promo-create-section][data-promo-mode]'));
        const visibleCount = document.querySelector('[data-promo-visible-count]');
        const cards = Array.from(document.querySelectorAll('[data-promo-card][data-promo-card-type]'));
        const hasPromotionCards = cards.some((card) => (card.getAttribute('data-promo-card-type') || '') === 'promotion');
        const hasCouponCards = cards.some((card) => (card.getAttribute('data-promo-card-type') || '') === 'coupon');
        const useClientFilter = hasPromotionCards && hasCouponCards;

        const sectionCount = (section) => {
            const countAttr = parseInt(section.getAttribute('data-promo-items') || '0', 10);
            if (!Number.isNaN(countAttr) && countAttr >= 0) {
                return countAttr;
            }
            return section.querySelectorAll('[data-promo-card]').length;
        };

        const isDarkTheme = () =>
            document.documentElement.classList.contains('dark') ||
            document.documentElement.getAttribute('data-theme') === 'dark';

        const applyImportantStyle = (node, property, value) => {
            if (!node || !node.style) {
                return;
            }
            node.style.setProperty(property, value, 'important');
        };

        const applyFilterButtonStyle = (button, isActive) => {
            if (!button) {
                return;
            }
            const dark = isDarkTheme();
            if (isActive) {
                applyImportantStyle(button, 'border-color', '#1d4ed8');
                applyImportantStyle(button, 'background', '#2563eb');
                applyImportantStyle(button, 'color', '#ffffff');
                applyImportantStyle(button, 'box-shadow', '0 16px 28px -18px rgba(37, 99, 235, 0.55)');
                return;
            }
            if (dark) {
                applyImportantStyle(button, 'border-color', '#334155');
                applyImportantStyle(button, 'background', '#0f172a');
                applyImportantStyle(button, 'color', '#e2e8f0');
                applyImportantStyle(button, 'box-shadow', '0 10px 18px -16px rgba(2, 6, 23, 0.6)');
                return;
            }
            applyImportantStyle(button, 'border-color', '#d1d5db');
            applyImportantStyle(button, 'background', '#f1f5f9');
            applyImportantStyle(button, 'color', '#475569');
            applyImportantStyle(button, 'box-shadow', '0 10px 18px -16px rgba(15, 23, 42, 0.18)');
        };

        const updateButtons = (currentFilter) => {
            listFilterButtons.forEach((button) => {
                const filterValue = button.getAttribute('data-promo-list-filter') || 'all';
                const isActive = filterValue === currentFilter;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                applyFilterButtonStyle(button, isActive);
            });
        };

        const applyFilter = (filterValue) => {
            const normalized = ['all', 'promotion', 'coupon'].includes(filterValue) ? filterValue : 'all';
            let currentVisible = 0;

            if (useClientFilter) {
                cards.forEach((card) => {
                    const cardType = card.getAttribute('data-promo-card-type') || 'promotion';
                    const showCard = normalized === 'all' || cardType === normalized;
                    card.classList.toggle('hidden', !showCard);
                    if (showCard) {
                        currentVisible += 1;
                    }
                });
                listSections.forEach((section) => {
                    section.classList.remove('hidden');
                });
            } else {
                listSections.forEach((section) => {
                    const sectionType = section.getAttribute('data-promo-list-type') || '';
                    const showSection = normalized === 'all' || sectionType === normalized;
                    section.classList.toggle('hidden', !showSection);
                    if (showSection) {
                        currentVisible += sectionCount(section);
                    }
                });
            }

            createSections.forEach((section) => {
                const sectionMode = section.getAttribute('data-promo-mode') || '';
                const showSection = normalized === 'all' || sectionMode === normalized;
                section.classList.toggle('hidden', !showSection);
            });

            if (visibleCount) {
                visibleCount.textContent = String(currentVisible);
            }
            updateButtons(normalized);
        };

        listFilterButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                const filterValue = button.getAttribute('data-promo-list-filter') || 'all';
                if (useClientFilter && button.tagName === 'A') {
                    event.preventDefault();
                    if (button.href) {
                        window.history.pushState({}, '', button.href);
                    }
                }
                applyFilter(filterValue);
            });
        });

        const defaultFilter = listFilterWrap.getAttribute('data-default-filter') || 'all';
        applyFilter(defaultFilter);

        document.addEventListener('click', (event) => {
            if (!event.target || typeof event.target.closest !== 'function') {
                return;
            }
            const themeToggle = event.target.closest('[data-theme-toggle], #theme-toggle');
            if (!themeToggle) {
                return;
            }
            window.setTimeout(() => {
                const activeButton = listFilterButtons.find((button) =>
                    button.getAttribute('aria-pressed') === 'true'
                );
                const activeValue = activeButton?.getAttribute('data-promo-list-filter') || defaultFilter;
                updateButtons(activeValue);
            }, 60);
        });
    }

    const dynamicFilterForms = Array.from(document.querySelectorAll('[data-promo-period-form]'));
    dynamicFilterForms.forEach((form) => {
        const typeSelect = form.querySelector('[data-filter-type]');
        const valueFields = Array.from(form.querySelectorAll('[data-filter-field]'));
        if (!typeSelect || valueFields.length === 0) {
            return;
        }

        const syncFilterField = () => {
            const selectedType = (typeSelect.value || 'q').trim();
            valueFields.forEach((field) => {
                const fieldType = (field.getAttribute('data-filter-field') || '').trim();
                const isActive = fieldType === selectedType;
                field.classList.toggle('hidden', !isActive);
                const controls = field.querySelectorAll('input, select, textarea');
                controls.forEach((control) => {
                    control.disabled = !isActive;
                });
            });
        };

        typeSelect.addEventListener('change', syncFilterField);
        syncFilterField();
    });

    const deleteForms = document.querySelectorAll('[data-promo-delete]');
    const modal = document.getElementById('promo-delete-modal');
    if (deleteForms.length) {
        if (!modal) {
            deleteForms.forEach((form) => {
                form.addEventListener('submit', (event) => {
                    const message = form.getAttribute('data-confirm-message') || 'Supprimer cette promotion ?';
                    if (!window.confirm(message)) {
                        event.preventDefault();
                    }
                });
            });
        } else {
            const messageEl = modal.querySelector('[data-modal-message]');
            const courseEl = modal.querySelector('[data-modal-course]');
            const cancelBtns = modal.querySelectorAll('[data-modal-cancel]');
            const confirmBtn = modal.querySelector('[data-modal-confirm]');
            const backdrop = modal.querySelector('[data-modal-backdrop]');
            const panel = modal.querySelector('[data-modal-panel]');
            let pendingForm = null;
            let closeTimer = null;

            const closeModal = () => {
                if (closeTimer) {
                    window.clearTimeout(closeTimer);
                    closeTimer = null;
                }
                if (backdrop) {
                    backdrop.classList.add('opacity-0');
                }
                if (panel) {
                    panel.classList.add('opacity-0', 'translate-y-3', 'scale-95');
                }
                modal.setAttribute('aria-hidden', 'true');
                closeTimer = window.setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    unlockBodyScroll();
                    pendingForm = null;
                }, 200);
            };

            const openModal = (form) => {
                pendingForm = form;
                const message = form.getAttribute('data-confirm-message') || 'Supprimer cette promotion ?';
                const courseTitle = form.getAttribute('data-course-title') || 'Cours';
                if (messageEl) {
                    messageEl.textContent = message;
                }
                if (courseEl) {
                    courseEl.textContent = `Cours: ${courseTitle}`;
                }
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                lockBodyScroll();
                modal.setAttribute('aria-hidden', 'false');
                if (closeTimer) {
                    window.clearTimeout(closeTimer);
                    closeTimer = null;
                }
                window.requestAnimationFrame(() => {
                    if (backdrop) {
                        backdrop.classList.remove('opacity-0');
                    }
                    if (panel) {
                        panel.classList.remove('opacity-0', 'translate-y-3', 'scale-95');
                    }
                });
                if (confirmBtn) {
                    confirmBtn.focus();
                }
            };

            deleteForms.forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    openModal(form);
                });
            });

            cancelBtns.forEach((btn) => {
                btn.addEventListener('click', closeModal);
            });
            if (backdrop) {
                backdrop.addEventListener('click', closeModal);
            }
            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    if (pendingForm) {
                        pendingForm.submit();
                    }
                });
            }
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        }
    }

    const copyButtons = Array.from(document.querySelectorAll('[data-copy-text]'));
    copyButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const rawText = button.getAttribute('data-copy-text') || '';
            const text = rawText.trim();
            if (!text) {
                return;
            }
            const label = button.getAttribute('data-copy-label') || 'Copier';
            const successLabel = button.getAttribute('data-copy-success') || 'Copié';
            const originalText = button.textContent || label;
            const setLabel = (value) => {
                button.textContent = value;
            };
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
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
                setLabel(successLabel);
                button.disabled = true;
                window.setTimeout(() => {
                    button.disabled = false;
                    setLabel(originalText || label);
                }, 1600);
            } catch (error) {
                setLabel('Erreur');
                window.setTimeout(() => {
                    setLabel(originalText || label);
                }, 1600);
            }
        });
    });

    const targetModal = document.getElementById('promo-target-modal');
    const targetTriggers = document.querySelectorAll('[data-target-trigger]');
    if (!targetModal || !targetTriggers.length) {
        return;
    }

    const targetBackdrop = targetModal.querySelector('[data-target-backdrop]');
    const targetPanel = targetModal.querySelector('[data-target-panel]');
    const targetList = targetModal.querySelector('[data-target-list]');
    const targetSearch = targetModal.querySelector('[data-target-search]');
    const targetCount = targetModal.querySelector('[data-target-count]');
    const targetLimit = targetModal.querySelector('[data-target-limit]');
    const targetSelectLimit = targetModal.querySelector('[data-target-select-limit]');
    const targetSelectAll = targetModal.querySelector('[data-target-select-all]');
    const targetClear = targetModal.querySelector('[data-target-clear]');
    const targetApply = targetModal.querySelector('[data-target-apply]');
    const targetCancelBtns = targetModal.querySelectorAll('[data-target-cancel]');
    const targetItems = Array.from(targetModal.querySelectorAll('[data-target-item]'));
    const targetCheckboxes = Array.from(targetModal.querySelectorAll('[data-target-checkbox]'));
    let activeTargetInput = null;
    let targetCloseTimer = null;
    let lastTrigger = null;
    let targetSearchFrame = null;
    let lastSearchValue = '';

    const parseEmails = (value) =>
        value
            .split(/[\s,;]+/g)
            .map((item) => item.trim().toLowerCase())
            .filter((item) => item.length > 0);

    const getVisibleItems = () => targetItems.filter((item) => !item.classList.contains('hidden'));

    const getSelectedEmails = () =>
        targetCheckboxes
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => checkbox.getAttribute('data-target-email'))
            .filter(Boolean);

    const updateTargetCount = () => {
        if (targetCount) {
            targetCount.textContent = String(getSelectedEmails().length);
        }
    };

    const applyTargetFilter = (rawValue) => {
        const value = (rawValue || '').trim().toLowerCase();
        if (value === lastSearchValue) {
            return;
        }
        lastSearchValue = value;
        targetItems.forEach((item) => {
            const text = item.getAttribute('data-target-search-text') || '';
            item.classList.toggle('hidden', value !== '' && !text.includes(value));
        });
    };

    const openTargetModal = (trigger) => {
        const form = trigger.closest('form');
        if (!form) {
            return;
        }
        const input = form.querySelector('[data-target-input]');
        if (!input) {
            return;
        }
        activeTargetInput = input;
        lastTrigger = trigger;
        if (targetSearch) {
            targetSearch.value = '';
        }
        applyTargetFilter('');
        const selected = new Set(parseEmails(input.value));
        targetCheckboxes.forEach((checkbox) => {
            const email = (checkbox.getAttribute('data-target-email') || '').toLowerCase();
            checkbox.checked = selected.has(email);
        });
        updateTargetCount();
        targetModal.classList.remove('hidden');
        targetModal.classList.add('flex');
        lockBodyScroll();
        targetModal.setAttribute('aria-hidden', 'false');
        if (targetCloseTimer) {
            window.clearTimeout(targetCloseTimer);
            targetCloseTimer = null;
        }
        window.requestAnimationFrame(() => {
            if (targetBackdrop) {
                targetBackdrop.classList.remove('opacity-0');
            }
            if (targetPanel) {
                targetPanel.classList.remove('opacity-0', 'translate-y-3', 'scale-95');
            }
            if (targetSearch) {
                targetSearch.focus();
            }
        });
    };

    const closeTargetModal = () => {
        if (targetCloseTimer) {
            window.clearTimeout(targetCloseTimer);
            targetCloseTimer = null;
        }
        if (targetBackdrop) {
            targetBackdrop.classList.add('opacity-0');
        }
        if (targetPanel) {
            targetPanel.classList.add('opacity-0', 'translate-y-3', 'scale-95');
        }
        targetModal.setAttribute('aria-hidden', 'true');
        targetCloseTimer = window.setTimeout(() => {
            targetModal.classList.add('hidden');
            targetModal.classList.remove('flex');
            unlockBodyScroll();
            if (lastTrigger) {
                lastTrigger.focus();
            }
            if (targetSearch) {
                targetSearch.value = '';
            }
            if (targetLimit) {
                targetLimit.value = '';
            }
            applyTargetFilter('');
            activeTargetInput = null;
        }, 200);
    };

    targetTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => openTargetModal(trigger));
    });

    targetCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateTargetCount);
    });

    if (targetSearch) {
        targetSearch.addEventListener('input', () => {
            if (targetSearchFrame !== null) {
                window.cancelAnimationFrame(targetSearchFrame);
            }
            targetSearchFrame = window.requestAnimationFrame(() => {
                targetSearchFrame = null;
                applyTargetFilter(targetSearch.value);
            });
        });
    }

    if (targetSelectLimit) {
        targetSelectLimit.addEventListener('click', () => {
            const limitValue = targetLimit ? targetLimit.value : '0';
            const limit = parseInt(limitValue || '0', 10);
            if (!limit || limit < 1) {
                return;
            }
            targetCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            const visibleItems = getVisibleItems();
            for (let i = 0; i < visibleItems.length && i < limit; i += 1) {
                const checkbox = visibleItems[i].querySelector('[data-target-checkbox]');
                if (checkbox) {
                    checkbox.checked = true;
                }
            }
            updateTargetCount();
        });
    }

    if (targetSelectAll) {
        targetSelectAll.addEventListener('click', () => {
            getVisibleItems().forEach((item) => {
                const checkbox = item.querySelector('[data-target-checkbox]');
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            updateTargetCount();
        });
    }

    if (targetClear) {
        targetClear.addEventListener('click', () => {
            targetCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            updateTargetCount();
        });
    }

    if (targetApply) {
        targetApply.addEventListener('click', () => {
            if (activeTargetInput) {
                const emails = getSelectedEmails();
                activeTargetInput.value = emails.join(', ');
            }
            closeTargetModal();
        });
    }

    targetCancelBtns.forEach((btn) => {
        btn.addEventListener('click', closeTargetModal);
    });
    if (targetBackdrop) {
        targetBackdrop.addEventListener('click', closeTargetModal);
    }
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !targetModal.classList.contains('hidden')) {
            closeTargetModal();
        }
    });
});
