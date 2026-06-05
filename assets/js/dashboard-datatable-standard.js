(function(window, $) {
    'use strict';

    if (!$ || !$.fn || !$.fn.DataTable) {
        return;
    }

    var defaultDom = "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>";

    function getWrapper(tableSelectorOrNode) {
        var $table = $(tableSelectorOrNode);
        if (!$table.length) {
            return $();
        }

        if (!$table.is('table')) {
            $table = $table.closest('table');
        }

        if (!$table.length) {
            return $();
        }

        var tableId = $table.attr('id');
        if (tableId) {
            return $('#' + tableId + '_wrapper');
        }

        return $table.closest('.dataTables_wrapper');
    }

    function removeDuplicateControls($wrapper) {
        var $infos = $wrapper.find('.dataTables_info');
        if ($infos.length > 1) {
            $infos.slice(0, -1).remove();
        }

        // IMPORTANT:
        // Never remove pagination containers here. DataTables rebuilds pager nodes
        // on draw and owns their event bindings; external removal can break paging.
    }

    function getTableFromWrapper($wrapper) {
        if (!$wrapper || !$wrapper.length) {
            return $();
        }

        var wrapperId = $wrapper.attr('id') || '';
        if (wrapperId && /_wrapper$/.test(wrapperId)) {
            var tableId = wrapperId.replace(/_wrapper$/, '');
            var $tableById = $('#' + tableId);
            if ($tableById.length) {
                return $tableById;
            }
        }

        return $wrapper.find('table.dataTable').first();
    }

    function updateHeaderStats($wrapper, $table) {
        if (!$wrapper.length || !$table.length || !$.fn.DataTable.isDataTable($table[0])) {
            return;
        }

        var tableApi = $table.DataTable();
        var info = tableApi.page.info();
        var $stats = $wrapper.find('.dt-summary-badges').first();

        if (!$stats.length) {
            return;
        }

        $stats.find('[data-dt-stat="total"]').text(info.recordsTotal || 0);
        $stats.find('[data-dt-stat="filtered"]').text(info.recordsDisplay || 0);
        $stats.find('[data-dt-stat="page"]').text(info.end > 0 ? (info.end - info.start) : 0);
    }

    function ensureStandardHeader($wrapper, $table) {
        var $dtHeader = $wrapper.find('.dt-header').first();
        if (!$dtHeader.length) {
            return;
        }

        var $dtHeadLeft = $wrapper.find('.dt-head-left').first();
        var $dtHeadRight = $wrapper.find('.dt-head-right').first();
        var $dtFilter = $wrapper.find('.dataTables_filter').first();
        var $dtLength = $wrapper.find('.dataTables_length').first();

        if ($dtFilter.length && $dtHeadLeft.length && !$dtHeadLeft.find('.dataTables_filter').length) {
            $dtFilter.appendTo($dtHeadLeft);
        }

        if ($dtLength.length && $dtHeadLeft.length && !$dtHeadLeft.find('.dataTables_length').length) {
            $dtLength.appendTo($dtHeadLeft);
        }

        if ($dtFilter.length && $dtLength.length && $dtHeadLeft.length) {
            // Ensure standard order: search first, then length dropdown.
            if ($dtFilter.parent().is($dtHeadLeft)) {
                $dtLength.insertAfter($dtFilter);
            }
        }

        var $nativeSearchInput = $dtHeadLeft.find('.dataTables_filter input').first();
        if ($nativeSearchInput.length) {
            if (!$nativeSearchInput.attr('placeholder')) {
                $nativeSearchInput.attr('placeholder', 'Search...');
            }
            $nativeSearchInput.attr('aria-label', 'Search table');
        } else if ($dtHeadLeft.length && $table.length && $.fn.DataTable.isDataTable($table[0])) {
            // Fallback: if no native filter is rendered, inject a shared search input.
            var $fallback = $dtHeadLeft.find('.dt-search-fallback');
            if (!$fallback.length) {
                $fallback = $('<div class="dt-search-fallback"></div>');
                $fallback.append('<input type="text" class="form-control form-control-sm" placeholder="Search..." aria-label="Search table">');
                $dtHeadLeft.prepend($fallback);

                var tableApi = $table.DataTable();
                var timer = null;
                $fallback.find('input').on('input', function() {
                    var value = $(this).val();
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        tableApi.search(value).draw();
                    }, 250);
                });
            }
        }

        if ($dtLength.length) {
            $dtLength.show();
        }

        if ($dtHeadRight.length && !$dtHeadRight.find('.dt-summary-badges').length) {
            $dtHeadRight.append(
                '<div class="dt-summary-badges">'
                + '<span class="badge dt-stat-chip dt-stat-total">Total: <strong data-dt-stat="total">0</strong></span>'
                + '<span class="badge dt-stat-chip dt-stat-filtered">Filtered: <strong data-dt-stat="filtered">0</strong></span>'
                + '<span class="badge dt-stat-chip dt-stat-page">On Page: <strong data-dt-stat="page">0</strong></span>'
                + '</div>'
            );
        }

        updateHeaderStats($wrapper, $table);
    }

    function applyLayoutFixes(tableSelectorOrNode) {
        var $wrapper = getWrapper(tableSelectorOrNode);
        if (!$wrapper.length) {
            return;
        }

        var $table = getTableFromWrapper($wrapper);

        removeDuplicateControls($wrapper);

        var $dtHeader = $wrapper.find('.dt-header');
        var $dtHeadLeft = $wrapper.find('.dt-head-left');
        var $dtHeadRight = $wrapper.find('.dt-head-right');
        var $dtFilter = $wrapper.find('.dataTables_filter');
        var $dtLength = $wrapper.find('.dataTables_length');

        $dtHeader.css({
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'nowrap',
            width: '100%',
            gap: '12px'
        });

        $dtHeadLeft.css({
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'flex-start',
            flex: '1 1 auto',
            minWidth: '0'
        });

        $dtHeadRight.css({
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'flex-end',
            flex: '0 0 auto',
            marginLeft: 'auto'
        });

        $dtFilter.css({
            margin: '0',
            display: 'flex',
            alignItems: 'center',
            whiteSpace: 'nowrap'
        });

        $dtLength.css({
            margin: '0',
            display: 'flex',
            alignItems: 'center',
            whiteSpace: 'nowrap'
        });

        var $dtFooter = $wrapper.find('.dt-footer');
        var $dtFootLeft = $wrapper.find('.dt-foot-left');
        var $dtFootRight = $wrapper.find('.dt-foot-right');

        $dtFooter.css({
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'nowrap',
            width: '100%',
            gap: '12px'
        });

        $dtFootLeft.css({
            display: 'flex',
            alignItems: 'center',
            flex: '1 1 auto',
            minWidth: '0'
        });

        $dtFootRight.css({
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'flex-end',
            flex: '0 0 auto',
            marginLeft: 'auto'
        });

        ensureStandardHeader($wrapper, $table);
    }

    function getDefaultOptions(overrides) {
        var base = {
            dom: defaultDom,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            responsive: true,
            autoWidth: false
        };

        return $.extend(true, {}, base, overrides || {});
    }

    window.HAIDataTable = window.HAIDataTable || {};
    window.HAIDataTable.defaultDom = defaultDom;
    window.HAIDataTable.applyLayoutFixes = applyLayoutFixes;
    window.HAIDataTable.getDefaultOptions = getDefaultOptions;
})(window, window.jQuery);