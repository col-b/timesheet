<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

class session
{
    function session($session_id = '')
    {
        if ($session_id)
        {
            $statement = "select * from session where session_id = '$session_id'";
            $this->data = mysql_fetch_array(mysql_query($statement));
        }
        else
        {
            $statement = "select * from session where user_id = '" . $_SESSION['user_userid'] . "' order by session_id desc limit 1";
            $this->data = mysql_fetch_array(mysql_query($statement));
        }
    }

    function get_status()
    {
        $status = "Clocked Out";
        if ( $this->clocked_in() )
        {
            $project = new project($this->data['project_id']);
            $now = new date_option(strtotime("now"));
            $start = new date_option(strtotime($this->data['session_start']));

            $status = "Project (" . $project->get_project_name() . ") has been clocked in since: " .
                $start->display_nice();

            $diff = ($now->get() - $start->get())/3600.0;
            $status .= ' (' . $diff . 'hrs)';
            $status .= '<p>Current Log Time: ' . $now->display_nice();
        }
        return $status;
    }

    function get_simple_status()
    {
        $status = "Clocked Out";
        if ( $this->clocked_in() )
        {
            $project = new project($this->data['project_id']);
            $now = new date_option(strtotime("now"));
            $start = new date_option(strtotime($this->data['session_start']));

            $status = "Project (" . $project->get_project_name() . ") has been clocked in since: " .
                $start->display_nice();

            $diff = ($now->get() - $start->get())/3600;
            $hrs = round($diff);
            $min = round(($diff-$hrs)*60);
            $status .= ' (' . $hrs . 'hrs ' . $min . 'min)';
        }
        return $status;
    }

    function menu()
    {
        $html = '<form method="post" action="do_clock.php">';

        if (!$this->clocked_in())
        {
            $html .= '<p>';
            $html .= 'Project:';
            $html .= '<select name="project_id">';
            $project = new project();
            $html .= $project->option_list();
            $html .= '</select>';

            $html .= '<p>';
            $html .= '<input type="submit" name="clock_in" value="Clock In">';
            $html .= '<input type="submit" name="reclock_out" value="ReClock Out">';
            $html .= '<input type="submit" name="log_out" value="Log Out">';
        }
        else
        {
            if ( $this->data['session_desc'] )
            {
                $html .= '<p>Description:<p>';
                $html .= $this->data['session_desc'];
            }
            else
            {
                $html .= '<p>No Description Yet';
            }

            $html .= '<p><textarea name="session_desc" cols=100 rows=5 maxLength=500></textarea>';

            $html .= '<p><input type="submit" name="clock_out" value="Clock Out"> ';
            $html .= '<input type="submit" name="add_description" value="Add Description"> ';
            $html .= '<input type="submit" name="log_out" value="Log Out">';
        }
        $html .= '</form>';
        return $html;
    }

    function get_project_id()
    {
        return $this->data['project_id'];
    }

    function clocked_in()
    {
        return ($this->data['session_id'] && !$this->data['session_stop']);
    }

    function do_clock()
    {
        $_POST['session_desc'] = str_replace("\n", "<br>", $_POST['session_desc']);
        $_POST['session_desc'] = str_replace("'", "\'", $_POST['session_desc']);
        if ($_POST['clock_in'])
        {
            $user = mysql_fetch_array(mysql_query("select * from user where user_username = '" . $_SESSION['user_username'] . "'"));
            $current_time = new date_option(strtotime("now"));
            $statement = "insert into session (session_start, project_id, user_id) values
                 ('".date("Y-m-d H:i:s",$current_time->Get())."', '".$_POST['project_id']."', '".$user['user_id']."')";
            mysql_query($statement);
        }
        else if ($_POST['reclock_out'])
        {
            $statement = "select * from user where user_username = '" . $_SESSION['user_username'] . "'";
            $user = mysql_fetch_array(mysql_query($statement));

            //$statement = "select * from session where user_id = '" . $user['user_id'] . "' and session_id = '" . 30 . "'";
            $statement = "select * from session where user_id = '" . $user['user_id'] . "' order by session_id desc limit 1";
            $result = mysql_fetch_array(mysql_query($statement));

            $current_time = new date_option(strtotime("now"));
            $statement = "update session set session_stop = '".date("Y-m-d H:i:s", $current_time->Get())."' where session_id = '".$result['session_id']."'";

            mysql_query($statement);
            echo mysql_error();
        }
        else if ($_POST['clock_out'])
        {
            if ($this->data['session_id'] && !$_POST['session_desc'] && !$this->data['session_desc'])
            {
                $_SESSION['message'] = "You must post a description of your work";
            }
            else
            {
                $this->clock_out(date("Y-m-d H:i:s"), $_POST['session_desc']);
            }
        }
        else if ($_POST['add_description'])
        {
            if ($this->data['session_id'] && !$_POST['session_desc'])
            {
                $_SESSION['message'] = "You must enter a description to add one";
            }
            else
            {
                if ($this->data['session_desc'])
                {
                    $statement = "update session set session_desc = '".addslashes($this->data['session_desc'])."<br>".$_POST['session_desc']."' where session_id = '".$this->data['session_id']."'";
                }
                else
                {
                    $statement = "update session set session_desc = '".$_POST['session_desc']."' where session_id = '".$this->data['session_id']."'";
                }
                mysql_query($statement);
                echo mysql_error();
            }
        }
        else if ($_POST['log_out'])
        {
            $login = new login();
            $login->logoff();
            //unset($_SESSION['user_username']);
            //unset($_SESSION['user_password']);
        }
        header ("Location: ./");
    }

