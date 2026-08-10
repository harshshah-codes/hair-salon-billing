/* ============================================================
   Dashboard — customer search & quick create
   ============================================================ */
(function ($) {
    'use strict';

    const money = (v) => '₹' + Number(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const $input = $('#dashboardCustomerSearch');
    const $results = $('#dashboardCustomerResults');
    let searchTimer;

    function search() {
        const q = $input.val().trim();
        if (q.length < 2) {
            $results.addClass('d-none').html('');
            return;
        }
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            $.getJSON('/customers/ajax/search', { q: q })
                .done((res) => {
                    if (!res.success) return;
                    renderResults(res.customers, q);
                });
        }, 300);
    }

    function renderResults(list, q) {
        let html = '';
        if (!list.length) {
            html = '<div class="list-group-item text-muted small py-3 text-center border-0">No customers found.</div>';
        }
        list.forEach((c) => {
            const bal = parseFloat(c.available_balance) || 0;
            const out = parseFloat(c.outstanding) || 0;
            html += '<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 border-0" data-customer-id="' + c.id + '">'
                + '<span class="avatar-initials" style="width:36px;height:36px;background:hsl(' + (c.name.length * 40 % 360) + ',55%,40%)">' + c.name.charAt(0).toUpperCase() + '</span>'
                + '<span class="flex-grow-1 text-start"><span class="d-block fw-semibold">' + c.name + '</span>'
                + '<span class="small text-muted">' + (c.mobile || '') + '</span></span>'
                + '<span class="text-end small"><span class="d-block ' + (bal > 0 ? 'text-success fw-semibold' : 'text-muted') + '">' + money(bal) + '</span>'
                + '<span class="' + (out > 0 ? 'text-danger' : 'text-muted') + '">Outstanding ' + money(out) + '</span></span>'
                + '</button>';
        });
        html += '<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 border-0 border-top text-primary fw-semibold" id="btnDashboardCreateCustomer">'
            + '<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-soft text-primary" style="width:36px;height:36px"><i class="fa-solid fa-plus"></i></span>'
            + '<span>Create new customer</span>'
            + '</button>';
        $results.html('<div class="list-group list-group-flush">' + html + '</div>').removeClass('d-none');
    }

    $input.on('input', search);

    $(document).on('click', '#dashboardCustomerResults .list-group-item', function () {
        const id = $(this).data('customer-id');
        if (id) window.location.href = '/billing?customer_id=' + id;
    });

    $(document).on('click', '#btnDashboardCreateCustomer', function () {
        $results.addClass('d-none').html('');
        const q = $input.val().trim();
        CreateCustomerModal.open({ phone: /^\d+$/.test(q) ? q : '', name: '' });
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#dashboardCustomerSearch, #dashboardCustomerResults').length) {
            $results.addClass('d-none');
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') $results.addClass('d-none');
    });

    window.NHS = window.NHS || {};
    window.NHS.init = function () {
        CreateCustomerModal.onCreated(function (customer) {
            window.location.href = '/billing?customer_id=' + customer.id;
        });
    };
})(jQuery);
