(function () {
  function qs(selector, context) {
    return (context || document).querySelector(selector);
  }

  function qsa(selector, context) {
    return Array.prototype.slice.call((context || document).querySelectorAll(selector));
  }

  function normalizeSpace(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function normalizeStatusToken(value) {
    var text = normalizeSpace(value).toLowerCase();
    if (!text) {
      return '';
    }
    if (text === 'successful') {
      return 'success';
    }
    return text;
  }

  function statusClassFromToken(token) {
    var normalized = normalizeStatusToken(token);
    if (!normalized) {
      return '';
    }
    if (normalized === 'not verified') {
      return 'is-not-verified';
    }
    if (normalized === 'failed' || normalized === 'error') {
      return 'is-failed';
    }
    if (normalized === 'success') {
      return 'is-success';
    }
    if (normalized === 'confirmed') {
      return 'is-confirmed';
    }
    if (normalized === 'info') {
      return 'is-info';
    }
    if (normalized === 'danger') {
      return 'is-danger';
    }
    return 'is-' + normalized.replace(/[^a-z0-9]+/g, '-');
  }

  function applyStatusClass(el, token) {
    var statusClass = statusClassFromToken(token);
    if (!statusClass) {
      return;
    }
    Array.prototype.slice.call(el.classList).forEach(function (cls) {
      if (cls.indexOf('is-') === 0) {
        el.classList.remove(cls);
      }
    });
    el.classList.add('admin-status-pill', statusClass);
  }

  function syncActiveMenu() {
    var current = window.location.pathname.replace(/\/$/, '').toLowerCase();
    qsa('.sidebar-menu a').forEach(function (link) {
      var href = link.getAttribute('href') || '';
      if (!href || href === '#') {
        return;
      }
      var parser = document.createElement('a');
      parser.href = href;
      var path = (parser.pathname || '').replace(/\/$/, '').toLowerCase();
      if (path && (current.endsWith(path) || current === path)) {
        var li = link.closest('li');
        if (li) {
          li.classList.add('active');
        }
        var tree = link.closest('.treeview');
        if (tree) {
          tree.classList.add('active', 'menu-open');
        }
      }
    });
  }

  function initTreeMenus() {
    qsa('.sidebar-menu .treeview > a').forEach(function (anchor) {
      anchor.addEventListener('click', function (event) {
        var parent = anchor.closest('.treeview');
        if (!parent) {
          return;
        }
        event.preventDefault();
        parent.classList.toggle('menu-open');
      });
    });
  }

  function initSidebarToggle() {
    var toggle = qs('[data-admin-sidebar-toggle]');
    if (!toggle) {
      return;
    }

    var STORAGE_KEY = 'admin_sidebar_collapsed';

    function closeSidebar() {
      document.body.classList.remove('sidebar-open');
    }

    function shouldOverlaySidebar() {
      return window.innerWidth <= 991;
    }

    toggle.addEventListener('click', function (event) {
      event.preventDefault();
      if (shouldOverlaySidebar()) {
        document.body.classList.toggle('sidebar-open');
      } else {
        document.body.classList.toggle('sidebar-collapsed');
        document.body.classList.remove('sidebar-open');
        try {
          localStorage.setItem(STORAGE_KEY, document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
        } catch (e) {
          // Ignore storage failures.
        }
      }
    });

    var backdrop = qs('.admin-sidebar-backdrop');
    if (backdrop) {
      backdrop.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeSidebar();
      }
    });

    window.addEventListener('resize', function () {
      if (!shouldOverlaySidebar()) {
        closeSidebar();
      }
    });

    if (!shouldOverlaySidebar()) {
      try {
        var saved = localStorage.getItem(STORAGE_KEY);
        if (saved === '1') {
          document.body.classList.add('sidebar-collapsed');
        }
      } catch (e) {
        // Ignore storage failures.
      }
    }
  }

  function enhanceContentScaffold() {
    var content = qs('.content');
    if (!content) {
      return;
    }
    content.classList.add('admin-page-content');

    qsa('.box').forEach(function (box) {
      box.classList.add('admin-card');
      var header = qs('.box-header', box);
      if (!header) {
        return;
      }
      header.classList.add('admin-card-header');

      var hasActions = !!qs('.btn, .form-inline, .pull-right, .pull-left', header);
      if (hasActions && !header.classList.contains('admin-toolbar')) {
        header.classList.add('admin-toolbar');
      }
    });
  }

  function enhanceTables() {
    qsa('.content table').forEach(function (table) {
      if (table.closest('.admin-table-wrap')) {
        return;
      }
      var wrap = document.createElement('div');
      wrap.className = 'admin-table-wrap table-responsive';
      if (table.classList.contains('legacy-mobile-table')) {
        wrap.classList.add('legacy-table-wrap');
      }
      table.parentNode.insertBefore(wrap, table);
      wrap.appendChild(table);
      table.classList.add('table', 'table-bordered', 'table-hover');
    });
  }

  function normalizeSidebarIcons() {
    qsa('.sidebar-menu a > i.fa').forEach(function (icon) {
      icon.classList.add('fa-fw');
    });
    qsa('.sidebar-menu .treeview-menu a > i.fa').forEach(function (icon) {
      icon.classList.add('fa-fw');
    });
  }

  function classifyHeader(text) {
    var low = normalizeSpace(text).toLowerCase();
    var compact = low.replace(/[^a-z0-9]+/g, ' ');
    var isMoney = /\b(price|amount|value|subtotal|total)\b/.test(compact);
    var isActions = /\b(tool|tools|action|actions|full detail|full details|details)\b/.test(compact);
    var isStatus = /\bstatus\b/.test(compact);
    return {
      money: isMoney,
      actions: isActions,
      status: isStatus
    };
  }

  function annotateTableColumns(context) {
    qsa('table', context).forEach(function (table) {
      var headers = qsa('thead th', table);
      if (!headers.length) {
        return;
      }

      var headerMeta = headers.map(function (th) {
        var meta = classifyHeader(th.textContent || '');
        th.classList.remove('admin-col-money', 'admin-col-actions', 'admin-col-status');
        if (meta.money) {
          th.classList.add('admin-col-money');
        }
        if (meta.actions) {
          th.classList.add('admin-col-actions');
        }
        if (meta.status) {
          th.classList.add('admin-col-status');
        }
        return meta;
      });

      qsa('tbody tr', table).forEach(function (row) {
        var cells = Array.prototype.slice.call(row.children).filter(function (node) {
          return node.tagName === 'TD' || node.tagName === 'TH';
        });
        cells.forEach(function (cell, index) {
          var meta = headerMeta[index];
          cell.classList.remove('admin-col-money', 'admin-col-actions', 'admin-col-status');
          if (!meta) {
            return;
          }
          if (meta.money) {
            cell.classList.add('admin-col-money');
          }
          if (meta.actions) {
            cell.classList.add('admin-col-actions');
          }
          if (meta.status) {
            cell.classList.add('admin-col-status');
          }
        });
      });
    });
  }

  function sanitizeLegacyErrorPlaceholders(context) {
    var legacyPattern = /error!\s*fill up the edit form first/i;
    qsa('table tbody td', context).forEach(function (cell) {
      if (cell.querySelector('input, textarea, select, button, a')) {
        return;
      }
      var text = normalizeSpace(cell.textContent || '');
      if (!text) {
        return;
      }
      if (!legacyPattern.test(text)) {
        return;
      }
      cell.innerHTML = '<span class="admin-empty-placeholder">Not provided</span>';
    });
  }

  function normalizeStatusPills(context) {
    qsa('table .label', context).forEach(function (badge) {
      applyStatusClass(badge, badge.textContent || '');
    });

    qsa('table .status-toggle', context).forEach(function (button) {
      var status = button.getAttribute('data-status') || button.textContent;
      button.classList.add('admin-status-button');
      button.classList.remove('btn-success', 'btn-danger', 'btn-warning', 'btn-info', 'btn-default', 'btn-primary');
      button.removeAttribute('style');
      applyStatusClass(button, status);
    });

    qsa('table .js-sales-status-static', context).forEach(function (badge) {
      var status = badge.getAttribute('data-status') || badge.textContent;
      applyStatusClass(badge, status);
    });

    qsa('table td.admin-col-status', context).forEach(function (cell) {
      if (cell.querySelector('.admin-status-pill, button, select, input, textarea')) {
        return;
      }

      var text = normalizeSpace(cell.textContent || '');
      if (!text || text.length > 24) {
        return;
      }

      var token = normalizeStatusToken(text);
      var known = {
        active: true,
        inactive: true,
        archived: true,
        pending: true,
        failed: true,
        success: true,
        confirmed: true,
        'not verified': true
      };

      if (!known[token]) {
        return;
      }

      cell.innerHTML = '<span class="admin-status-pill ' + statusClassFromToken(token) + '">' + text + '</span>';
    });
  }

  function applyThumbnailStyles(context) {
    qsa('table tbody td img', context).forEach(function (img) {
      img.classList.add('admin-table-thumb');
    });
  }

  function isActionElement(node) {
    if (!node || (node.tagName !== 'BUTTON' && node.tagName !== 'A')) {
      return false;
    }
    if (node.classList.contains('dataTables_paginate') || node.closest('.pagination')) {
      return false;
    }
    if (node.classList.contains('btn') || node.classList.contains('edit') || node.classList.contains('delete') || node.classList.contains('transact') || node.classList.contains('confirm-bank') || node.classList.contains('status-toggle') || node.classList.contains('photo') || node.classList.contains('status') || node.classList.contains('image')) {
      return true;
    }
    if ((node.getAttribute('href') || '').charAt(0) === '#') {
      return true;
    }
    return false;
  }

  function getActionLabel(node) {
    var explicit = normalizeSpace(node.textContent || '');
    if (explicit) {
      return explicit;
    }
    if (node.classList.contains('delete')) {
      return 'Delete';
    }
    if (node.classList.contains('edit')) {
      return 'Edit';
    }
    if (node.classList.contains('transact')) {
      return 'View';
    }
    if (node.classList.contains('confirm-bank')) {
      return 'Confirm';
    }
    return 'Action';
  }

  function isDestructiveAction(node, label) {
    var text = (normalizeSpace(label) + ' ' + (node.className || '')).toLowerCase();
    return text.indexOf('delete') !== -1 || text.indexOf('archive') !== -1 || text.indexOf('danger') !== -1;
  }

  function createMenuAction(node) {
    var tag = node.tagName.toLowerCase();
    var action = document.createElement(tag);

    Array.prototype.slice.call(node.attributes).forEach(function (attr) {
      if (attr.name === 'class' || attr.name === 'style') {
        return;
      }
      action.setAttribute(attr.name, attr.value);
    });

    var baseClasses = (node.className || '').split(/\s+/).filter(Boolean);
    action.className = baseClasses.join(' ');
    Array.prototype.slice.call(action.classList).forEach(function (cls) {
      if (cls === 'btn' || cls.indexOf('btn-') === 0) {
        action.classList.remove(cls);
      }
    });
    action.classList.add('admin-row-menu-item');
    if (tag === 'button' && !action.getAttribute('type')) {
      action.setAttribute('type', 'button');
    }

    var label = getActionLabel(node);
    if (isDestructiveAction(node, label)) {
      action.classList.add('is-danger');
    }

    var icon = node.querySelector('i.fa');
    if (icon) {
      action.appendChild(icon.cloneNode(true));
    }
    action.appendChild(document.createTextNode(label));
    action.setAttribute('title', label);
    action.setAttribute('aria-label', label);
    return action;
  }

  function createCompactAction(node) {
    var tag = node.tagName.toLowerCase();
    var action = document.createElement(tag);

    Array.prototype.slice.call(node.attributes).forEach(function (attr) {
      if (attr.name === 'style') {
        return;
      }
      action.setAttribute(attr.name, attr.value);
    });
    if (tag === 'button' && !action.getAttribute('type')) {
      action.setAttribute('type', 'button');
    }
    action.className = node.className || '';
    Array.prototype.slice.call(action.classList).forEach(function (cls) {
      if (cls === 'btn' || cls.indexOf('btn-') === 0) {
        action.classList.remove(cls);
      }
    });
    action.classList.add('btn', 'btn-secondary');
    action.classList.add('admin-action-compact');

    var label = getActionLabel(node);
    var icon = node.querySelector('i.fa');
    if (icon) {
      action.innerHTML = icon.outerHTML;
      action.setAttribute('title', label);
      action.setAttribute('aria-label', label);
    } else {
      action.textContent = label;
    }

    return action;
  }

  function restoreActionCell(cell) {
    if (cell.dataset.adminOriginalActions && cell.dataset.adminRowMenuApplied === '1') {
      cell.innerHTML = cell.dataset.adminOriginalActions;
      cell.dataset.adminRowMenuApplied = '0';
    }
  }

  function transformActionCell(cell) {
    var actions = qsa('button, a', cell).filter(isActionElement).filter(function (el) {
      return !el.classList.contains('admin-row-menu-toggle');
    });

    if (!actions.length) {
      return;
    }

    if (!cell.dataset.adminOriginalActions) {
      cell.dataset.adminOriginalActions = cell.innerHTML;
    }

    if (actions.length === 1) {
      var compact = createCompactAction(actions[0]);
      cell.innerHTML = '';
      cell.appendChild(compact);
      cell.dataset.adminRowMenuApplied = '1';
      return;
    }

    var container = document.createElement('div');
    container.className = 'admin-row-menu';
    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'admin-row-menu-toggle';
    toggle.setAttribute('aria-haspopup', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('title', 'Actions');
    toggle.innerHTML = '<i class="fa fa-ellipsis-v" aria-hidden="true"></i><span class="sr-only">Actions</span>';

    var list = document.createElement('div');
    list.className = 'admin-row-menu-list';
    list.setAttribute('role', 'menu');

    actions.forEach(function (action) {
      list.appendChild(createMenuAction(action));
    });

    container.appendChild(toggle);
    container.appendChild(list);
    cell.innerHTML = '';
    cell.appendChild(container);
    cell.dataset.adminRowMenuApplied = '1';
  }

  function transformRowActions(context) {
    var isMobile = window.innerWidth <= 767;
    qsa('table tbody td.admin-col-actions', context).forEach(function (cell) {
      if (isMobile) {
        restoreActionCell(cell);
        return;
      }
      if (cell.dataset.adminRowMenuApplied === '1') {
        return;
      }
      transformActionCell(cell);
    });
  }

  function closeAllRowMenus() {
    qsa('.admin-row-menu.is-open').forEach(function (menu) {
      menu.classList.remove('is-open');
      var toggle = qs('.admin-row-menu-toggle', menu);
      if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function initRowMenuEvents() {
    document.addEventListener('click', function (event) {
      var toggle = event.target.closest('.admin-row-menu-toggle');
      if (toggle) {
        event.preventDefault();
        var menu = toggle.closest('.admin-row-menu');
        if (!menu) {
          return;
        }
        var shouldOpen = !menu.classList.contains('is-open');
        closeAllRowMenus();
        if (shouldOpen) {
          menu.classList.add('is-open');
          toggle.setAttribute('aria-expanded', 'true');
        }
        return;
      }

      if (!event.target.closest('.admin-row-menu')) {
        closeAllRowMenus();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeAllRowMenus();
      }
    });
  }

  function enhanceAdminTables(context) {
    var scope = context && context.nodeType ? context : document;
    annotateTableColumns(scope);
    sanitizeLegacyErrorPlaceholders(scope);
    normalizeStatusPills(scope);
    applyThumbnailStyles(scope);
    transformRowActions(scope);
  }

  function annotateRequiredFields() {
    qsa('label[for]').forEach(function (label) {
      if (label.querySelector('.admin-required')) {
        return;
      }
      var targetId = label.getAttribute('for');
      if (!targetId) {
        return;
      }
      var input = document.getElementById(targetId);
      if (!input) {
        return;
      }
      if (input.hasAttribute('required')) {
        var marker = document.createElement('span');
        marker.className = 'admin-required';
        marker.textContent = ' *';
        label.appendChild(marker);
      }
    });
  }

  function addFormValidationUX() {
    qsa('form').forEach(function (form) {
      if (form.dataset.adminValidated === '1') {
        return;
      }
      form.dataset.adminValidated = '1';
      form.addEventListener('submit', function () {
        qsa('[required]', form).forEach(function (field) {
          var value = (field.value || '').trim();
          var isInvalid = !value;
          field.classList.toggle('admin-invalid', isInvalid);
          field.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
        });
      });
    });
  }

  function normalizeButtons() {
    qsa('.btn').forEach(function (btn) {
      btn.classList.remove('btn-flat');
      if (btn.classList.contains('btn-default')) {
        btn.classList.add('btn-secondary');
      }
    });
  }

  function improveModalUX() {
    if (!window.jQuery) {
      return;
    }
    window.jQuery(document).on('shown.bs.modal', '.modal', function () {
      var first = this.querySelector('input, select, textarea, button');
      if (first) {
        first.focus();
      }
    });
  }

  function closeSidebarOnNavigation() {
    qsa('.sidebar-menu a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth <= 991) {
          var href = (link.getAttribute('href') || '').trim();
          var isTreeToggle = link.parentElement && link.parentElement.classList.contains('treeview');
          var shouldStayOpen = href === '#' || isTreeToggle;
          if (!shouldStayOpen) {
            document.body.classList.remove('sidebar-open');
          }
        }
      });
    });
  }

  function initBackToTop() {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-primary admin-backtop';
    btn.setAttribute('aria-label', 'Back to top');
    btn.innerHTML = '<i class="fa fa-arrow-up"></i>';
    document.body.appendChild(btn);

    function updateVisibility() {
      btn.style.display = window.scrollY > 280 ? 'inline-flex' : 'none';
    }

    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', updateVisibility, { passive: true });
    updateVisibility();
  }

  document.addEventListener('DOMContentLoaded', function () {
    var resizeTimer = null;
    initTreeMenus();
    initSidebarToggle();
    syncActiveMenu();
    normalizeSidebarIcons();
    enhanceContentScaffold();
    enhanceTables();
    annotateRequiredFields();
    addFormValidationUX();
    normalizeButtons();
    initRowMenuEvents();
    enhanceAdminTables(document);
    improveModalUX();
    closeSidebarOnNavigation();
    initBackToTop();

    window.addEventListener('resize', function () {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(function () {
        enhanceAdminTables(document);
      }, 120);
    });
  });

  window.AdminModernEnhanceTables = function (context) {
    enhanceAdminTables(context || document);
  };
})();
