<?php 

$apiKey = "AIzaSyD10I62WSWrEaOoHP52NLYy5p51h-QCSMU";

// Function to format input
function formatCommaSeparatedInput($input) {
  return preg_replace('/,\s*/', ', ', $input);
}