<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    CodeIgniter
 * @author    EllisLab Dev Team
 * @copyright    Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright    Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license    http://opensource.org/licenses/MIT	MIT License
 * @link    https://codeigniter.com
 * @since    Version 1.3.1
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * HTML Table Generating Class
 *
 * Lets you create tables manually or from database result objects, or arrays.
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    HTML Tables
 * @author        EllisLab Dev Team
 * @link        https://codeigniter.com/user_guide/libraries/table.html
 */
class CI_Table
{

    public $rows = [];
    public $columns = [];
    public $auto_heading = true;
    public $caption = null;
    public $template = null;
    public $newline = "\n";
    public $empty_cells = '';
    private $actions = [];
    private $url = null;
    public $function = null;

    /**
     * Ustawia szablon z pliku konfiguracyjnego tabel jesli istnieje
     *
     * @param    array $config (default: array())
     * @return    void
     */
    public function __construct($config = [])
    {
        // inicjalizacja configu
        foreach ($config as $key => $val) {
            $this->template[$key] = $val;
        }

        log_message('info', 'Table Class Initialized');
    }

    /**
     * Przygotowanie argumentów
     *
     * Ensures a standard associative array format for all cell data
     *
     * @param    array
     * @return    array
     */
    protected function _prep_args($args)
    {
        // If there is no $args[0], skip this and treat as an associative array
        // This can happen if there is only a single key, for example this is passed to table->generate
        // array(array('foo'=>'bar'))
        if (isset($args[0]) && count($args) === 1 && is_array($args[0]) && !isset($args[0]['data'])) {
            $args = $args[0];
        }

        foreach ($args as $key => $val) {
            is_array($val) OR $args[$key] = array('data' => $val);
        }

        return $args;
    }

    /**
     * Add a table caption
     *
     * @param    string $caption
     * @return    CI_Table
     */
    public function set_caption($caption)
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * Ustawienie szablonu
     *
     * @param    array $template
     * @return    bool
     */
    public function set_template($template)
    {
        if (!is_array($template)) {
            return FALSE;
        }

        $this->template = $template;
        return TRUE;
    }

    /**
     * Dodanie dowolnej akcji
     */
    public function add_action($url, $icon, $name = null, $new_window = null, $param = null, $style = null)
    {
        if (empty($param)) {
            $param = 'id';
        }

        $this->actions [] = [
            'name' => $name,
            'url' => $url,
            'icon' => $icon,
            'param' => $param,
            'style' => "text-align:center;" . $style,
            'new_window' => $new_window
        ];

    }

    /**
     * Obsługa klinięcia rekordu
     */
    public function add_click($url = null)
    {
        if (empty($url)) {
            $url = get_path() . '/details';
        }
        $this->url = $url;
    }

    /**
     * Dodanie akcji usuwania
     */
    public function add_action_delete($url = null)
    {
        if(empty($url)) {
            $url = get_path() . '/delete';
        }
        $this->add_action($url, '/assets/icons/trash.png', _('Usuń'));
    }

    /**
     * Stworzenie kolumn
     *
     * @param    array $array
     * @param    int $col_limit
     * @return    array
     */
    public function make_columns($array = [], $col_limit = 0)
    {
        if (!is_array($array) OR count($array) === 0 OR !is_int($col_limit)) {
            return FALSE;
        }

        // Turn off the auto-heading feature since it's doubtful we
        // will want headings from a one-dimensional array
        $this->auto_heading = FALSE;

        if ($col_limit === 0) {
            return $array;
        }

        $new = array();
        do {
            $temp = array_splice($array, 0, $col_limit);

            if (count($temp) < $col_limit) {
                for ($i = count($temp); $i < $col_limit; $i++) {
                    $temp[] = '&nbsp;';
                }
            }

            $new[] = $temp;
        } while (count($array) > 0);

        return $new;
    }

    /**
     * Ustawienie pustych komórek
     *
     * Can be passed as an array or discreet params
     *
     * @param    mixed $value
     * @return    CI_Table
     */
    public function set_empty($value)
    {
        $this->empty_cells = $value;
        return $this;
    }

    /**
     * Dodanie kolejnego wiersza
     *
     * Can be passed as an array or discreet params
     *
     * @param    mixed
     * @return    CI_Table
     */
    public function add_row($args = [])
    {
        $this->rows[] = $this->_prep_args(func_get_args());
        return $this;
    }

    /**
     * Dodanie nowej kolumny
     *
     * @param    string $column_name
     * @param    string $db_field
     * @param    array $name_map
     * @return    array
     */
    public function add_column($column_name, $db_field, $name_map = null)
    {
        if($name_map === true) {
            $name_map = [0 => _('nie'), 1 => _('tak')];
        }
        $this->columns[$db_field] = ['column_name' => $column_name, 'name_map' => $name_map];
        return $this;
    }

