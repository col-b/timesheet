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

echo "<h3>";

$session = new session();
echo $session->get_simple_status();
$now = new date_option_literal(strtotime("now"));
echo "<p>Current Time: " . $now->display_nice();

echo "</h3>";

?>
