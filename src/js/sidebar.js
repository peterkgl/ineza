/* ==========================================
   INEZA AFRICAN MINING — Sidebar Scripts
   ========================================== */

document.addEventListener('DOMContentLoaded', function () {
  var sidebar = document.querySelector('.sidebar');
  var navGroupItems = document.querySelector('.nav-group-items');
  var detailsElements = document.querySelectorAll('.sidebar details.nav-group');

  // 1. Restore details open/closed states first (as it affects sidebar scrollable height)
  detailsElements.forEach(function (details, index) {
    var isOpen = sessionStorage.getItem('sidebar-details-' + index);
    if (isOpen !== null) {
      if (isOpen === 'true') {
        details.setAttribute('open', '');
      } else {
        details.removeAttribute('open');
      }
    }
  });

  // 2. Restore scroll positions
  if (sidebar) {
    var sidebarScroll = sessionStorage.getItem('sidebar-scroll-position');
    if (sidebarScroll !== null) {
      sidebar.scrollTop = parseInt(sidebarScroll, 10);
    }
  }
  if (navGroupItems) {
    var groupScroll = sessionStorage.getItem('sidebar-group-scroll-position');
    if (groupScroll !== null) {
      navGroupItems.scrollTop = parseInt(groupScroll, 10);
    }
  }

  // 3. Save details states on toggle
  detailsElements.forEach(function (details, index) {
    details.addEventListener('toggle', function () {
      sessionStorage.setItem('sidebar-details-' + index, details.open);
    });
  });

  // 4. Save scroll positions on scroll
  if (sidebar) {
    sidebar.addEventListener('scroll', function () {
      sessionStorage.setItem('sidebar-scroll-position', sidebar.scrollTop);
    });
  }
  if (navGroupItems) {
    navGroupItems.addEventListener('scroll', function () {
      sessionStorage.setItem('sidebar-group-scroll-position', navGroupItems.scrollTop);
    });
  }

  // 5. Active item highlight & click listener (backup state save)
  var navItems = document.querySelectorAll('.sidebar .nav-item');
  navItems.forEach(function (item) {
    item.addEventListener('click', function () {
      if (sidebar) {
        sessionStorage.setItem('sidebar-scroll-position', sidebar.scrollTop);
      }
      if (navGroupItems) {
        sessionStorage.setItem('sidebar-group-scroll-position', navGroupItems.scrollTop);
      }

      navItems.forEach(function (el) { el.classList.remove('active'); });
      item.classList.add('active');
    });
  });
});
