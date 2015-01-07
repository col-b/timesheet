<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

include "includes/classes.inc.php";

// Get the current Session Timeout Value
/*
$currentTimeoutInSecs = ini_get('session.gc_maxlifetime');
echo "<p>current timeout: " . $currentTimeoutInSecs;
*/

// Set the current session timeout
ini_set('session.gc_maxlifetime', 60*60*24);

// Get the current Session Timeout Value
/*
$currentTimeoutInSecs = ini_get('session.gc_maxlifetime');
echo "<p>current timeout: " . $currentTimeoutInSecs;
*/

$login->process_login();
?>
