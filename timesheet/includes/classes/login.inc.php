<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

define (kMinPasswordLength, 6);

class login
{
    function login()
    {
        // Check to see if we have a valid login session
        $statement = "select * from user where user_username = '".$_SESSION['user_username']."' and user_password = '".$_SESSION['user_password']."'";
        $this->data = mysql_fetch_array(mysql_query($statement));

        // usefull for pw changing...
        // $_SESSION['message'] = $_SESSION['user_password'];

        if (!$this->data['user_id'])
        {
            if ( isset($_COOKIE['timesheet_login_info_user_cookie']) &&
                 isset($_COOKIE['timesheet_login_info_pw_cookie']) )
            {
                $_SESSION['user_username'] = $_COOKIE['timesheet_login_info_user_cookie'];
                $_SESSION['user_userid']   = $_COOKIE['timesheet_login_info_id_cookie'];
                $_SESSION['user_password'] = $_COOKIE['timesheet_login_info_pw_cookie'];
                $statement = "select * from user where user_username = '".$_SESSION['user_username']."' and
user_password = '".$_SESSION['user_password']."'";
                $this->data = mysql_fetch_array(mysql_query($statement));

                header("Location: index.php");
                exit;
            }
        }

        if (!$this->data['user_id'])
        {
            if ($_SESSION['user_username'])
            {
                $_SESSION['message'] = "That username/password combination is not valid.";
                unset($_SESSION['user_username']);
                unset($_SESSION['user_userid']);
            }
            if (!preg_match("/login.(html.php|php)$/", $_SERVER['REQUEST_URI']))
            {
                $_SESSION['login_uri'] = $_SERVER['REQUEST_URI'];
                header("Location: login.html.php");
                exit;
            }
        }
        else
        {
            // set the cookies for this user
            setcookie("timesheet_login_info_user_cookie", $_SESSION['user_username'], time()+60*60*24*30);
            setcookie("timesheet_login_info_id_cookie",   $_SESSION['user_userid'],       time()+60*60*24*30);
            setcookie("timesheet_login_info_pw_cookie",   $_SESSION['user_password'], time()+60*60*24*30);

            // Update the last login value
            $statement = "update user set user_last_login = now() where user_id = '".$this->data['user_id']."'";
            mysql_query($statement);
        }
    }

    function process_login()
    {
        $_SESSION['user_username'] = $_POST['user_username'];
        $_SESSION['user_password'] = md5($_POST['user_password']);

        // I think we would rather always be returned to the front page on a login beacuse
        // so many pages are dependent on the session posts
        //header("Location: ".$_SESSION['login_uri']);
        header("Location: index.php");
        exit;
    }

    function logoff()
    {
        unset($_SESSION['user_username']);
        unset($_SESSION['user_userid']);
        unset($_SESSION['user_password']);

        setcookie("timesheet_login_info_user_cookie", "", time()-3600);
        setcookie("timesheet_login_info_pw_cookie",   "", time()-3600);

        header("Location: ./");
    }

    function user_id()
    {
        return $this->data['user_id'];
    }

    function interface_check($interface_id)
    {
        // Checks to see if this interface is accessible by our user
        $tables[] = "user_group_to_interface";
        $wheres[] = "user_group_to_interface.interface_id = '$interface_id'";
        $tables[] = "user_group";
        $wheres[] = "user_group.user_group_id = user_group_to_interface.user_group_id";
        $tables[] = "user_to_group";
        $wheres[] = "user_to_group.group_id = user_group.user_group_id";
        $wheres[] = "user_to_group.user_id = '".$this->data['user_id']."'";
        $statement = "select * from ".implode(",",$tables)." where ".implode(" and ",$wheres);
        if (mysql_num_rows(mysql_query($statement)))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    function change_password()
    {
        // Receiving the form from the change password page
        // Check for cancel
        if ($_POST['cancel'])
        {
            header('Location: ./');
            exit;
        }

// First check the current password
        if ($this->data['user_password'] != md5($_POST['current_password']))
        {
            $_SESSION['message'] = "The current password was entered incorrectly.";
            header("Location: change_password.html");
            exit;
        }

        if ($_POST['new_password'] != $_POST['verify_password'])
        {
            $_SESSION['message'] = "The new password did not match the verification.";
            header("Location: change_password.html");
            exit;
        }

        if (strlen($_POST['new_password']) < kMinPasswordLength)
        {
            $_SESSION['message'] = "Your password must be at least ".kMinPasswordLength." characters long.";
            header("Location: change_password.html");
            exit;
        }

        // Change the password!
        $statement = "update user set user_password = '".md5($_POST['new_password'])."' where user_id = '".$this->data['user_id']."'";
        mysql_query($statement);
        $_SESSION['user_password'] = md5($_POST['new_password']);
        $_SESSION['message'] = "Your password has been changed.";
        header("Location: ./");
        exit;
    }
}
?>
