<?php
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 **************************************************************************/

class table_list {
    // This class will handle the drawing of all lists.  Call the initialization functions to set
    // things like the headers, date format, etc.  Then call the draw_list function with the sql statement
    // to draw the list.  It will keep track of its own page number, sort, etc.
    function table_list($page) {
        $this->header = array();
        $this->interface_id = $page;

        // Retrieve the order_by value
        $this->order_by = $_GET['order_by'];
        if (!$this->order_by) {
            $this->order_by = $_SESSION['list_order_by'][$this->interface_id];
        }
        $_SESSION['list_order_by'][$this->interface_id] = $this->order_by;

        // Retrieve the page page value, and set to 0 if not set
        $this->page = $_GET['start'];
        if (!$this->page) {
            $this->page = $_SESSION['current_page'][$this->interface_id];
        }
        if (!$this->page) {
            $this->page = 1;
        }
        $_SESSION['current_page'][$this->interface_id] = $this->page;

        // Retrieve the records per page, set it to 100 if not sen
        $this->records_per_page = $_GET['records_per_page'];
        if (!$this->records_per_page) {
            $this->records_per_page = $_COOKIE['records_per_page_'.$this->interface_id];
        }
        if (!$this->records_per_page) {
            $this->records_per_page = 100;
        }
        setcookie('records_per_page_'.$this->interface_id, $this->records_per_page, time()+60*60*24*360*10);
    }

    function append_query($queries) {
        // Add $queries to the end of the query to get back to this page
        $this->queries = array_merge($this->queries, $queries);
    }

    function set_header($column, $text, $default_sort = '', $link = true, $sort = true) {
        $this->header[$column]['text'] = $text;
        $this->header[$column]['sort'] = $sort;
        $this->header[$column]['link'] = $link;
        if ($default_sort || !$this->default_sort) {
            $this->default_sort = $column;
            if ($default_sort == 'desc') {
                $this->default_sort .= ' desc';
            }
        }
    }

    function set_width($column, $width) {
        $this->header[$column]['width'] = $width;
    }

    function set_align($column, $align) {
        $this->header[$column]['align'] = $align;
    }

    function set_compute_total($column) {
        $this->header[$column]['compute_total'] = 1;
    }

    function set_show_zeros($column) {
        $this->header[$column]['show_zeros'] = 1;
    }

    function clear_total_column_bold($column) {
        $this->header[$column]['dont_use_h3_on_total_row'] = 1;
    }

    function set_date_format($column, $format) {
        $this->header[$column]['date'] = $format;
    }

    function set_link($link, $id_col) {
        // Pass the link in the form of "...?id=" and the value of $id_col will be appended to the end of the link.
        $this->link = $link;
        $this->id_col = $id_col;
    }

    function set_data_format($column, $format) {
        // Pass the format in "text text %data% text text" - %data% will be replaced by the data, %id% will be replaced by
        // $id_col in the link
        $this->header[$column]['format'] = $format;
    }

    function set_number_format($column, $format) {
        $this->header[$column]['number_format'] = $format;
    }

    function set_exec($column, $command) {
        // A command to execute on the data, passing the data as the single argument
        $this->header[$column]['exec'] = $command;
    }

    function add_order_by($column) {
        $this->add_order_by[] = $column;
    }

    function set_form($action, $method) {
        // Info so that we can draw a form around the table
        $this->form['action'] = $action;
        $this->form['method'] = $method;
    }

    function set_form_button($name, $value, $type="submit") {
        $this->buttons[$name]['value'] = $value;
        $this->buttons[$name]['type'] = $type;
    }

    function set_limit($limit) {
        $this->limit = $limit;
    }

