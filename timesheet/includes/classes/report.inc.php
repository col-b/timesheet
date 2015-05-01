<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

class report
{
    function report($type)
    {
        $this->type = $type;
    }

    function draw()
    {
        $session = new session($_GET{session_id});
        $need_to_remove_clock_out = 0;
        if (!$session->clocked_in())
        {
        }
        else
        {
            $need_to_remove_clock_out = 1;
            $current_time = new date_option(strtotime("now"));
            $statement = "update session set session_stop = '".date("Y-m-d H:i:s", $current_time->Get())."' where session_id = '".$session->data['session_id']."'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement;
                echo mysql_error();
                exit;
            }
        }
        switch($this->type)
        {
            case 'sessionreport':

                // here is we don't have a project filter select, instead of erroring with the oops statement below,
                // we just select all projects, active or not.
                if (!$_POST['project_filter_id'])
                {
                    // select the project ids from the project_to_user table where the user id is the current logged in user
                    $statement = "select project_id from project_to_user where user_id=" . $_SESSION['user_userid'];
                    $result = mysql_query($statement);
                    if (mysql_error())
                    {
                        echo $statement;
                        echo mysql_error();
                        exit;
                    }

                    // add all these projects to the session project filter array
                    while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
                    {
                        $_SESSION['project_filter_id'][] = $row['project_id'];
                    }
                }

                if ($_POST['project_filter_id'])
                {
                    $_SESSION['project_filter_id'] = $_POST['project_filter_id'];
                }
                else if (!$_SESSION['project_filter_id'])
                {
                    $_SESSION['project_filter_id'] = '';
                    $html = '&nbsp<p>&nbsp<p>&nbsp<p><h1 align="center">Oops, you didn\'t select a project filter</h1>';
                    $html .='<p align="center"><a href="./"><h1>Go Back</h1></a>';
                    if ($need_to_remove_clock_out)
                    {
                        $need_to_remove_clock_out = 0;
                        $statement = "update session set session_stop = NULL where session_id = '".$session->data['session_id']."'";
                        mysql_query($statement);
                        if (mysql_error())
                        {
                            echo $statement;
                            echo mysql_error();
                            exit;
                        }
                    }
                    return $html;
                }
                $project_filter = $_SESSION['project_filter_id'];

                if (count($project_filter) > 1)
                {
                    $html .= '<h1 align="center">Session Report for the Projects: ';
                }
                else
                {
                    $html .= '<h1 align="center">Session Report for the Project: ';
                }

                foreach($project_filter as $cur_project_filter)
                {
                    $project = new project($cur_project_filter);
                    if ($cur_project_filter == $project_filter[0])
                    {
                        $html .= $project->data['project_name'];
                    }
                    else
                    {
                        $html .= ', ' . $project->data['project_name'];
                    }
                }
                $html .= '</h1>';
                $html .= '
            <p align="center"><a href="./">&laquo; Back</a>';

                $html .= $this->session_report();

                break;
            case 'timesheet':
                $html .= '<form method="post" action="report.inc.php">';
                $html .= '<h1 align="center">Time Sheet Report for Job: ';
                $html .= '<select name="job_filter_id" onchange="submit()">';
                if ($_POST['job_filter_id'])
                {
                }
                else
                {
                    if ($session->clocked_in())
                    {
                        $current_project = new project($session->get_project_id());
                        $_POST['job_filter_id'] = $current_project->get_job_id();
                    }
                    else
                    {
                        $_POST['job_filter_id'] = "all";
                    }
                }
                $job = new job($_POST['job_filter_id']);
                $html .= $job->option_list($_POST['job_filter_id']);
                $html .= '</select></form>';
                $html .= '</h1>';
                $html .= '<p align="center"><a href="./">&laquo; Back</a>';
                $html .= $this->time_report();
                break;
            case 'dailyreport':
                $html .= '<form method="post" action="report.inc.php">';
                $html .= '<h1 align="center">Daily Report for Job: ';
                $html .= '<select name="job_filter_id" onchange="submit()">';
                if ($_POST['job_filter_id'])
                {
                }
                else
                {
                    if ($session->clocked_in())
                    {
                        $current_project = new project($session->get_project_id());
                        $_POST['job_filter_id'] = $current_project->get_job_id();
                    }
                    else
                    {
                        $_POST['job_filter_id'] = "all";
                    }
                }
                $job = new job($_POST['job_filter_id']);
                $html .= $job->option_list($_POST['job_filter_id']);
                $html .= '</select></form>';
                $html .= '</h1>';
                $html .= '
              <p align="center"><a href="./">&laquo; Back</a>';
                $html .= $this->daily_report();
                break;
            case 'recentreport':
                $html .= '<h1 align="center">Recent Activity</h1>';
                $html .= '<p align="center"><a href="./">&laquo; Back</a>';
                $html .= $this->recent_report();
                break;
        }
        $html .= '
          <p align="center"><a href="./">&laquo; Back</a>';

