<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
// Check for a notification in the session
if (!empty($_SESSION['notification'])) {
  echo $_SESSION['notification'];
  // Clear the notification from the session
  unset($_SESSION['notification']);
}