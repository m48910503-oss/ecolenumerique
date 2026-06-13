(function () {
    function initTeacherCoursesPage() {
        initActionConfirmModal();
        initCourseStatsModal();
        var filterForm = document.getElementById('courses-filter-form');
        if (filterForm) {
            var filterDebounce = null;

            var submitFilters = function () {
                if (filterDebounce !== null) {
                    window.clearTimeout(filterDebounce);
                    filterDebounce = null;
                }
                filterForm.submit();
            };

            var textInputs = Array.prototype.slice.call(filterForm.querySelectorAll('[data-autofilter="text"]'));
            var selectInputs = Array.prototype.slice.call(filterForm.querySelectorAll('[data-autofilter="select"]'));

            textInputs.forEach(function (el) {
                el.addEventListener('input', function () {
                    if (filterDebounce !== null) {
                        window.clearTimeout(filterDebounce);
                    }
                    filterDebounce = window.setTimeout(function () {
                        filterForm.submit();
                    }, 350);
                });
                el.addEventListener('change', submitFilters);
            });

            selectInputs.forEach(function (el) {
                el.addEventListener('change', submitFilters);
            });
        }

        var selectAll = document.getElementById('bulk-select-all');
        if (!selectAll) {
            return;
        }

        var items = Array.prototype.slice.call(document.querySelectorAll('.course-selector'));
        var syncSelectAll = function () {
            if (items.length === 0) {
                selectAll.checked = false;
                return;
            }
            selectAll.checked = items.every(function (el) {
                return el.checked;
            });
        };

        selectAll.addEventListener('change', function () {
            items.forEach(function (el) {
                el.checked = selectAll.checked;
            });
        });

        items.forEach(function (el) {
            el.addEventListener('change', syncSelectAll);
        });

        syncSelectAll();
    }

    function initActionConfirmModal() {
        var modal = document.getElementById('course-action-modal');
        if (!modal) {
            return;
        }
        var titleEl = document.getElementById('course-action-modal-title');
        var messageEl = document.getElementById('course-action-modal-message');
        var confirmBtn = document.getElementById('course-action-modal-confirm');
        var cancelBtn = document.getElementById('course-action-modal-cancel');
        if (!titleEl || !messageEl || !confirmBtn || !cancelBtn) {
            return;
        }

        var baseConfirmClass = 'teacher-courses-btn inline-flex items-center justify-center rounded-lg px-4 py-2 font-semibold transition';
        var variantClass = {
            danger: ' teacher-courses-card__button--danger border border-rose-500 bg-rose-600 text-white hover:bg-rose-500',
            success: ' teacher-courses-btn--primary border border-emerald-500 bg-emerald-600 text-slate-950 hover:bg-emerald-500',
            default: ' teacher-courses-btn--primary border border-indigo-500 bg-indigo-600 text-white hover:bg-indigo-500',
        };

        var pending = null;

        function closeModal() {
            pending = null;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
            confirmBtn.className = baseConfirmClass + variantClass.success;
            confirmBtn.textContent = 'Confirmer';
        }

        function openModal(config) {
            pending = config;
            titleEl.textContent = config.title || 'Confirmation';
            messageEl.textContent = config.message || 'Confirmez-vous cette action ?';
            confirmBtn.textContent = config.label || 'Confirmer';
            var variant = config.variant;
            if (!variantClass[variant]) {
                variant = 'success';
            }
            confirmBtn.className = baseConfirmClass + variantClass[variant];
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
            confirmBtn.focus();
        }

        function executePending() {
            if (!pending) {
                closeModal();
                return;
            }
            var action = pending;
            closeModal();

            if (action.type === 'link' && action.href) {
                window.location.assign(action.href);
                return;
            }

            if (action.type === 'form' && action.form) {
                if (typeof action.form.requestSubmit === 'function' && action.submitter) {
                    action.form.requestSubmit(action.submitter);
                } else {
                    action.form.submit();
                }
            }
        }

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-confirm-action]');
            if (trigger) {
                if (trigger.disabled) {
                    return;
                }
                event.preventDefault();

                var kind = trigger.getAttribute('data-confirm-action');
                var title = trigger.getAttribute('data-confirm-title') || 'Confirmation';
                var message = trigger.getAttribute('data-confirm-message') || 'Confirmez-vous cette action ?';
                var label = trigger.getAttribute('data-confirm-label') || 'Confirmer';
                var variant = trigger.getAttribute('data-confirm-variant') || 'success';

                if (kind === 'link') {
                    openModal({
                        type: 'link',
                        href: trigger.getAttribute('href') || '',
                        title: title,
                        message: message,
                        label: label,
                        variant: variant,
                    });
                    return;
                }

                if (kind === 'form') {
                    var form = trigger.closest('form');
                    if (!form) {
                        return;
                    }
                    openModal({
                        type: 'form',
                        form: form,
                        submitter: trigger,
                        title: title,
                        message: message,
                        label: label,
                        variant: variant,
                    });
                }
                return;
            }

            if (event.target === modal) {
                closeModal();
                return;
            }

            if (event.target.closest('[data-modal-close]')) {
                event.preventDefault();
                closeModal();
            }
        });

        confirmBtn.addEventListener('click', function (event) {
            event.preventDefault();
            executePending();
        });

        document.addEventListener('keydown', function (event) {
            if (modal.classList.contains('hidden')) {
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal();
                return;
            }
            if (event.key === 'Enter' && document.activeElement === confirmBtn) {
                event.preventDefault();
                executePending();
            }
        });
    }

    function initCourseStatsModal() {
        var modal = document.querySelector('[data-course-stats-modal]');
        if (!modal) {
            return;
        }

        var closeButton = modal.querySelector('.teacher-course-stats-modal__close');
        var fields = {
            title: modal.querySelector('[data-course-stats-title]'),
            teacher: modal.querySelector('[data-course-stats-teacher]'),
            students: modal.querySelector('[data-course-stats-students]'),
            revenue: modal.querySelector('[data-course-stats-revenue]'),
            sales: modal.querySelector('[data-course-stats-sales]'),
            rating: modal.querySelector('[data-course-stats-rating]'),
            reviews: modal.querySelector('[data-course-stats-reviews]'),
            completion: modal.querySelector('[data-course-stats-completion]'),
            lessons: modal.querySelector('[data-course-stats-lessons]'),
            modules: modal.querySelector('[data-course-stats-modules]'),
            views: modal.querySelector('[data-course-stats-views]'),
            price: modal.querySelector('[data-course-stats-price]'),
        };

        function setField(name, value) {
            if (!fields[name]) {
                return;
            }
            fields[name].textContent = value || fields[name].textContent || '';
        }

        function read(trigger, key, fallback) {
            return trigger.getAttribute('data-course-' + key) || fallback || '';
        }

        function openModal(trigger) {
            setField('title', read(trigger, 'title', 'Cours'));
            setField('teacher', read(trigger, 'teacher', 'Enseignant'));
            setField('students', read(trigger, 'students', '0'));
            setField('revenue', read(trigger, 'revenue', '0 F CFA'));
            setField('sales', read(trigger, 'sales', '0 commande(s) payée(s)'));
            setField('rating', read(trigger, 'rating', 'Aucun avis'));
            setField('reviews', read(trigger, 'reviews', '0 avis'));
            setField('completion', read(trigger, 'completion', '0%'));
            setField('lessons', read(trigger, 'lessons', '0 leçons'));
            setField('modules', read(trigger, 'modules', '0 modules'));
            setField('views', read(trigger, 'views', '0 vues'));
            setField('price', read(trigger, 'price', 'Gratuit'));

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
            if (closeButton) {
                closeButton.focus();
            }
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-course-stats-open]');
            if (trigger) {
                event.preventDefault();
                openModal(trigger);
                return;
            }

            if (event.target.closest('[data-course-stats-close]')) {
                event.preventDefault();
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (modal.classList.contains('hidden')) {
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTeacherCoursesPage);
    } else {
        initTeacherCoursesPage();
    }
})();
