(function () {
  function qs(selector, context) {
    return (context || document).querySelector(selector);
  }

  function qsa(selector, context) {
    return Array.prototype.slice.call((context || document).querySelectorAll(selector));
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
    initTreeMenus();
    initSidebarToggle();
    syncActiveMenu();
    enhanceContentScaffold();
    enhanceTables();
    annotateRequiredFields();
    addFormValidationUX();
    normalizeButtons();
    improveModalUX();
    closeSidebarOnNavigation();
    initBackToTop();
  });
})();
