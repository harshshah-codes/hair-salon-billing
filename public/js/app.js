/* ============================================================
   Nirav Hair Storm — Global application JS
   ============================================================ */
(function ($) {
    'use strict';

    /* ---------- CSRF for AJAX ---------- */
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    const App = {
        sidebar() {
            const open = () => document.body.classList.add('sidebar-open');
            const close = () => document.body.classList.remove('sidebar-open');
            $('#sidebarToggle').on('click', open);
            $('#sidebarClose, #sidebarBackdrop').on('click', close);
        },

        theme() {
            const root = document.documentElement;
            const current = root.getAttribute('data-theme') || 'light';

            const apply = (theme) => {
                root.setAttribute('data-theme', theme);
                localStorage.setItem('nhs_theme', theme);
            };
            const saved = localStorage.getItem('nhs_theme');
            if (saved && saved !== current) {
                apply(saved);
            }

            $('#themeToggle').on('click', () => {
                const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                apply(next);
                $.post('/settings/theme', { theme: next })
                    .done(() => {})
                    .fail(() => {});
            });
        },

        toasts() {
            const container = $('#flashToasts');
            const success = container.data('success');
            const error = container.data('error');
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3200,
                timerProgressBar: true,
                didOpen: (t) => t.addEventListener('mouseenter', Swal.stopTimer)
            });
            if (success) Toast.fire({ icon: 'success', title: success });
            if (error) Toast.fire({ icon: 'error', title: error });
        },

        activity() {
            const list = $('[data-activity-list]');
            if (!list.length) return;
            $.getJSON('/settings/activity', { ajax: 1, limit: 8 })
                .done((res) => {
                    if (!res.success || !res.activities.length) {
                        list.html('<div class="px-3 py-4 text-center text-muted small">No recent activity</div>');
                        return;
                    }
                    let html = '';
                    res.activities.forEach((a) => {
                        html += '<div class="notification-item px-3 py-2 d-flex gap-2">'
                            + '<i class="fa-solid fa-circle-info text-success mt-1" style="font-size:.6rem"></i>'
                            + '<div class="small"><div class="fw-semibold">' + a.description + '</div>'
                            + '<div class="text-muted" style="font-size:.72rem">' + a.time_ago + '</div></div></div>';
                    });
                    list.html(html);
                })
                .fail(() => list.html('<div class="px-3 py-4 text-center text-muted small">Could not load activity</div>'));
        },

        confirmModal() {
            $(document).on('submit', 'form[data-confirm]', function (e) {
                e.preventDefault();
                const form = $(this);
                const title = form.data('confirm-title') || 'Are you sure?';
                const message = form.data('confirm-message') || 'This action cannot be undone.';
                const confirmText = form.data('confirm-button') || 'Yes, proceed';

                Swal.fire({
                    title: title,
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    reverseButtons: true,
                    customClass: { popup: 'rounded-4' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.off('submit').submit();
                    }
                });
            });
        },

        confirmDelete() {
            $(document).on('click', '[data-delete-url]', function (e) {
                e.preventDefault();
                const url = $(this).data('delete-url');
                const message = $(this).data('delete-message') || 'This record will be permanently removed.';
                Swal.fire({
                    title: 'Delete record?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const token = $('meta[name="csrf-token"]').attr('content');
                        $('<form method="post" action="' + url + '">'
                            + '<input type="hidden" name="_token" value="' + token + '">'
                            + '</form>').appendTo('body').submit();
                    }
                });
            });
        },

        dateInputs() {
            $('input[type="date"]').each(function () {
                if (this.value === '') this.value = new Date().toISOString().slice(0, 10);
            });
        },

        pageScripts() {
            // Page scripts can hook into window.NHS
            if (window.NHS && typeof window.NHS.init === 'function') {
                window.NHS.init();
            }
        },

        init() {
            this.sidebar();
            this.theme();
            this.toasts();
            this.activity();
            this.confirmModal();
            this.confirmDelete();
            this.pageScripts();
        }
    };

    window.NHS = window.NHS || {};
    window.NHS.confirm = (title, message, url) => {
        Swal.fire({
            title, text: message, icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Yes',
            confirmButtonColor: '#dc2626', reverseButtons: true
        }).then((r) => {
            if (r.isConfirmed) {
                const token = $('meta[name="csrf-token"]').attr('content');
                $('<form method="post" action="' + url + '"><input type="hidden" name="_token" value="' + token + '"></form>').appendTo('body').submit();
            }
        });
    };

    window.confirmAction = function (form, options) {
        const opts = options || {};
        Swal.fire({
            title: opts.title || 'Are you sure?',
            text: opts.text || '',
            icon: opts.icon || 'warning',
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Yes, proceed',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                if (opts.onConfirm) { opts.onConfirm(); return; }
                form.submit();
            }
        });
    };

    $(function () {
        App.init();
    });
})(jQuery);
