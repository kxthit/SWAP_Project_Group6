<!-- header.php -->
<header>
  <div class="logo">
   
  </div>
  <div class="user-info">
  <p>Welcome, <?php echo htmlspecialchars($_SESSION['session_name'] ?? 'Guest'); ?></p>
  </div>
</header>

<div class="container">
  <aside class="sidebar">
    <p>MAIN MENU</p>
    <ul>
      <li>Profile</a></li>
      <li>Grades</a></li>
    </ul>
    <p>SETTINGS</p>
    <ul>
      <li>Setting</li>
      <li><a href="logout.php">Logout</li>
    </ul>
  </aside>
