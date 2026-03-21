<?php $adminModernJsVersion = (string) (@filemtime(__DIR__ . '/assets/admin-modern.js') ?: '1'); ?>
<meta name="csrf-token" content="<?php echo function_exists('app_get_csrf_token') ? htmlspecialchars(app_get_csrf_token(), ENT_QUOTES, 'UTF-8') : ''; ?>">
<script src="../bower_components/jquery/dist/jquery.min.js"></script>
<script src="../bower_components/jquery-ui/jquery-ui.min.js"></script>
<script src="../bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="../bower_components/select2/dist/js/select2.full.min.js"></script>
<script src="../bower_components/moment/moment.js"></script>
<script src="../bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="../bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="../bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>
<script src="../bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<script src="../plugins/timepicker/bootstrap-timepicker.min.js"></script>
<script src="../bower_components/ckeditor/ckeditor.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="assets/admin-modern.js?v=<?php echo e($adminModernJsVersion); ?>"></script>

<script>
  (function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    var token = csrfToken ? csrfToken.getAttribute('content') : '';
    var adminBaseUrl = null;

    try {
      adminBaseUrl = new URL(
        window.location.pathname.replace(/[^/]*$/, ''),
        window.location.origin
      );
    } catch (error) {
      adminBaseUrl = null;
    }

    window.AdminBaseUrl = adminBaseUrl ? adminBaseUrl.toString() : '';
    window.adminUrl = function(path) {
      var target = String(path || '').trim();
      if (!target || !adminBaseUrl) {
        return target;
      }
      if (/^[a-z]+:/i.test(target) || target.indexOf('//') === 0) {
        return target;
      }
      return new URL(target, adminBaseUrl).toString();
    };

    if (window.jQuery) {
      $.ajaxSetup({
        headers: {
          'X-CSRF-Token': token
        }
      });

      $.ajaxPrefilter(function(options) {
        if (!options || typeof options.url !== 'string') {
          return;
        }
        options.url = window.adminUrl(options.url);
      });

      $(function() {
        $('form').each(function() {
          if (!$(this).find('input[name="_csrf"]').length) {
            $(this).append('<input type="hidden" name="_csrf" value="' + token + '">');
          }
        });
      });

      $(document).on('click', 'button, input[type="submit"], .btn', function(e) {
        var $btn = $(this);
        if ($btn.is('[data-allow-multi-click]')) {
          return;
        }
        if ($btn.data('clickLocked')) {
          e.preventDefault();
          e.stopImmediatePropagation();
          return false;
        }
        $btn.data('clickLocked', true);
        setTimeout(function() {
          $btn.data('clickLocked', false);
        }, 800);
      });

      $('form').on('submit', function() {
        var $form = $(this);
        if ($form.data('submitLocked')) {
          return false;
        }
        $form.data('submitLocked', true);
        $form.find('button[type="submit"], input[type="submit"]').each(function() {
          var $btn = $(this);
          if ($btn.is('[data-allow-multi-click]')) {
            return;
          }
          $btn.prop('disabled', true).attr('aria-disabled', 'true');
          if ($btn.is('input')) {
            $btn.val('Please wait...');
          } else {
            $btn.text('Please wait...');
          }
        });
      });

      $(function() {
        function runAdminModernEnhancement(target) {
          if (window.AdminModernEnhanceTables && typeof window.AdminModernEnhanceTables === 'function') {
            window.AdminModernEnhanceTables(document);
          }
        }

        function applyMobileTableLabels($table) {
          if (!$table || !$table.length) {
            return;
          }
          var headers = [];
          $table.find('thead th').each(function(index) {
            var text = $.trim($(this).text()) || ('Column ' + (index + 1));
            headers.push(text);
          });

          $table.find('tbody tr').each(function() {
            $(this).find('td').each(function(index) {
              var label = headers[index] || ('Column ' + (index + 1));
              $(this).attr('data-label', label);
            });
          });
        }

        function initDataTableWithMobileLabels(selector, options) {
          if (!$(selector).length || $.fn.DataTable.isDataTable(selector)) {
            if ($(selector).length) {
              applyMobileTableLabels($(selector));
              runAdminModernEnhancement($(selector).get(0));
            }
            return;
          }
          var table = $(selector).DataTable(options || {});
          applyMobileTableLabels($(selector));
          runAdminModernEnhancement($(selector).get(0));
          $(selector).on('draw.dt', function() {
            applyMobileTableLabels($(selector));
            runAdminModernEnhancement($(selector).get(0));
          });
          return table;
        }

        function escapeHtml(value) {
          return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function getHeaderLabels($table) {
          var labels = [];
          $table.find('thead th').each(function() {
            labels.push($.trim($(this).text()) || '');
          });
          return labels;
        }

        function ensureMobileContainers($table) {
          var tableId = $table.attr('id') || ('table_' + Math.random().toString(36).slice(2));
          var key = ($table.data('mobileCardKey') || tableId).replace(/[^a-zA-Z0-9_-]/g, '_');
          $table.data('mobileCardKey', key);

          var $host = $table.closest('.box-body');
          if (!$host.length) {
            $host = $table.parent();
          }

          var cardsId = 'adminMobileCards_' + key;
          var pagerId = 'adminMobilePager_' + key;
          var $cards = $('#' + cardsId);
          var $pager = $('#' + pagerId);
          if (!$cards.length) {
            $cards = $('<div/>', {
              id: cardsId,
              'class': 'admin-mobile-cards'
            }).appendTo($host);
          }
          if (!$pager.length) {
            $pager = $('<div/>', {
              id: pagerId,
              'class': 'admin-mobile-pagination'
            }).appendTo($host);
          }

          return {
            key: key,
            cards: $cards,
            pager: $pager
          };
        }

        function isActionCell($cell, label) {
          var low = (label || '').toLowerCase();
          if (low.indexOf('tool') !== -1 || low.indexOf('action') !== -1 || low.indexOf('detail') !== -1) {
            return true;
          }
          return $cell.find('button, a.btn, .edit, .delete, .transact, .confirm-bank, .status-toggle').length > 0;
        }

        function extractActionsHtml($row) {
          var controls = [];
          $row.find('button, a').each(function() {
            var $el = $(this);
            if (
              $el.hasClass('btn') ||
              $el.hasClass('edit') ||
              $el.hasClass('delete') ||
              $el.hasClass('transact') ||
              $el.hasClass('confirm-bank') ||
              $el.hasClass('status-toggle')
            ) {
              controls.push(this.outerHTML);
            }
          });
          if (!controls.length) {
            return '';
          }
          return '<div class="admin-mobile-card-actions">' + controls.join(' ') + '</div>';
        }

        function buildCardHtml($row, headers) {
          var $cells = $row.children('td');
          if (!$cells.length) {
            return '';
          }

          var title = '';
          var details = [];
          var media = '';

          $cells.each(function(index) {
            var $cell = $(this);
            if ($cell.hasClass('hidden')) {
              return;
            }

            var label = headers[index] || ('Column ' + (index + 1));
            var text = $.trim($cell.text());
            var html = $.trim($cell.html());
            if (!title && text) {
              title = text;
            }

            if (!media) {
              var $img = $cell.find('img').first();
              if ($img.length) {
                media = '<img class="admin-mobile-card-thumb" src="' + escapeHtml($img.attr('src') || '') + '" alt="' + escapeHtml($img.attr('alt') || 'Item') + '">';
              }
            }

            if (!text || isActionCell($cell, label)) {
              return;
            }

            details.push(
              '<div class="admin-mobile-card-row">' +
              '<span class="admin-mobile-card-label">' + escapeHtml(label) + '</span>' +
              '<span class="admin-mobile-card-value">' + html + '</span>' +
              '</div>'
            );
          });

          if (!title) {
            title = 'Item';
          }

          var actionsHtml = extractActionsHtml($row);
          var top = '<div class="admin-mobile-card-top">' +
            (media || '') +
            '<h4 class="admin-mobile-card-title">' + escapeHtml(title) + '</h4>' +
            '</div>';

          return '<div class="admin-mobile-card">' + top + '<div class="admin-mobile-card-body">' + details.join('') + '</div>' + actionsHtml + '</div>';
        }

        function renderMobileCards($table, page, attempt) {
          var bp = 768;
          var state = ensureMobileContainers($table);
          var $cards = state.cards;
          var $pager = state.pager;
          var key = state.key;
          var $wrapper = $table.closest('.dataTables_wrapper');
          if (!$wrapper.length) {
            $wrapper = $table.closest('.admin-table-wrap');
          }

          if (window.innerWidth > bp) {
            $('body').removeClass('mobile-cards-ready-' + key);
            $cards.hide().empty();
            $pager.hide().empty();
            $wrapper.show();
            return;
          }

          var rows = [];
          var tableId = $table.attr('id');
          if (tableId && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + tableId)) {
            rows = $table.DataTable().rows({
              search: 'applied',
              order: 'applied'
            }).nodes().toArray();
          } else {
            rows = $table.find('tbody tr').toArray();
          }

          var cleanRows = $(rows).filter(function() {
            return !$(this).hasClass('child');
          }).toArray();

          if (!cleanRows.length) {
            if ((attempt || 0) < 8) {
              setTimeout(function() {
                renderMobileCards($table, page || 1, (attempt || 0) + 1);
              }, 120);
              return;
            }
            $cards.html('<div class="admin-mobile-card"><div class="admin-mobile-card-body"><div class="admin-mobile-card-row"><span class="admin-mobile-card-value">No records found.</span></div></div></div>');
            $pager.hide().empty();
            $wrapper.hide();
            $('body').addClass('mobile-cards-ready-' + key);
            $cards.show();
            return;
          }

          var perPage = 8;
          var totalPages = Math.max(1, Math.ceil(cleanRows.length / perPage));
          var currentPage = parseInt(page || $cards.data('currentPage') || 1, 10);
          if (currentPage < 1) currentPage = 1;
          if (currentPage > totalPages) currentPage = totalPages;
          $cards.data('currentPage', currentPage);

          var start = (currentPage - 1) * perPage;
          var end = Math.min(start + perPage, cleanRows.length);
          var headers = getHeaderLabels($table);

          var html = '';
          cleanRows.slice(start, end).forEach(function(row) {
            html += buildCardHtml($(row), headers);
          });
          $cards.html(html);

          if (totalPages > 1) {
            var pagerHtml = '<ul class="pagination">';
            pagerHtml += '<li class="' + (currentPage === 1 ? 'disabled' : '') + '"><a href="#" class="page-link" data-mobile-table="' + escapeHtml(key) + '" data-page="' + (currentPage - 1) + '">&laquo;</a></li>';
            for (var i = 1; i <= totalPages; i++) {
              pagerHtml += '<li class="' + (i === currentPage ? 'active' : '') + '"><a href="#" class="page-link" data-mobile-table="' + escapeHtml(key) + '" data-page="' + i + '">' + i + '</a></li>';
            }
            pagerHtml += '<li class="' + (currentPage === totalPages ? 'disabled' : '') + '"><a href="#" class="page-link" data-mobile-table="' + escapeHtml(key) + '" data-page="' + (currentPage + 1) + '">&raquo;</a></li>';
            pagerHtml += '</ul>';
            $pager.html(pagerHtml).show();
          } else {
            $pager.hide().empty();
          }

          $wrapper.hide();
          $('body').addClass('mobile-cards-ready-' + key);
          $cards.show();
        }

        if ($('#example1').length && !$.fn.DataTable.isDataTable('#example1')) {
          initDataTableWithMobileLabels('#example1', {
            responsive: false,
            scrollX: false,
            autoWidth: false,
            pageLength: 25,
            order: [],
            language: {
              search: 'Find:',
              lengthMenu: 'Show _MENU_ items',
              info: 'Showing _START_ to _END_ of _TOTAL_ items',
              infoEmpty: 'No items found',
              paginate: {
                previous: 'Prev',
                next: 'Next'
              }
            }
          });
        }
        if ($('#example2').length && !$.fn.DataTable.isDataTable('#example2')) {
          initDataTableWithMobileLabels('#example2', {
            paging: true,
            lengthChange: false,
            searching: false,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            scrollX: false,
            language: {
              search: 'Find:',
              lengthMenu: 'Show _MENU_ items',
              info: 'Showing _START_ to _END_ of _TOTAL_ items',
              infoEmpty: 'No items found',
              paginate: {
                previous: 'Prev',
                next: 'Next'
              }
            }
          });
        }

        // Apply labels for any non-DataTable tables too.
        $('table').each(function() {
          applyMobileTableLabels($(this));
          runAdminModernEnhancement($(this).get(0));
        });

        // Admin-wide mobile card renderer for primary list tables.
        var $mobileTables = $('table#example1, table#example2');
        $mobileTables.each(function() {
          var $table = $(this);
          // Products page already has custom containers; generic engine can still use them.
          renderMobileCards($table, 1, 0);
          runAdminModernEnhancement($table.get(0));
          $table.on('init.dt draw.dt order.dt search.dt', function() {
            renderMobileCards($table, 1, 0);
            runAdminModernEnhancement($table.get(0));
          });
        });

        $(window).on('resize', function() {
          $mobileTables.each(function() {
            var $table = $(this);
            renderMobileCards($table, null, 0);
            runAdminModernEnhancement($table.get(0));
          });
        });

        $('.content table').not('.legacy-mobile-table').each(function() {
          $(this).addClass('mobile-stack-table');
        });

        $(document).on('click', '.admin-mobile-pagination .page-link', function(e) {
          e.preventDefault();
          var $link = $(this);
          var key = String($link.data('mobile-table') || '');
          var page = parseInt($link.data('page'), 10);
          if (!key || !page || page < 1) {
            return;
          }
          $mobileTables.each(function() {
            var $table = $(this);
            if (($table.data('mobileCardKey') || '') === key) {
              renderMobileCards($table, page, 0);
              runAdminModernEnhancement($table.get(0));
            }
          });
        });

        $('.select2').select2();

        if (window.CKEDITOR && typeof window.CKEDITOR.replace === 'function') {
          ['editor1', 'short_desc', 'desc', 'editor2', 'edit_short_desc', 'edit_desc'].forEach(function(id) {
            if (document.getElementById(id) && !window.CKEDITOR.instances[id]) {
              window.CKEDITOR.replace(id);
            }
          });
        }
      });
    }
  })();
</script>
