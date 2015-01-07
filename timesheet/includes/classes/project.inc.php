<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

class project
{
    //! 
    function project($project_id = '') 
    {
        $statement = "select * from project where project_id = '$project_id'";
        $this->data = mysql_fetch_array(mysql_query($statement));
    }

    //!
    function draw()
    {
        // init the form object
        $html = '<form method="post" action="do_edit_project.php">';
        
        // the project id
        $html .= "Project ID: ";
        $html .= '<input type="text" name="project_id" readonly value="' . $this->data['project_id'] . '" width=40 maxLength=50/>';

        // do the job name option list
        $html .= '<p>';
        $html .= 'Job Name: <select name = "job_id">';
        $job = new job($this->data["job_id"]);
        $html .= $job->option_list();
        $html .= "</select><p>";

        // the project name
        $html .= "Project Name: ";
        $html .= '<input type="text" name="project_name" value="' . $this->data['project_name'] . '" width=40 maxLength=50/>';

        // the owner name
        $html .= '<p>';
        $html .= 'Owner Name: <select name = "owner_id">';
        $user = new user($this->data["user_id"]);
        $html .= $user->option_list();
        $html .= "</select><p>";

        // what is the charge number?
        $html .= '<p>';
        $html .= 'Charge Number: ';
        $html .= '<input type="text" name="project_charge_number" value="' . $this->data['project_charge_number'] . '" width=30 maxLength=30/>';

        // is it active?
        $html .= '<p>';
        $html .= "Active: ";
        $html .= '<input type="text" name="project_active" readonly value="' .$this->data['project_active'] . '" width=5 maxLength=5/>';
        
        // buttons to submit or cancel
        $html .= '<p>';
        $html .= '<input type="submit" name="submit" value="Submit Changes">';
        $html .= '<input type="submit" name="cancel" value="Cancel">';

        if ( $this->data['project_active'] == '1' )
        {
            $html .= '<input type="submit" name="deactivate" value="Deactivate">';
        }
        else
        {
            $html .= '<input type="submit" name="activate" value="Activate">';
        }
            
        // add the back part...
        $html .= '<p align="left"><a href="./">&laquo; Back</a>';

        // close the form object
        $html .= '</form>';

        return $html;
    }

    //! 
    function option_list() 
    {
        $statement = "select * from project where project_id = '$project_id'";
        $data = mysql_fetch_array(mysql_query($statement));

        $statement = "select * from user where user_username = '" . $_SESSION['user_username'] . "'";
        $user_data = mysql_fetch_array(mysql_query($statement));

        $statement = "select * from project order by project_name";
        $result = mysql_query($statement);
        while ($row = mysql_fetch_array($result)) 
        {
            if ($row['user_id'] == $user_data['user_id'])
            {
                if ($row['project_active'] == "1")
                {
                    if ($row['project_id'] == $this->data['project_id']) 
                    {
                        $selected = " selected";
                    } 
                    else 
                    {
                        $selected = "";
                    }
                    $html .= '<option value="'.$row['project_id'].'"'.$selected.'>'.$row['project_name'].'</option>';
                }
            }
        }
        return $html;
    }

    //!
    function get_project_name() 
    {
        return $this->data['project_name'];
    }

    //!
    function get_project_id() 
    {
        return $this->data['project_id'];
    }

    //!
    function get_job_id() 
    {
        return $this->data['job_id'];
    }

    //!
    function get_user_id()
    {
        return $this->data['user_id'];
    }

    //!
    function process()
    {
        if ($_POST["cancel"])
        {
            // do nothing - we cancelled
            header("Location:admin.inc.php");
            exit;
        }
        else if ($_POST["submit"])
        {
            // read out the new job_id
            $new_job_id = $_POST["job_id"];

            // update the job id for this project
            $statement = "update project set job_id='" . $new_job_id . "' where project_id = '" . $this->data['project_id'] . "'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement . "<p>";
                echo mysql_error();
                exit;
            }
            
            // read out the new project_name
            $new_project_name = $_POST["project_name"];

            // submit the changes to the current project name
            $statement = "update project set project_name='" . $new_project_name . "' where project_id = '" . $this->data['project_id'] . "'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement . "<p>";
                echo mysql_error();
                exit;
            }

