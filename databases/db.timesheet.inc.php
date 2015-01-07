<?php

/* Should have a link to this file in ~/databases */

mysql_connect ("localhost", "cbaker", "HH7xBjr6Wemat4fG") or die ('I cannot connect to the database server because: ' . mysql_error());
mysql_select_db ("chris-timesheet") or die ("I cannot connect to the database because: ".mysql_error());

?>
