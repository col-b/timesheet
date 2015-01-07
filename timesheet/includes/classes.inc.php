<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

session_start();

//include '../../databases/db.timesheet.inc.php';
include '../databases/db.timesheet.inc.php';
include 'includes/classes/page.inc.php';
include 'includes/classes/session.inc.php';
include 'includes/classes/project.inc.php';
include 'includes/classes/date_option.inc.php';
include 'includes/classes/report.inc.php';
include 'includes/classes/admin.inc.php';
include 'includes/classes/list.inc.php';
include 'includes/classes/job.inc.php';
include 'includes/classes/user.inc.php';
include 'includes/classes/login.inc.php';

$login = new login();
// ---------------------------------------------------------------------

?>
