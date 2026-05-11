(function ($) {
    'use strict';

    $(document).ready(function () {
        var $input = $('#menu-search-input');
        var $wrapper = $('#menu-search-container');

        if (!$input.length || !$wrapper.length) {
            return;
        }

        var allMenuItems = [];
        var filteredItems = [];
        var selectedIndex = -1;
        var debounceTimer = null;
        var loadState = 'loading';

        var apiUrl = $wrapper.data('menu-search-url');
        if (!apiUrl || apiUrl === '') {
            apiUrl = '/api/menu/search';
        }

        function showResultsPanel(text, options) {
            var opts = options || {};
            var $results = $('#menu-search-results');
            $results.empty();
            var cls = 'menu-search-status';
            if (opts.isError) {
                cls += ' menu-search-status-error';
            }
            $results.append($('<div/>', { class: cls, text: text }));
            $results.addClass('show');
        }

        function fetchItems() {
            loadState = 'loading';
            $.ajax({
                url: apiUrl,
                method: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .done(function (data) {
                    loadState = 'ready';
                    allMenuItems = (data && data.items) ? data.items : [];
                    var v = $input.val();
                    if (v && String(v).trim()) {
                        filterItems(String(v));
                    }
                })
                .fail(function (xhr) {
                    loadState = 'error';
                    allMenuItems = [];
                    if (xhr.status === 401 || xhr.status === 419) {
                        showResultsPanel('Session expired. Reload the page and sign in again.', { isError: true });
                    } else if (xhr.status === 429) {
                        showResultsPanel('Too many requests. Wait a moment and try again.', { isError: true });
                    } else {
                        showResultsPanel('Menu search could not load. Refresh the page or contact support.', { isError: true });
                    }
                });
        }

        function sortFiltered(term, items) {
            items.sort(function (a, b) {
                var at = a.title.toLowerCase();
                var bt = b.title.toLowerCase();
                var rank = function (t) {
                    if (t.indexOf(term) === 0) {
                        return 0;
                    }
                    if (t.indexOf(term) >= 0) {
                        return 1;
                    }
                    return 2;
                };
                var ar = rank(at);
                var br = rank(bt);
                if (ar !== br) {
                    return ar - br;
                }
                return at.localeCompare(bt);
            });
            return items.slice(0, 15);
        }

        function filterItems(raw) {
            var term = String(raw).trim().toLowerCase();
            var $results = $('#menu-search-results');

            if (!term) {
                filteredItems = [];
                selectedIndex = -1;
                $results.empty().removeClass('show');
                return;
            }

            if (loadState === 'loading') {
                showResultsPanel('Loading menu…');
                return;
            }

            if (loadState === 'error') {
                return;
            }

            filteredItems = allMenuItems.filter(function (item) {
                return item.searchText.indexOf(term) !== -1;
            });
            filteredItems = sortFiltered(term, filteredItems);
            renderResults();
        }

        function renderResults() {
            var $results = $('#menu-search-results');
            $results.empty();
            selectedIndex = -1;

            if (!filteredItems.length) {
                if (allMenuItems.length > 0) {
                    showResultsPanel('No matching menu items.');
                } else {
                    $results.removeClass('show');
                }
                return;
            }

            filteredItems.forEach(function (item, idx) {
                var $row = $('<div/>', {
                    class: 'menu-search-item',
                    'data-index': idx,
                    'data-url': item.route,
                });
                var $icon = $('<i/>', { class: item.icon + ' menu-search-item-icon' });
                var $title = $('<div/>', { class: 'menu-search-item-title', text: item.title });
                var $meta = $('<div/>', { class: 'menu-search-item-meta', text: item.breadcrumb });
                $row.append($('<div/>', { class: 'd-flex align-items-start' }).append($icon).append($('<div/>').append($title).append($meta)));
                $results.append($row);
            });

            $results.addClass('show');
        }

        function updateActiveHighlight() {
            $('#menu-search-results .menu-search-item').removeClass('menu-search-item-active');
            if (selectedIndex >= 0 && selectedIndex < filteredItems.length) {
                $('#menu-search-results .menu-search-item').eq(selectedIndex).addClass('menu-search-item-active');
            }
        }

        function navigateToSelected() {
            var idx = selectedIndex;
            if (idx < 0 && filteredItems.length > 0) {
                idx = 0;
            }
            if (idx >= 0 && idx < filteredItems.length) {
                window.location.href = filteredItems[idx].route;
            }
        }

        function closeResults() {
            if (loadState === 'error') {
                $('#menu-search-results').empty().removeClass('show');
                return;
            }
            $('#menu-search-results').empty().removeClass('show');
            filteredItems = [];
            selectedIndex = -1;
        }

        fetchItems();

        $input.on('input', function () {
            clearTimeout(debounceTimer);
            var val = $(this).val();
            debounceTimer = setTimeout(function () {
                filterItems(val);
            }, 300);
        });

        $input.on('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                $('#menu-search-results').empty().removeClass('show');
                filteredItems = [];
                selectedIndex = -1;
                return;
            }

            var $results = $('#menu-search-results');
            var statusOnly = $results.find('.menu-search-status').length > 0;

            if (statusOnly || !$results.hasClass('show') || !filteredItems.length) {
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, filteredItems.length - 1);
                updateActiveHighlight();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateActiveHighlight();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                navigateToSelected();
            }
        });

        $('#menu-search-results').on('mousedown', '.menu-search-item', function (event) {
            event.preventDefault();
            var url = $(this).data('url');
            if (url) {
                window.location.href = url;
            }
        });

        $(document).on('click', function (e) {
            if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) {
                closeResults();
            }
        });

        $(document).on('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                if (tag === 'input' || tag === 'textarea' || e.target.isContentEditable) {
                    return;
                }
                e.preventDefault();
                $input.trigger('focus');
            }
        });
    });
})(jQuery);
