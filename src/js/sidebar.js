/* ==========================================
   INEZA AFRICAN MINING — Sidebar Scripts
   ========================================== */

document.addEventListener('DOMContentLoaded', function () {
  var navItems = document.querySelectorAll('.sidebar .nav-item');
  navItems.forEach(function (item) {
    item.addEventListener('click', function () {
      navItems.forEach(function (el) { el.classList.remove('active'); });
      item.classList.add('active');
    });
  });
});
