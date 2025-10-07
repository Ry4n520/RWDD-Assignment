<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current file name (e.g., home.php, trading.php)
?>

<!-- Sidebar toggle button -->
<button id="open-sidebar-button" onclick="openSidebar()" aria-label="open sidebar" aria-expanded="false" aria-controls="navbar">
  <!-- hamburger icon -->
  <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#c9c9c9">
    <path d="M165.13-254.62q-10.68 0-17.9-7.26-7.23-7.26-7.23-18t7.23-17.86q7.22-7.13 17.9-7.13h629.74q10.68 0 17.9 7.26 7.23 7.26 7.23 18t-7.23 17.87q-7.22 7.12-17.9 7.12H165.13Zm0-200.25q-10.68 0-17.9-7.27-7.23-7.26-7.23-17.99 0-10.74 7.23-17.87 7.22-7.13 17.9-7.13h629.74q10.68 0 17.9 7.27 7.23 7.26 7.23 17.99 0 10.74-7.23 17.87-7.22 7.13-17.9 7.13H165.13Zm0-200.26q-10.68 0-17.9-7.26-7.23-7.26-7.23-18t7.23-17.87q7.22-7.12 17.9-7.12h629.74q10.68 0 17.9 7.26 7.23 7.26 7.23 18t-7.23 17.86q-7.22 7.13-17.9 7.13H165.13Z"/>
  </svg>
</button>

<!-- Navbar -->
<header>
  <nav id="navbar">
    <ul class="mainlist">
      <li>
        <button id="close-sidebar-button" onclick="closeSidebar()" aria-label="close sidebar">
          <!-- close icon -->
          <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#c9c9c9">
            <path d="m480-444.62-209.69 209.7q-7.23 7.23-17.5 7.42-10.27.19-17.89-7.42-7.61-7.62-7.61-17.7 0-10.07 7.61-17.69L444.62-480l-209.7-209.69q-7.23-7.23-7.42-17.5-.19-10.27 7.42-17.89 7.62-7.61 17.7-7.61 10.07 0 17.69 7.61L480-515.38l209.69-209.7q7.23-7.23 17.5-7.42 10.27-.19 17.89 7.42 7.61 7.62 7.61 17.7 0 10.07-7.61 17.69L515.38-480l209.7 209.69q7.23 7.23 7.42 17.5.19 10.27-7.42 17.89-7.62 7.61-17.7 7.61-10.07 0-17.69-7.61L480-444.62Z"/>
          </svg>
        </button>
      </li>

      <li class="home-li">
        <a href="home.php" class="<?= ($current_page == 'home.php') ? 'active-link' : '' ?>">Home</a>
      </li>
      <li>
        <button id="theme-toggle" aria-label="Toggle theme" title="Toggle light/dark theme">
          <!-- moon icon -->
          <svg class="icon-moon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
          <!-- sun icon -->
          <svg class="icon-sun" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6.76 4.84l-1.8-1.79L3.17 4.84l1.79 1.8 1.8-1.8zM1 13h3v-2H1v2zm10-9h2V1h-2v3zm7.04 1.05l1.79-1.8-1.79-1.79-1.8 1.8 1.8 1.79zM20 11v2h3v-2h-3zM12 21h2v-3h-2v3zm4.24-2.76l1.8 1.79 1.79-1.79-1.79-1.8-1.8 1.8zM4.84 17.24l-1.8 1.79L4.84 20l1.8-1.79-1.8-1.8zM12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>
        </button>
      </li>
      <li>
        <a href="trading.php" class="<?= ($current_page == 'trading.php') ? 'active-link' : '' ?>">Trading</a>
      </li>
      <li>
        <a href="events.php" class="<?= ($current_page == 'events.php') ? 'active-link' : '' ?>">Events</a>
      </li>
      <li>
        <a href="communities.php" class="<?= ($current_page == 'communities.php') ? 'active-link' : '' ?>">Communities</a>
      </li>
      <li>
        <a href="profile.php" class="<?= ($current_page == 'profile.php') ? 'active-link' : '' ?>">Profile</a>
      </li>
    </ul>
  </nav>
  <div id="overlay" onclick="closeSidebar()" aria-hidden="true"></div>
</header>
