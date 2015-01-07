<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

// html files must be parsed as php for this to work
include ("includes/classes.inc.php");
$page = new page("login");
echo $page->draw();
?>
