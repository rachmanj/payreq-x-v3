(function ($) {
    'use strict';

    var allMenuItems = [];
    var filteredItems = [];
    var selectedIndex = -1;
    var debounceTimer = null;
    var MENU_SEARCH_URL = '/api/menu/search';

    function fetchItems() {
        $.ajax({
            url: MENU_SEARCH_URL,
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (data) {
                allMenuItems = data.items || [];
                var v = $('#menu-search-input').val();
                if (v && String(v).trim()) {
                    filterItems(String(v));
                }
            },
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
            $results.removeClass('show');
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
        $('#menu-search-results').empty().removeClass('show');
        filteredItems = [];
        selectedIndex = -1;
    }

    $(document).ready(function () {
        var $input = $('#menu-search-input');
        var $wrapper = $('#menu-search-container');

        if (!$input.length || !$wrapper.length) {
            return;
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
            if (!$('#menu-search-results').hasClass('show') || !filteredItems.length) {
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
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeResults();
            }
        });

        $('#menu-search-results').on('mousedown', '.menu-search-item', function (e) {
            e.preventDefault();
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
