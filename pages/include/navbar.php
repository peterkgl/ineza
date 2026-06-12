<!-- TOPBAR / NAVBAR -->
<header class="topbar">
  <div class="breadcrumb">
    <a href="#">Home</a>
    <span class="sep">/</span>
    <span class="cur">Cash Lead Schedule</span>
  </div>
  <div class="topbar-search">
    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" placeholder="Search anything…" id="searchInput">
  </div>
  <div class="topbar-right">
    <button class="icon-btn" title="Notifications" id="notifBtn">
      <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
      <span class="notif-dot"></span>
    </button>
    <button class="icon-btn" title="Grid view" id="gridBtn">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    </button>
    <button class="icon-btn" title="Toggle theme" id="themeToggleBtn">
      <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
    </button>
    <div class="profile-wrap">
      <button class="profile-btn" onclick="toggleDropdown()" id="profileBtn">
        <div class="avatar">SA</div>
        <div class="profile-info">
          <div class="profile-name">Super Admin</div>
          <div class="profile-role">Administrator</div>
        </div>
        <svg class="profile-caret" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div class="dropdown" id="profileDropdown">
        <div class="dropdown-header">
          <div class="dropdown-name">Super Admin</div>
          <div class="dropdown-email">admin@inezamining.rw</div>
        </div>
        <div class="dropdown-item">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41"/></svg>
          Settings
        </div>
        <div class="dropdown-item">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          My Profile
        </div>
        <div class="dropdown-divider"></div>
        <div class="dropdown-item danger">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          Logout
        </div>
      </div>
    </div>
  </div>
</header>