    /**
     * Generowanie tabeli
     *
     * @param    mixed $table_data
     * @return    string
     */
    public function generate($table_data = null)
    {
        // The table data can optionally be passed to this function
        // either as a database result object or an array

        //Modyfikacje pod wysyłanie listy obiektów i pobranie nazw headerów
        $fields = null;
        if (!is_array($table_data)) {
            if (!empty(current($table_data))) {
                $fields = (array)current(current($table_data))->fields;
            }
            $table_data = $table_data->toArray();
        }

        // Is there anything to display? No? Smite them!
        if (empty($this->columns)) {
            return _('Undefined table columns');
        }

        // Build the table rows
        if (!empty($table_data)) {

            // Compile and validate the template date
            $this->_compile_template();

            // Validate a possibly existing custom cell manipulation function
            if (isset($this->function) && !is_callable($this->function)) {
                $this->function = NULL;
            }

            // Build the table!

            if (!empty($this->url)) {
                $this->template['table_open'] = '<table class="datatable" data-href="' . $this->url . '">';
                $this->template['tbody_open'] = '<tbody style="cursor: pointer">';
            }
            $out = $this->template['table_open'] . $this->newline;

            // Add any caption here
            if ($this->caption) {
                $out .= '<caption>' . $this->caption . '</caption>' . $this->newline;
            }

            // Is there a table heading to display?
            if (!empty($this->columns)) {
                $out .= $this->template['thead_open'] . $this->newline . $this->template['heading_row_start'] . $this->newline;

                foreach ($this->columns as $column) {
                    $out .= $this->template['heading_cell_start'] . $column['column_name'] . $this->template['heading_cell_end'];
                }

                if (!empty($this->actions)) {
                    $out .= '<th style="text-align: center">' . _('Akcje') . $this->template['heading_cell_end'];
                }

                $out .= $this->template['heading_row_end'] . $this->newline . $this->template['thead_close'] . $this->newline;
            }

            $out .= $this->template['tbody_open'] . $this->newline;

            $i = 1;
            $headings = array_keys($this->columns);
            foreach ($table_data as $row) {
                $row = (array)$row;

                if (!is_array($row)) {
                    break;
                }

                // We use modulus to alternate the row colors
                $name = fmod($i++, 2) ? '' : 'alt_';

                $out .= '<tr data-id="' . $row['id'] . '">' . $this->newline;
                $temp = null;

                foreach ($headings as $heading) {

                    $cell = $row[$heading];
                    $name_map = $this->columns[$heading]['name_map'];
                    if(!empty($name_map) && isset($name_map[$cell])) {
                        $cell = $name_map[$cell];
                    }

                    $out .= $this->template['cell_' . $name . 'start'] . $cell . $this->template['cell_' . $name . 'end'];
                }

                if (!empty($this->actions)) {
                    $out .= '<td style="text-align:center;width: ' . (count($this->actions) * 35) . 'px">';

                    foreach ($this->actions as $action) {
                        $url = $action['url'] . '/' . $row['id'];
                        $target = !empty($action['new_window']) ? 'onclick="window.open(\'' . $url . '\', \'newwindow\', \'width=' . $action['new_window'][0] . ',height=' . $action['new_window'][1] . '\');" ' : 'href="' . $url . '"';
                        $out .= '<a ' . $target . '><img src="' . $action['icon'] . '" class="action_button" title="' . $action['name'] . '" style="' . $action['style'] . '"></a>';
                    }

                    $out .= $this->template['cell_' . $name . 'end'];
                }

                $out .= $this->template['row_' . $name . 'end'] . $this->newline;
            }

            $out .= $this->template['tbody_close'] . $this->newline;
        } else {
            return '<div class="datatable empty_datatable">' . _('Brak danych') . '!</div>';
        }

        $out .= $this->template['table_close'];

        // Clear table class properties before generating the table
        $this->clear();

        return $out;
    }

    /**
     * Czyszczenie zawartości tabeli
     * Useful if multiple tables are being generated
     *
     * @return    CI_Table
     */
    public function clear()
    {
        $this->rows = array();
        $this->heading = array();
        $this->auto_heading = TRUE;
        return $this;
    }

    /**
     * Kompilowanie szablonu
     *
     * @return    void
     */
    protected function _compile_template()
    {
        if ($this->template === NULL) {
            $this->template = $this->_default_template();
            return;
        }

        $this->temp = $this->_default_template();
        foreach (array('table_open', 'thead_open', 'thead_close', 'heading_row_start', 'heading_row_end', 'heading_cell_start', 'heading_cell_end', 'tbody_open', 'tbody_close', 'row_start', 'row_end', 'cell_start', 'cell_end', 'row_alt_start', 'row_alt_end', 'cell_alt_start', 'cell_alt_end', 'table_close') as $val) {
            if (!isset($this->template[$val])) {
                $this->template[$val] = $this->temp[$val];
            }
        }
    }

    /**
     * Domyślny szablon
     *
     * @return    array
     */
    protected function _default_template()
    {
        return array(
            'table_open' => '<table class="datatable">',

            'thead_open' => '<thead>',
            'thead_close' => '</thead>',

            'heading_row_start' => '<tr>',
            'heading_row_end' => '</tr>',
            'heading_cell_start' => '<th>',
            'heading_cell_end' => '</th>',

            'tbody_open' => '<tbody>',
            'tbody_close' => '</tbody>',

            'row_start' => '<tr>',
            'row_end' => '</tr>',
            'cell_start' => '<td>',
            'cell_end' => '</td>',

            'row_alt_start' => '<tr>',
            'row_alt_end' => '</tr>',
            'cell_alt_start' => '<td>',
            'cell_alt_end' => '</td>',

            'table_close' => '</table>'
        );
    }

}
