/* ==========================================
   INEZA AFRICAN MINING — Navbar Scripts
   ========================================== */

/**
 * Toggle the profile dropdown menu
 */
function toggleDropdown() {
  var dropdown = document.getElementById('profileDropdown');
  if (dropdown) {
    dropdown.classList.toggle('open');
  }
}

/**
 * Close dropdown when clicking outside
 */
document.addEventListener('click', function (e) {
  var wrap = document.querySelector('.profile-wrap');
  if (wrap && !wrap.contains(e.target)) {
    var dropdown = document.getElementById('profileDropdown');
    if (dropdown) {
      dropdown.classList.remove('open');
    }
  }
});

/**
 * Handle Dark/Light theme toggling on the Dashboard Page
 */
document.addEventListener('DOMContentLoaded', function () {
  var themeToggleBtn = document.getElementById('themeToggleBtn');
  if (themeToggleBtn) {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || 'light';

    document.documentElement.setAttribute('data-theme', currentTheme);

    themeToggleBtn.addEventListener('click', function () {
      var activeTheme = document.documentElement.getAttribute('data-theme');
      var newTheme = activeTheme === 'dark' ? 'light' : 'dark';
      
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
    });
  }
});
