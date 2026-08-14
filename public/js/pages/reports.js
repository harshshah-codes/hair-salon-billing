/* ============================================================
   Reports — searchable filter dropdowns
   - Customer: server-side search (scales to thousands)
   - Employee / Service: local search over preloaded options
   ============================================================ */
(function ($) {
    'use strict';

    window.NHS = window.NHS || {};

    window.NHS.init = function () {
        const $customer = $('select[name="customer_id"]');
        const $local = $('select[name="employee_id"], select[name="service_id"], select[name="branch_id"]');

        if ($customer.length && $customer.data('ajax-search')) {
            $customer.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'All',
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: $customer.data('ajax-search'),
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ q: params.term || '' }),
                    processResults: (data) => ({
                        results: (data.customers || []).map((c) => ({
                            id: c.id,
                            text: c.name + (c.mobile ? ' — ' + c.mobile : '')
                        }))
                    }),
                    cache: true
                }
            });
        }

        $local.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'All',
            allowClear: true
        });
    };
})(jQuery);