    function clock_out($date, $desc)
    {
        $current_time = new date_option(strtotime("now"));

        if ($this->data['session_desc'])
        {
            $statement = "update session set session_stop = '".date("Y-m-d H:i:s", $current_time->Get())."', session_desc = '".addslashes($this->data['session_desc'])."<br>".$desc."'
                        where session_id = '".$this->data['session_id']."'";
        }
        else
        {
            $statement = "update session set session_stop = '".date("Y-m-d H:i:s", $current_time->Get())."', session_desc = '".$desc."'
                        where session_id = '".$this->data['session_id']."'";
        }
        mysql_query($statement);
        echo mysql_error();
    }

    function draw()
    {
        $html = '<form method = "post" action = "do_edit_session.php">';
        $html .= '<input type = "hidden" name = "session_id" value = "'.$this->data["session_id"].'">';
        $html .= '<h1>';
        if ($this->data["session_stop"])
        {
            $html .= 'Clocked Out';
        }
        else
        {
            $html .= 'Clocked In';
        }
        $html .= '</h1>';
        $html .= '<p>project: <select name = "project_id"> ';
        $project = new project($this->data["project_id"]);
        $html .= $project->option_list();
        $html .= "</select>";

        // not sure why we need this, but the times are off if we don't - probably has to do with the time zone...
        $date_option_now = new date_option(strtotime("now"));

        $html .= '<p>start time: ';
        $date_option_start = new date_option(strtotime($this->data["session_start"]));
        $html .= $date_option_start->draw("session_start");

        if ($this->data["session_stop"])
        {
            $html .= '<p>end time: ';
            $date_option_stop = new date_option(strtotime($this->data["session_stop"]));
            $html .= $date_option_stop->draw("session_stop");
        }
        else
        {
            $html .= '<p>end time: Still Clocked In';
        }

        $html .= '<p><h2>Description:</h2>';
        //$html .= '<p><input type = "text" name="session_desc" size="100" value="'.$this->data["session_desc"].'"></input>';
        $initial_text = str_replace("<br>", "\n", $this->data["session_desc"]);
        $html .= '<p><textarea name = "session_desc" cols="100" rows="10">'.$initial_text.'</textarea>';

        $html .= '<p><input type = "submit" name = "ok" value = "Save">
                     <input type = "submit" name = "cancel" value = "Cancel">';
        if ($this->data["session_stop"])
        {
            $cur_session = new session($_GET{sission_id});
            if ($this->data["session_id"] == $cur_session->data['session_id'])
            {
                $html .= '<input type = "submit" name = "set_clocked_in" value = "Set as Clocked In">';
            }
        }
        $html .= '<p>Type "delete" and click delete to delete:<p>';
        $html .= '<input type = "text" name = "confirm_delete" size = "3">
                  <input type = "submit" name = "delete" value = "Delete Record">';

        $html .= '</form>';

        return $html;
    }

    function process()
    {
        if ($_POST["cancel"])
        {
            header("Location:report.inc.php");
            exit;
        }
        else if ($_POST["set_clocked_in"])
        {
            $statement = "update session set session_stop = NULL where session_id = '".$this->data['session_id']."'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement;
                echo mysql_error();
                exit;
            }
            header("Location:report.inc.php");
        }
        else if ($_POST["ok"])
        {
            $call[] = "project_id = '".$_POST["project_id"]."'";

            $session_start = new date_option($_POST["session_start"]);
            $call[] = "session_start = '" . date("Y-m-d H:i:s",$session_start->get()) . "'";

            if ($_POST["session_stop"])
            {
                $session_end = new date_option($_POST["session_stop"]);
                $call[] = "session_stop = '" . date("Y-m-d H:i:s",$session_end->get()) . "'";
            }
            $_POST["session_desc"] = str_replace("\n", "<br>", $_POST['session_desc']);
            $_POST['session_desc'] = str_replace("'", "\'", $_POST['session_desc']);
            $call[] = "session_desc = '".$_POST["session_desc"]."'";

            if ($_POST["session_stop"])
            {
                if ($session_start->get() > $session_end->get())
                {
                    $_SESSION['message'] = "is your name vicki?";
                    header("Location:edit_session.php?session_id=" . $this->data["session_id"]);
                    exit;
                }
            }

            $statement = "update session set " . implode(",", $call) . " where session_id = '" . $this->data["session_id"] . "'";
            mysql_query($statement);
            header("Location:report.inc.php");
        }
        else if ($_POST["delete"] && $_POST["confirm_delete"] == "delete")
        {
            $statement = "delete from session where session_id = '" . $this->data["session_id"] . "'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement . "<p>";
                echo mysql_error();
                exit;
            }
            header("Location:report.inc.php");
        }
        else
        {
            header("Location:report.inc.php");
            exit;
        }
    }
}

?>