            // read out the owner name
            $new_owner_id = $_POST["owner_id"];

            // submit this owner name
            $statement = "update project set user_id='" . $new_owner_id . "' where project_id = '" . $this->data['project_id'] . "'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement . "<p>";
                echo mysql_error();
                exit;
            }

            // read out the new project charge number
            $new_project_charge_number = $_POST["project_charge_number"];

            // submit the changes to the current project charge number
            $statement = "update project set project_charge_number='" . $new_project_charge_number . "' where project_id = '" . $this->data['project_id'] . "'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement . "<p>";
                echo mysql_error();
                exit;
            }

            header("Location:admin.inc.php");
            exit;
        }
        else if ($_POST["activate"])
        {
            $statement = "update project set project_active='1' where project_id = '" . $this->data['project_id'] . "'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement . "<p>";
                echo mysql_error();
                exit;
            }
            
            header("Location:admin.inc.php");
            exit;
        }
        else if ($_POST["deactivate"])
        {
            // deleting the project really just sets it invalid
            $statement = "update project set project_active='0' where project_id = '" . $this->data['project_id'] . "'";
            mysql_query($statement);
            if (mysql_error())
            {
                echo $statement . "<p>";
                echo mysql_error();
                exit;
            }
            
            header("Location:admin.inc.php");
            exit;
        }
        else if ($_POST["new_project"])
        {
            // extract all the post data
            $new_project_job_id                = $_POST['new_project_job_id'];
            $new_project_project_name          = $_POST['new_project_project_name'];
            $new_project_owner_id              = $_POST['new_project_owner_id'];
            $new_project_project_active        = $_POST['new_project_project_active'];
            $new_project_project_charge_number = $_POST['new_project_project_charge_number'];

            // make sure we don't already have this project name
            $statement = "select * from project where project_name = '" . $new_project_project_name . "'";
            $result = mysql_fetch_array(mysql_query($statement));
            if ($result['project_name'])
            {
                echo "Project already exists: " . $project_name;
                exit;
            }

            // we are ok, so we can insert this project - let's build the statement
            $query = sprintf("INSERT INTO project (job_id, project_name, user_id, project_active, project_charge_number) VALUES ('%s', '%s', '%s', '%s', '%s');",
                             mysql_real_escape_string($new_project_job_id),
                             mysql_real_escape_string($new_project_project_name),
                             mysql_real_escape_string($new_project_owner_id),
                             mysql_real_escape_string($new_project_project_active),
                             mysql_real_escape_string($new_project_project_charge_number));
            
            // now query the statement
            mysql_query($query);
            if (mysql_error())
            {
                echo $query . "<p>";
                echo mysql_error();
                exit;
            }

            // ok, so now that we have inserted the project, we need to get
            // it so that we can see what the project_id is
            $query = "select * from project where project_name = '" . $new_project_project_name . "'";
            $result = mysql_fetch_array(mysql_query($query));
            $new_project_project_id = $result['project_id'];

            // now we need to add this project_id / user_id to the project_to_user table
            $query = sprintf("INSERT INTO project_to_user (project_id, user_id) VALUES ('%s', '%s');",
                             $new_project_project_id,
                             $new_project_owner_id);

            // now run the query
            mysql_query($query);
            if (mysql_error())
            {
                echo $query . "<p>";
                echo mysql_error();
                exit;
            }

            header("Location:admin.inc.php");
            exit;
        }
        else
        {
            header("Location:admin.inc.php");
            exit;
        }
    }
}

// ---------------------------------------------------------------------
?>
