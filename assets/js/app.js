document.addEventListener('click', function (event) {
  const printButton = event.target.closest('[data-print]');
  if (printButton) {
    window.print();
  }
});

const summaryForm = document.querySelector('[data-summary-form]');
if (summaryForm) {
  summaryForm.addEventListener('submit', function () {
    const button = summaryForm.querySelector('button[type="submit"]');
    if (button) {
      button.disabled = true;
      button.textContent = 'Generating...';
    }
  });
}

const sidebar = document.getElementById('sidebar');
const sidebarOpeners = document.querySelectorAll('[data-sidebar-open]');
const sidebarClosers = document.querySelectorAll('[data-sidebar-close]');
const mobileSidebar = window.matchMedia('(max-width: 900px)');
let lastSidebarOpener = null;

const setSidebarOpen = function (open) {
  document.documentElement.classList.toggle('sidebar-open', open);

  sidebarOpeners.forEach(function (button) {
    button.setAttribute('aria-expanded', String(open));
  });

  if (!sidebar) {
    return;
  }

  if (mobileSidebar.matches && !open) {
    sidebar.setAttribute('inert', '');
  } else {
    sidebar.removeAttribute('inert');
  }

  if (open) {
    const closeButton = sidebar.querySelector('[data-sidebar-close]');
    if (closeButton) {
      closeButton.focus({ preventScroll: true });
    }
  } else if (lastSidebarOpener && mobileSidebar.matches) {
    lastSidebarOpener.focus({ preventScroll: true });
  }
};

sidebarOpeners.forEach(function (button) {
  button.addEventListener('click', function () {
    lastSidebarOpener = button;
    setSidebarOpen(true);
  });
});

sidebarClosers.forEach(function (button) {
  button.addEventListener('click', function () {
    setSidebarOpen(false);
  });
});

document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape' && document.documentElement.classList.contains('sidebar-open')) {
    setSidebarOpen(false);
  }
});

const syncSidebarMode = function () {
  setSidebarOpen(false);
};

if (mobileSidebar.addEventListener) {
  mobileSidebar.addEventListener('change', syncSidebarMode);
} else if (mobileSidebar.addListener) {
  mobileSidebar.addListener(syncSidebarMode);
}

setSidebarOpen(false);

// Give every table cell its column heading so small screens can stack rows
// into labeled cards without relying on the hidden header row.
document.querySelectorAll('table').forEach(function (table) {
  const headers = table.querySelectorAll('thead th');
  if (!headers.length) {
    return;
  }

  table.querySelectorAll('tbody tr').forEach(function (row) {
    Array.prototype.forEach.call(row.children, function (cell, index) {
      const header = headers[index];
      if (!header) {
        return;
      }

      const label = header.textContent.trim();
      if (!label) {
        return;
      }

      cell.setAttribute('data-label', label);

      if (cell.classList.contains('actions') || cell.querySelector('form, .btn')) {
        return;
      }

      const content = document.createElement('span');
      content.className = 'td-content';
      while (cell.firstChild) {
        content.appendChild(cell.firstChild);
      }
      cell.appendChild(content);
    });
  });
});
