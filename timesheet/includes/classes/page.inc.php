<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

class page
{
    function page($page)
    {
        $this->page = $page;
    }

    function draw()
    {
        //
        if ($_SESSION['user_username'])
        {
            $html .= '<div id="login_info">Loading...</div>';
            $html .= '<script language="javascript" src="includes/java/mootools.js"></script>';
        }
        else
        {
            $login = new login();
        }

        $file_contents .= file_get_contents("includes/html/wrapper.html");
        $function = "draw_".$this->page;
        if ($_SESSION['message'])
        {
            $content = '<p class="error">'.$_SESSION['message'].'</p>';
            unset($_SESSION['message']);
        }
        $content .= $this->$function();
        $html .= str_replace("%content%", $content, $file_contents);

        $html .= "
<script language='javascript'>
    window.addEvent('domready', function()
    {
        $('login_info').load('login_info.php');
        var reloader = function()
        {
            $('login_info').load('login_info.php');
        }.periodical(60000)
    })
</script>
";

        /*
        $html .= "<script type=text/javascript>";
        $html .= "setTimeout(' document.location=document.location', 60000);";
        $html .= "</script>";
        */

        return $html;
    }


    function draw_index()
    {
        $html = file_get_contents("includes/html/index.html");
        $html = str_replace("%user%", $_SESSION['user_username'], $html);
        $html = str_replace("%userid%", $_SESSION['user_userid'], $html);

        $session = new session();
        $status = $session->get_status();
        //$html = str_replace("%status%", $status, $html);
        $menu .= $session->menu();
        $menu .= '<form method="post" action="report.inc.php">';
        $menu .= 'Report from: ';

        $date_option = new date_option(strtotime("last sunday 12:00 a.m."));
        $menu .= $date_option->draw('report_start');
        $menu .= '<p>Until: ';

        //if (date("D",time()) != "Fri")
        //{
        $date_option = new date_option(time());
        //}
        //else
        //{
        //$date_option = new date_option(strtotime("friday 11:59 p.m."));
        //}
        $menu .= $date_option->draw('report_end');
        $menu .= '<p>Session Report for Project:<p>';
        $menu .= '<select name="project_filter_id[]" multiple="multiple" size=10>';
        $project = new project();
        $menu .= $project->option_list();
        $menu .= '  </select><p>';
        $menu .= '<input type="submit" name="sessionreport" value="Session"> ';
        $menu .= '<input type="submit" name="timesheet" value="Time Sheet"> ';
        $menu .= '<input type="submit" name="dailyreport" value="Daily"> ';
        $menu .= '<input type="submit" name="recentreport" value="Last 5"> ';
        $menu .= '</form>';

        $_SESSION['project_filter_id'] = '';

        $html = str_replace("%menu%", $menu, $html);

        // get the user info to see if they are an adim
        $statement = "select * from user where user_username = '" . $_SESSION['user_username'] . "'";
        $result = mysql_fetch_array(mysql_query($statement));

        if ( $result['user_admin'] == "1" )
        {
            // setup the form input
            $html .= '<form method="post" action="admin.inc.php">';

            // setup the inputs
            $html .= '<input type="submit" name="user_management" value="User Management"> ';
            $html .= '<input type="submit" name="proj_management" value="Project Management">';


            // close the form
            $html .= '</form>';
        }

        $_SESSION['user_userid'] = $result['user_id'];

        return $html;
    }

    // the admin page
    function draw_admin()
    {
        // define the admin page
        $admin_page = new admin();

        // setup the user management page
        if ($_POST['user_management'])
        {
            $html .= $admin_page->draw_user_management();
        }

        // setup the project management page
        elseif ($_POST['proj_management'])
        {
            $html .= $admin_page->draw_proj_management();
        }

        // default to the project management page
        else
        {
            $html .= $admin_page->draw_proj_management();
        }

        return $html;
    }


    function draw_report()
    {
        if ($_POST['fix_job_id_to_project_id'])
        {
            $statement = "select * from session order by session_id";
            $result = mysql_query($statement);
            while ($row = mysql_fetch_array($result))
            {
                echo $row['session_id'] . " " . $row['project_id'] . " " . $row['job_id'] . " -> ";
                if ($row['project_id'] == 0)
                {
                    $statement = "update session set project_id = '" . $row['job_id'] . "' where session_id = '" . $row['session_id'] . "'";
                }
                else if ($row['job_id'] == 0)
                {
                    $statement = "update session set job_id = '" . $row['project_id'] . "' where session_id = '" . $row['session_id'] . "'";
                }
                else
                {
                    $statement = "";
                }
                if ($statement)
                {
                    mysql_query($statement);
                    if (mysql_error())
                    {
                        echo $statement . "<p>";
                        echo mysql_error();
                        exit;
                    }
                }
            }
            echo "<p>";
            $statement = "select * from job order by job_id";
            $result = mysql_query($statement);
            while ($row = mysql_fetch_array($result))
            {
                echo $row['job_id'] . " - " . $row['job_name'] . "<p>";
                $statement = "insert into project (project_id, project_name) values ('" . $row['job_id'] . "', '" . $row['job_name'] . "')";
                mysql_query($statement);
                if (mysql_error())
                {
                    echo $statement . "<p>";
                    echo mysql_error();
                    exit;
                }
            }

            $html = "done";
            return $html;
        }
        if ($_POST['timesheet'])
        {
            $type = 'timesheet';
        }
        elseif ($_POST['sessionreport'])
        {
            $type = "sessionreport";
        }
        elseif ($_POST['dailyreport'])
        {
            $type = "dailyreport";
        }
        elseif ($_POST['recentreport'])
        {
            $type = "recentreport";
        }

        if (!$type)
        {
            $type = $_SESSION['report_type'];
        }
        $_SESSION['report_type'] = $type;
        $report = new report($type);
        return $report->draw();
    }

    function draw_edit_session()
    {
        $session = new session($_GET{session_id});
        return $session->draw();
    }

    function draw_edit_project()
    {
        $project = new project($_GET{project_id});
        return $project->draw();
    }

    function draw_login()
    {
        $login = new login();
        $html = file_get_contents("includes/html/login.html");
        return $html;
    }
}

// ---------------------------------------------------------------------

?>
