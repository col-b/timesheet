<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

class user
{
    function user($user_id = '')
    {
        $statement = "select * from user where user_id = '$user_id'";
        $this->data = mysql_fetch_array(mysql_query($statement));
    }

    function option_list()
    {
        $statement = "select * from user order by user_username";
        $result = mysql_query($statement);
        while ($row = mysql_fetch_array($result))
        {
            if ($row['user_id'] == $this->data['user_id'])
            {
                $selected = " selected";
            }
            else
            {
                $selected = "";
            }
            $html .= '
             <option value="'.$row['user_id'].'"'.$selected.'>'.$row['user_username'].'</option>';
        }

        return $html;
    }

    function get_user_name()
    {
        return $this->data['user_username'];
    }
}

// ---------------------------------------------------------------------

?>
