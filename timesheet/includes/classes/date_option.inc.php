<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

class date_option_literal
{
    function date_option_literal($time)
    {
        /* construct a statement to get this user info */
        $statement = "select * from user where user_username = '".$_SESSION['user_username']."'";
        $data = mysql_fetch_array(mysql_query($statement));

        /* get the time zone for this user's data */
        $this->time_zone = $data['user_time_zone'];
        date_default_timezone_set($this->time_zone);
 
        if (is_array($time))
        {
            $this->time = strtotime($time['year']."-".$time['month']."-".$time['day']." ".$time['hour'].":".$time['minute'].":00");
        }
        else
        {
            $this->time = $time;
        }
    }

    function display()
    {
        $html = date("Y-m-d H:i", $this->time);
        return $html;
    }

    function display_nice()
    {
        $html = date("D, M d h:i a", $this->time);
        return $html;
    }
}

class date_option
{
    function date_option($time)
    {
        /* construct a statement to get this user info */
        $statement = "select * from user where user_username = '".$_SESSION['user_username']."'";
        $data = mysql_fetch_array(mysql_query($statement));

        /* get the time increment from this user's data */
        $this->time_increment = $data['user_clock_round'];

        /* get the time zone for this user's data */
        $this->time_zone = $data['user_time_zone'];
        date_default_timezone_set($this->time_zone);
 
        if (is_array($time))
        {
            $this->time = strtotime($time['year']."-".$time['month']."-".$time['day']." ".$time['hour'].":".$time['minute'].":00");
        }
        else
        {
            $this->time = $time;
        }

        $this->time = round($this->time/(60.0*$this->time_increment))*(60.0*$this->time_increment);
    }

    function draw($name)
    {
        $html = '
<select name="'.$name.'[month]">';
        $html .= $this->month_list();
        $html .= '
</select>';

        $html .= '
<select name="'.$name.'[day]">';
        $html .= $this->day_list();
        $html .= '
</select>';

        $html .= '
<select name="'.$name.'[year]">';
        $html .= $this->year_list();
        $html .= '
</select>';

        $html .= '
<select name="'.$name.'[hour]">';
        $html .= $this->hour_list();
        $html .= '
</select>';

        $html .= ' :
<select name="'.$name.'[minute]">';
        $html .= $this->minute_list();
        $html .= '
</select>';
        return $html;
    }

    function display()
    {
        $html = date("Y-m-d H:i:s", $this->time);
        return $html;
    }

    function display_nice()
    {
        $html = date("D, M d h:i a", $this->time);
        return $html;
    }

    function get()
    {
        return $this->time;
    }

    function month_list()
    {
        $i = 1;
        $mymonth = date("n", $this->time);
        while ($i <= 12)
        {
            if ($i == $mymonth)
            {
                $selected = " selected";
            }
            else
            {
                $selected = "";
            }
            $html .= '
        <option value="'.$i.'"'.$selected.'>'.date("F", strtotime($i."/1/2000")).'</option>';
            $i++;
        }
        return $html;
    }

    function day_list()
    {
        $i = 1;
        $myday = date("d", $this->time);
        while ($i <= 31)
        {
            if ($i == $myday)
            {
                $selected = " selected";
            }
            else
            {
                $selected = "";
            }
            $html .= '
        <option value="'.$i.'"'.$selected.'>'.$i.'</option>';
            $i++;
        }
        return $html;
    }

    function year_list()
    {
        $i = date("Y", strtotime("-5 years"));
        $myyear = date("Y", $this->time);
        while ($i <= date("Y"))
        {
            if ($i == $myyear)
            {
                $selected = " selected";
            }
            else
            {
                $selected = "";
            }
            $html .= '
        <option value="'.$i.'"'.$selected.'>'.$i.'</option>';
            $i++;
        }
        return $html;
    }

    function hour_list() {
        $i = 0;
        $myhour = date("H", $this->time);
        while ($i < 24)
        {
            if ($i == $myhour)
            {
                $selected = " selected";
            }
            else
            {
                $selected = "";
            }
            $html .= '
        <option value="'.$i.'"'.$selected.'>'.$i.'</option>';
            $i++;
        }
        return $html;
      
    }

    function minute_list()
    {
        $i = 0;
        $myminute = date("i", $this->time);
        while ($i < 60)
        {
            if ($myminute == $i)
            {
                $selected = " selected";
            }
            else
            {
                $selected = "";
            }
            $html .= '
        <option value="'.$i.'"'.$selected.'>'.sprintf("%02d",$i).'</option>';
            $i += $this->time_increment;
        }
        return $html;
    }
}

?>