        if ($need_to_remove_clock_out)
        {
            $need_to_remove_clock_out = 0;
            $statement = "update session set session_stop = NULL where session_id = '".$session->data['session_id']."'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement;
                echo mysql_error();
                exit;
            }
            $html .= "<h1 align='center'>WARNING:</h1><h3 align='center'>You are still clocked in, so the ending time for your current session is only displayed here and is not actually in the database ;)</h3>";
            $html .= "<h3 align='center'>The current \"faked\" clockout time is: " . date("D h:i a",$current_time->Get()) . "</h3>";
        }

        return $html;
    }

    function time_report()
    {
        $list = new table_list($this->type);

        $tables[] = "session";
        $tables[] = "project";
        $wheres[] = "project.project_id = session.project_id";
        if ($_POST['job_filter_id'] != "all")
        {
            $wheres[] = "project.job_id = " . $_POST['job_filter_id'];
        }

        $tables[] = "job";
        $wheres[] = "job.job_id = project.job_id";
        $col[] = "job.job_name";
        $list->set_header("job_name", "Job");
        $list->set_width("job_name", 5);
        $list->set_align("job_name", "center");

        $wheres[] = "project.user_id = " . $_SESSION['user_userid'];
        $col[] = "project.project_name";
        $list->set_header("project_name", "Project");
        $list->set_width("project_name", 5);
        $list->set_align("project_name", "center");

        $col[] = "project.project_charge_number";
        $list->set_header("project_charge_number", "Charge Number");
        $list->set_width("project_charge_number", 5);
        $list->set_align("project_charge_number", "center");

        if ($_POST['report_start'])
        {
            $report_start = $_POST['report_start'];
            $report_end = $_POST['report_end'];
        }
        else
        {
            $report_start = $_SESSION['report_start'];
            $report_end = $_SESSION['report_end'];
        }

        $_SESSION['report_start'] = $report_start;
        $_SESSION['report_end'] = $report_end;
        $start_date = new date_option($report_start);
        $end_date = new date_option($report_end);
        $start_time = $start_date->get();
        $end_time = $end_date->get();
        $current = $start_time;
        while($current < $end_time)
        {
            $test['start'] = $current;
            if ($end_time > strtotime(date("m/d/y", $current)." 11:59 p.m.")) {
                $test['end'] = strtotime(date("m/d/y", $current)." 11:59 p.m.");
            } else {
                $test['end'] = $end_time;
            }
            $times[] = $test;
            $current = strtotime(date("m/d/y", $current)." + 1 day");
        }

        foreach($times as $time)
        {
            $id = "date_".$time['start'];
            $fullday = "hour(timediff(session_stop, session_start)) +
                        minute(timediff(session_stop, session_start))/60";
            $startsoon = date("H", $time['start']) + date("i", $time['start'])/60;
            $endlate = "NULL";
            $col[] = "sum(if ((session_start >= '".date("Y-m-d H:i:s", $time['start'])."' and
                             session_start < '".date("Y-m-d H:i:s", $time['end'])."'),
                             ".$fullday.", NULL)) ".$id;
            $list->set_header($id, date("l", $time['start']) . "<br>" . date("m/d/y", $time['start']));
            $list->set_compute_total($id);
            $list->set_align($id, "center");
            $list->set_show_zeros($id);
            $list->set_number_format($id, 2);
        }

        // Total column
        $col[] = "sum(if ((session_start >= '".date("Y-m-d H:i:s", $start_time)."' and
                           session_start < '".date("Y-m-d H:i:s", $end_time)."'),
                           ".$fullday.", NULL)) total";
        $list->set_header("total", "Total");
        $list->set_width("total", 5);
        $list->set_align("total", "center");
        $list->set_compute_total("total");
        $list->set_number_format("total", 2);

        $statement = "select ".implode(",",$col)." from ".implode(",",$tables)." where
        ".implode(" and ",$wheres)." group by project.project_id";

        $html .= $list->draw_list($statement);
        return $html;
    }

    function session_report()
    {
        $list = new table_list($this->type);
        $tables[] = "session";
        $col[] = "session.session_id";
        $list->set_link("edit_session.php?session_id=","session_id");

        // session start column
        $col[] = "session_start";
        $list->set_header("session_start", "Date");
        $list->set_date_format("session_start", "l F d, Y");
        $list->set_width("session_start", 7);
        $list->set_align("session_start", "center");

        if ($_POST['report_start']) {
            $_SESSION['report_start'] = $_POST['report_start'];
            $_SESSION['report_end'] = $_POST['report_end'];
        }
        $report_start = $_SESSION['report_start'];
        $report_end = $_SESSION['report_end'];
        $report_start_date = new date_option($report_start);
        $report_end_date = new date_option($report_end);
        $the_report_start_date = date($report_start_date->get());
        $the_report_end_date = date($report_end_date->get());

        $tables[] = "project";
        $wheres[] = "project.project_id = session.project_id";
        $wheres[] = "project.user_id = " . $_SESSION['user_userid'];
        $wheres[] = "session_start >= '".date("Y-m-d H:i:s",$the_report_start_date)."'";
        $wheres[] = "session_start <= '".date("Y-m-d H:i:s",$the_report_end_date)."'";

        // project column
        $col[] = "project.project_name";
        $list->set_header("project_name", "Project");
        $list->set_width("project_name", 5);
        $list->set_align("project_name", "center");

        // start time column
        $col[] = "session.session_start";
        $list->set_header("session.session_start", "Start Time");
        $list->set_width("session.session_start", 5);
        $list->set_align("session.session_start", "center");
        $list->set_date_format("session.session_start", "h:i a");

        // stop time column
        $col[] = "session_stop";
        $list->set_header("session_stop", "Stop Time");
        $list->set_width("session_stop", 5);
        $list->set_align("session_stop", "center");
        $list->set_date_format("session_stop", "h:i a");

        // time worked column
        $col[] = "hour(timediff(session_stop, session_start)) +
                  minute(timediff(session_stop, session_start))/60 worked";
        $list->set_header("worked", "Time Worked");
        $list->set_compute_total("worked");
        $list->set_width("worked", 5);
        $list->set_align("worked", "center");

        // description column
        $col[] = "session.session_desc";
        $list->set_header("session_desc", "Description");

        // setup to filter on the project
        if ($_POST['project_filter_id'])
        {
            $_SESSION['project_filter_id'] = $_POST['project_filter_id'];
        }

        $project_filter = $_SESSION['project_filter_id'];
        if ($project_filter)
        {
            $project_statement = '(';
            foreach($project_filter as $cur_project_filter)
            {
                if ($project_filter[0] == $cur_project_filter)
                {
                    $project_statement .= "(project.project_id = $cur_project_filter ";
                }
                else
                {
                    $project_statement .= " or (project.project_id = $cur_project_filter ";
                }
                
                $project_statement .= "and session_start >= '" . date("Y-m_d H:i:s",$the_report_start_date) . "')";
            }
            $project_statement .= ")";
        }
        $wheres[] = $project_statement;

        $statement = "select ".implode(",",$col)." from ".implode(",",$tables)." where
        ".implode(" and ",$wheres);

        return $list->draw_list($statement);
    }

    function daily_report()
    {
        $list = new table_list($this->type);
        $tables[] = "session";
        $col[] = "session.session_id";
        $list->set_link("edit_session.php?session_id=","session_id");

        // Date column
        $col[] = "session_start";
        $list->set_header("session_start", "Date");
        $list->set_width("session_start", 7);
        $list->set_align("session_start", "center");
        $list->set_date_format("session_start", "l F d, Y");

        // Job column
        $tables[] = "job";
        $wheres[] = "job.job_id = project.job_id";
        $col[] = "job.job_name";
        $list->set_header("job_name", "Job");
        $list->set_width("job_name", 5);
        $list->set_align("job_name", "center");

        // Project column
        $tables[] = "project";
        $wheres[] = "project.project_id = session.project_id";
        $wheres[] = "project.user_id = " . $_SESSION['user_userid'];
        if ($_POST['job_filter_id'] != "all")
        {
            $wheres[] = "project.job_id = " . $_POST['job_filter_id'];
        }
        $col[] = "project.project_name";
        $list->set_header("project_name", "Project");
        $list->set_width("project_name", 5);
        $list->set_align("project_name", "center");

        // start time column
        $col[] = "session.session_start";
        $list->set_header("session.session_start", "Start Time");
        $list->set_width("session.session_start", 5);
        $list->set_align("session.session_start", "center");
        $list->set_date_format("session.session_start", "h:i a");

        // stop time column
        $col[] = "session_stop";
        $list->set_header("session_stop", "Stop Time");
        $list->set_width("session_stop", 5);
        $list->set_align("session_stop", "center");
        $list->set_date_format("session_stop", "h:i a");

        // accomplishments
        $col[] = "session.session_desc";
        $list->set_header("session_desc", "Accomplishments");

        // time worked
        $col[] = "hour(timediff(session_stop, session_start)) +
                  minute(timediff(session_stop, session_start))/60 worked";
        $list->set_header("worked", "Time Worked");
        $list->set_width("worked", 7);
        $list->set_align("worked", "center");
        $list->set_compute_total("worked");

        //$session_day = date('D',strtotime($session_start));
        //$the_now = date('d',strtotime(now));

        $date_option_now = new date_option(strtotime("now"));

        $wheres[] = "date(session_start) = date(curdate())";
        /* $wheres[] = "date(session_start) = date(strtotime("now"))"; */

        $statement = "select ".implode(",",$col)." from ".implode(",",$tables)." where
        ".implode(" and ",$wheres);

        $html = $list->draw_list($statement);

        return $html;
    }

    function recent_report()
    {
        $list = new table_list($this->type);
        $tables[] = "session";
        $col[] = "session.session_id";
        $list->set_link("edit_session.php?session_id=","session_id");

        // session start column
        $col[] = "session_start";
        $list->set_header("session_start", "Date", "desc");
        $list->set_date_format("session_start", "l F d, Y");
        $list->set_width("session_start", 7);
        $list->set_align("session_start", "center");
        $list->set_limit(5);

        $tables[] = "project";
        $wheres[] = "project.project_id = session.project_id";
        $wheres[] = "project.user_id = " . $_SESSION['user_userid'];

        // project column
        $col[] = "project.project_name";
        $list->set_header("project_name", "Project");
        $list->set_width("project_name", 5);
        $list->set_align("project_name", "center");

        // start time column
        $col[] = "session.session_start";
        $list->set_header("session.session_start", "Start Time");
        $list->set_width("session.session_start", 5);
        $list->set_align("session.session_start", "center");
        $list->set_date_format("session.session_start", "h:i a");

        // stop time column
        $col[] = "session_stop";
        $list->set_header("session_stop", "Stop Time");
        $list->set_width("session_stop", 5);
        $list->set_align("session_stop", "center");
        $list->set_date_format("session_stop", "h:i a");

        // time worked column
        $col[] = "hour(timediff(session_stop, session_start)) +
                  minute(timediff(session_stop, session_start))/60 worked";
        $list->set_header("worked", "Time Worked");
        $list->set_compute_total("worked");
        $list->set_width("worked", 5);
        $list->set_align("worked", "center");

        // description column
        $col[] = "session.session_desc";
        $list->set_header("session_desc", "Description");

        // setup to filter on the project
        /* if ($_POST['project_filter_id']) */
        /* { */
        /*     $_SESSION['project_filter_id'] = $_POST['project_filter_id']; */
        /* } */

        /* $project_filter = $_SESSION['project_filter_id']; */
        /* if ($project_filter) */
        /* { */
        /*     $project_statement = '('; */
        /*     foreach($project_filter as $cur_project_filter) */
        /*     { */
        /*         if ($project_filter[0] == $cur_project_filter) */
        /*         { */
        /*             $project_statement .= "(project.project_id = $cur_project_filter "; */
        /*             $project_statement .= "and session_start >= '" . date("Y-m_d H:i:s",$the_report_start_date) . "')"; */
        /*         } */
        /*         else */
        /*         { */
        /*             $project_statement .= " or (project.project_id = $cur_project_filter "; */
        /*             $project_statement .= "and session_start >= '" . date("Y-m_d H:i:s",$the_report_start_date) . "')"; */
        /*         } */
        /*     } */
        /*     $project_statement .= ")"; */
        /* } */
        /* $wheres[] = $project_statement; */

        $statement = "select ".implode(",",$col)." from ".implode(",",$tables)." where
        ".implode(" and ",$wheres);

        return $list->draw_list($statement);
    }
}

?>