    function draw_list($statement) {
        // Generate the queries for the heading links
        $queries = array();
        if (is_array($this->queries)) {
            foreach($this->queries as $key=>$value) {
                $queries[] = "$key=$value";
            }
        }
        $queries = implode("&", $queries);
        // Append the order by
        $columns = count($this->header);
        if (!array_key_exists(preg_replace("/ desc/", "", $this->order_by), $this->header)) {
            $this->order_by = $this->default_sort;
        }
        $statement .= " order by ".$this->order_by;
        if (is_array($this->add_order_by)) {
            $statement .= ', '.implode(",",$this->add_order_by);
        }

        if ( $this->limit ) {
            $statement .= ' limit ' . $this->limit;
        }
        
        $result = mysql_query($statement);
        if (mysql_error()) {
            $html = "<p>There was an error in the list statement:";
            $html .= "<p>".$statement;
            $html .= "<p>".mysql_error();
            return $html;
        }
        // Count the rows
        $this->rows = mysql_num_rows($result);
        $this->pages = ceil($this->rows/$this->records_per_page);
        if ($this->page > $this->pages - 1) {
            $this->page = max($this->pages, 1);
        } elseif ($this->page < 1) {
            $this->page = 1;
        }
        // Decrement $this->page so that we have it 0-based
        $this->page --;

        // Draw the pages
        $html = $this->draw_page_numbers();

        // Draw the form if we have one
        if (is_array($this->form)) {
            $html .= '
<form method="'.$this->form['method'].'" action="'.$this->form['action'].'">';
        }

        // Draw the header
        $html .= '
<table class="datatable" align="center">
  <tr>';
        foreach ($this->header as $header=>$values) {
            if (preg_match("/".$header."/", $this->order_by)) {
                if (preg_match("/desc/", $this->order_by)) {
                    $arrow = '<img src="images/arrow_down.gif" border="0"> ';
                    $desc = '';
                } else {
                    $arrow = '<img src="images/arrow_up.gif" border="0"> ';
                    $desc = " desc";
                }
            } else {
                $arrow = "";
                $desc = "";
            }
            $html .= '
    <th class="heading" width="'.$values['width'].'%">';
            //$html .= '
            //<th class="heading" width="'.$width.'%">';
            if ($values['sort']) {
                $html .= '
      <a href="?'.$queries.'&order_by='.$header . $desc.'">';
            }
            $html .= $values['text'].' '.$arrow;
            if ($values['sort']) {
                $html .= '
      </a>';
            }
            $html .= '
    </th>';
        }
        $html .= '
  </tr>';
        // Draw the rows
        $first = $this->page * $this->records_per_page;
        $last = $first + $this->records_per_page;
        if ($last > mysql_num_rows($result)) {
            $last = mysql_num_rows($result);
        }
        while ($first < $last) {
            if ($class == "light") {
                $class = "dark";
            } else {
                $class = "light";
            }
            $html_tmp = '
  <tr>';
	    $no_totals_found = 1;
            foreach ($this->header as $header=>$values) {
                if ($this->link) {
                    if (preg_match('/%id/', $this->link)) {
                        $link = str_replace("%id%", mysql_result($result, $first, $this->id_col), $this->link);
                    } else {
                        $link = $this->link . mysql_result($result, $first, $this->id_col);
                    }
                } else {
                    $link = '';
                }
                $html_tmp .= '
    <td class="'.$class.'" align="'.$values['align'].'">';
                if ($link && $values['link']) {
                    $html_tmp .= '
      <a href="'.$link.'">';
                }

                $data = mysql_result($result, $first, $header);
                if ($values['exec']) {
                    $command = $values['exec'];
                    $data = $command($data);
                }
                if ($values['date']) {
                    if ($data == '0000-00-00' || $data == '0000-00-00 00:00:00' || !$data) {
                        $data = "";
                    } else {
                        $data = date($values['date'], strtotime($data));
                    }
                }
                if ($values['number_format']) {
                    $data = number_format($data, $values['number_format']);
                    $running_total[$values['text']] += $data;
		    $no_totals_found = 0;
                }
                if ($values['format']) {
                    $data = str_replace("%data%", $data, $values['format']);
                    $running_total[$values['text']] += $data;
                }
                if ($this->link) {
                    $data = str_replace("%id%", mysql_result($result, $first, $this->id_col), $data);
                    $running_total[$values['text']] += $data;
                }
                if (!$data) {
                    $data = '&nbsp;';
                }
                $html_tmp .= $data;
                if ($link) {
                    $html_tmp .= '
      </a>';
                }
                $html_tmp .= '
    </td>';
            }
            $html_tmp .= '
  </tr>';
            $first++;
	    if ($no_totals_found || $data > 0) {
	      $html .= $html_tmp;
	    } else { // in order to keep the row color switching, we need to force a switch in this case
	      if ($class == "light") {
                $class = "dark";
	      } else {
                $class = "light";
	      }
	    }
        }

        if ($class == "light") {
            $class = "dark";
        } else {
            $class = "light";
        }
        
        // first count to see if we need a total colmn at all
        $total_count = 0;
        foreach ($this->header as $header=>$values) {
            if ($values['compute_total']) {
                $total_count += 1;
            }
        }
        $first_column = 1;
        if ($total_count) {
            foreach ($this->header as $header=>$values) {
                // if this is the first column and it has not been set to compute total
                // then give it the TOTAL label
                if ($first_column) {
                    $first_column = 0;
                    if (!$values['compute_total'])
                    {
                        //$html .= '<td class="'.$class.'" align="'.$values['align'].'"><h3>TOTAL</h3>';
                        $html .= '<td class="'.$class.'" align="'.$values['align'].'">';
                        if (!$values['dont_use_h3_on_total_row']) {
                            $html .= '<h3>';
                        }
                        $html .= 'TOTAL';
                        continue;
                        if (!$values['dont_use_h3_on_total_row']) {
                            $html .= '</h3>';
                        }
                    }
                }
                // otherwise, put in the total values
                $html .= '
  <td class="'.$class.'" align="'.$values['align'].'">';
                if ($values['compute_total']) {
                    if ($running_total[$values['text']]) {
                        if (!$values['dont_use_h3_on_total_row']) {
                            $html .= '<h3>';
                        }
                        $html .= number_format($running_total[$values['text']],2);
                        if (!$values['dont_use_h3_on_total_row']) {
                            $html .= '</h3>';
                        }
                    } else if ($values['show_zeros']){
                        if (!$values['dont_use_h3_on_total_row']) {
                            $html .= '<h3>';
                        }
                        $html .= number_format(0,2);
                        if (!$values['dont_use_h3_on_total_row']) {
                            $html .= '</h3>';
                        }
                    }
                }
                $html .= '
  </td>';
            }
        }

        $html .= '
</table>';

        // Close the form
        if (is_array($this->buttons)) {
            $html .= '
<p align="center">';
            foreach ($this->buttons as $name=>$value) {
                $html .= '
<input type="'.$value['type'].'" name="'.$name.'" value="'.$value['value'].'">';
            }
        }

        if (is_array($this->form)) {
            $html .= '
</form>';
        }
        // Draw the pages at the end
        $html .= $this->draw_page_numbers();
        return $html;
    }

    function draw_page_numbers() {
        $id = uniqid('form');
        $page = 1;
        $html = '
<form method="get" action="" name="'.$id.'">
<table class="pages" align="center">
  <tr>
    <td class="pages">Page:</td>
    <td class="pages">
      <select name="start" onchange="document.'.$id.'.submit();" class="pages">';
        while ($page <= $this->pages) {
            if ($this->page+1 == $page) {
                $selected = " selected";
            } else {
                $selected = "";
            }
            $html .= '
<option value="'.$page.'"'.$selected.'>'.$page.'</option>';
            $page++;
        }
        $html .= '
      </select>
    </td>
    <td class="pages" width="25">&nbsp;</td>
    <td class="pages">Rows Per Page:</td>
    <td class="pages">
      <select name="records_per_page" onchange="document.'.$id.'.submit()">';
        $options = array(5, 10, 25, 50, 100, 200, 300, 400, 500);
        foreach($options as $value) {
            if ($value == $this->records_per_page) {
                $selected = " selected";
            } else {
                $selected = "";
            }
            $html .= '
        <option value="'.$value.'"'.$selected.'>'.$value.'</option>';
        }
        $html .= '
      </select>
    </td>
  </tr>
</table>
</form>';
        return $html;
    }
}

?>
