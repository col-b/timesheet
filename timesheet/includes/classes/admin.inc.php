<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

class admin 
{
    function admin() 
    {
    }

    function draw_user_management()
    {
        $html = 'draw_user_management';
        return $html;
    }

    function draw_proj_management()
    {
        $html .= '<h1><p align="center">PROJECT MANAGEMENT</h1>';
        $html .= '<p align="center"><a href="./">&laquo; Back</a>';

        // make sure this user is an admin
        $statement = "select * from user where user_username = '" . $_SESSION['user_username'] . "'";
        $result = mysql_fetch_array(mysql_query($statement));

        if ( $result['user_admin'] == "0" )
        {
            $html .= 'ERROR: YOU ARE NOT AN ADMIN!<p>';
            return $html;
        }

        // add the form button to add a new project
        $html .= '<h2>Add a new Project</h2>';
        $html .= '<form method="post" action="do_edit_project.php">';

        // new project job_id
        $html .= '<p>';
        $html .= 'Job Name: <select name = "new_project_job_id">';
        $job = new job();
        $html .= $job->option_list();
        $html .= "</select><p>";

        // new project project_name
        $html .= "Project Name: ";
        $html .= '<input type="text" name="new_project_project_name" width=40 maxLength=50/>';

        // new project owner_id
        $html .= '<p>';
        $html .= 'Owner Name: <select name = "new_project_owner_id">';
        $user = new user();
        $html .= $user->option_list();
        $html .= "</select><p>";

        // new project project_active
        $html .= '<p>';
        $html .= "Active: ";
        $html .= '<input type="text" name="new_project_project_active" readonly value=1 width=5 maxLength=5/>';

        // new project project_charge_number
        $html .= '<p>';
        $html .= 'Charge Number: ';
        $html .= '<input type="text" name="new_project_project_charge_number" width=30 maxLength=30/>';

        // add the button to actually submit the new project
        $html .= '<p>';
        $html .= '<input type="submit" name="new_project" value="Submit New Project">';
        $html .= '</form';

        $html .= $this->draw_project_table();

        $html .= '<p align="center"><a href="./">&laquo; Back</a>';

        return $html;
    }

    function draw_project_table()
    {
        $list = new table_list("projects");

        $tables[] = "project";

        $col[] = "project_name";
        $list->set_header("project_name", "Project");
        $list->set_width("project_name", 20);
        $list->set_align("project_name", "left");

        $col[] = "project_id";
        $list->set_header("project_id", "Project ID");
        $list->set_width("project_id", 3);
        $list->set_align("project_id", "center");

        $col[] = "job.job_id";
        $list->set_header("job_id", "Job ID");
        $list->set_width("job_id", 3);
        $list->set_align("job_id", "center");

        $tables[] = "job";
        $wheres[] = "job.job_id = project.job_id";
        $col[] = "job.job_name";

        $col[] = "job_name";
        $list->set_header("job_name", "Job Name");
        $list->set_width("job_name", 5);
        $list->set_align("job_name", "center");

        $tables[] = "user";
        $wheres[] = "user.user_id = project.user_id";
        $col[] = "user.user_username";
        $list->set_header("user_username", "Owner");
        $list->set_width("user_username", 7);
        $list->set_align("user_username", "center");

        $col[] = "project_charge_number";
        $list->set_header("project_charge_number", "Charge Number");
        $list->set_width("project_charge_number", 10);
        $list->set_align("project_charge_number", "center");

        $col[] = "project_active";
        $list->set_header("project_active", "Active");
        $list->set_width("project_active", 3);
        $list->set_align("project_active", "center");

        $list->set_link("edit_project.php?project_id=","project_id");

        $statement = "select " . implode(",",$col) . " from " . implode(",",$tables) . " where " . implode(" and ", $wheres);

        $html .= $list->draw_list($statement);

        return $html;
    }
}

// ---------------------------------------------------------------------

?>
