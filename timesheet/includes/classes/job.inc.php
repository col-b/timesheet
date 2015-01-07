<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

class job
{

    function job($job_id = '')
    {
        $statement = "select * from job where job_id = '$job_id'";
        $this->data = mysql_fetch_array(mysql_query($statement));
    }


    function option_list($all = '')
    {
        $state = mysql_query("select * from job_to_user where user_id = '" . $_SESSION['user_userid'] . "'");
        while ($job_id = mysql_fetch_array($state))
        {
            $statement = "select * from job where job_id = '" . $job_id['job_id'] . "' order by job_name";
            $result = mysql_query($statement);
            while ($row = mysql_fetch_array($result))
            {
                if ($row['job_id'] == $this->data['job_id'])
                {
                    $selected = " selected";
                }
                else
                {
                    $selected = "";
                }
                $html .= '<option value="'.$row['job_id'].'"'.$selected.'>'.$row['job_name'].'</option>';
            }
        }

        if ($all == "all")
        {
            $html .= '<option value="all" selected>All</option>';
        }
        else
        {
            $html .= '<option value="all">All</option>';
        }
        return $html;
    }

    function get_job_name()
    {
        return $this->data['job_name'];
    }
}

?>
