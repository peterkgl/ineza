/* ==========================================
   INEZA AFRICAN MINING — Navbar Scripts
   ========================================== */

/**
 * Toggle the profile dropdown menu
 */
function toggleDropdown() {
  var dropdown = document.getElementById('profileDropdown');
  dropdown.classList.toggle('open');
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
 * Handle Dark/Light theme toggling and local storage caching
 */
document.addEventListener('DOMContentLoaded', function () {
  var themeToggleBtn = document.getElementById('themeToggleBtn');
  if (themeToggleBtn) {
    // Check localStorage or system theme preference
    var savedTheme = localStorage.getItem('theme');
    var systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    var currentTheme = savedTheme || (systemPrefersDark ? 'dark' : 'light');

    // Apply the active theme
    document.documentElement.setAttribute('data-theme', currentTheme);

    themeToggleBtn.addEventListener('click', function () {
      var activeTheme = document.documentElement.getAttribute('data-theme');
      var newTheme = activeTheme === 'dark' ? 'light' : 'dark';
      
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
    });
  }
});
