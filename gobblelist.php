<?php

/**
 * Plugin Name: GobbleList
 * Plugin URI: https://github.com/alisonmf/Gobble-List
 * Description: Generic Paginated List function
 * Version: 1
 * Author: AMF&MCFW
 * Author URI: https://github.com/alisonmf/Gobble-List
*/

require_once('tpl/tpl.php');

if(! class_exists("GobbleList"))
{
   /**
   * GobbleList
   *
   * @todo table aliases are flawed
   */
   class GobbleList
   {
      /**
      * Native wordpress database object (referenced)
      *
      * @var object $database
      */
      private $database;

      /**
      * Default template pieces for building the list output
      *
      * @var array $template
      */
      public $template;

      /**
      * A collection of overall output
      *
      * @var array $output
      */
      private $output;

      /**
      * All optional parameters
      *
      * @var array $items
      */
      public $items;

      public function __construct()
      {
         global $wpdb;
         global $widgetTemplate;

         add_action( is_admin() ? 'admin_head' : 'wp_head', array($this, 'init_admin_head'));

         if(! empty($wpdb))
         {
            $this->database = &$wpdb;
         }

         $this->template = $widgetTemplate;
         $this->output   = array();

         /**
         * All optional parameters
         */
         $this->items = array();
         $this->items['sql']            = '';
         $this->items['sql_last_run']   = '';
         $this->items['view']           = '';
         $this->items['query']          = '';
         $this->items['name']           = '';
         $this->items['mode']           = 'sql'; //sql, view, table
         $this->items['id']             = '';
         $this->items['title']          = '';
         $this->items['dataLength']     = 25;
         $this->items['noDataMsg']      = 'Currently no data exists.';
         $this->items['target']         = '';
         $this->items['pagerClass']     = 'pagination';
         $this->items['class']          = 'listContainer';
         $this->items['table_class']    = '';
         $this->items['column_sort_order'] = 'ASC';
         $this->items['column_next_sort_order'] = '';
         $this->items['column_sort_field'] = '';
         $this->items['column_sort_list'] = false;
         $this->items['orderBy']        = '';
         $this->items['headers_class']  = array();
         $this->items['rowStart']       = 0;
         $this->items['rowLimit']       = 10;
         $this->items['rowMax']         = 0;
         $this->items['sequence']       = 1;
         $this->items['totalPages']     = 0;
         $this->items['append_criteria']= '';
         $this->items['filter_preserved'] = array();
         $this->items['filter']         = array();
         $this->items['fields']         = array();
         $this->items['bind_vars']      = array();
         $this->items['columns']        = array();
         $this->items['columns_class']  = array();
         $this->items['columns_sort']   = array();
         $this->items['columns_str_limit'] = array();
         $this->items['urlParameters']  = array();
         $this->items['urlParameters']  = array();
         $this->items['tpl_pieces']     = array();
         $this->items['bottomNav']      = false;
         $this->items['altRow']         = 'alternate';
         $this->items['use_check_all']  = false;
         $this->items['append_filter_url'] = true;
         $this->items['interactive_mode'] = 'ajax';
         $this->items['list_jump_function'] = 'list_jump';
         $this->items['list_jump_action'] = '';

         /**
         * search
         */
         $this->items['search']['fields']  = '';
         $this->items['search']['id_wrapper']  = '';
         $this->items['search']['id_field']  = '';
         $this->items['search']['id_template_advanced']  = '';
         $this->items['search']['template_advanced']  = array();
      }

        private function SplitIntegerCsv($searchFilter)
        {
          $searchString = '';

          if(stristr($searchFilter, ','))
          {
             $criteriaTmp = explode(',', $searchFilter);

             $searchString = array();

             foreach($criteriaTmp as $key => $value)
             {
                $tmp = intval($value);
                $searchString[] = $tmp;

                if(! is_numeric($tmp) || empty($tmp))
                {
                   $searchString = '';
                   break;
                }
             }

             $searchString = implode(',',$searchString);
          }
          else
          {
             $tmp = intval($searchFilter);
             $searchString = $tmp;

             if(! is_numeric($tmp) || empty($tmp))
             {
                $searchString = '';
             }
          }

          return $searchString;
        }

      public function BuildList($list = array())
      {
        $theList   = '';
        $templates = array();

         if(is_array($list) && ! empty($list))
         {
            $this->items = array_merge($this->items,$list);
         }

         /**
         * Template sets
         */
         switch($this->items['interactive_mode'])
         {
             case 'ajax':
                $this->template['list_pagination_nav_first_jump'] = $this->template['list_pagination_nav_first_jump_ajax'];//First Page Jump
                $this->template['list_pagination_nav_row_active'] = $this->template['list_pagination_nav_row_active_ajax'];
                $this->template['list_pagination_nav_single_jump'] = $this->template['list_pagination_nav_single_jump_ajax']; //Inividual number jump
                $this->template['list_pagination_nav_max_jump'] = $this->template['list_pagination_nav_max_jump_ajax'];//Last Page Jump
             break;
             default:
                $this->template['list_pagination_nav_first_jump'] = $this->template['list_pagination_nav_first_jump_link'];//First Page Jump
                $this->template['list_pagination_nav_row_active'] = $this->template['list_pagination_nav_row_active_link'];
                $this->template['list_pagination_nav_single_jump'] = $this->template['list_pagination_nav_single_jump_link']; //Inividual number jump
                $this->template['list_pagination_nav_max_jump'] = $this->template['list_pagination_nav_max_jump_link'];//Last Page Jump
         }

         /**
         * Merge any passed in template pieces within local scope only
         *
         */
         foreach($this->template as $tplKey => $tplValue)
         {
            if(array_key_exists($tplKey, $this->items['tpl_pieces']) && ! empty($this->items['tpl_pieces'][$tplKey]))
            {
               $templates[$tplKey] = $this->items['tpl_pieces'][$tplKey];
            }
            else
            {
               $templates[$tplKey] = $tplValue;
            }
         }

         //Search
         //
/*         if(count($this->items['search']['fields']) > 0)
         {
             foreach($this->items['search']['fields'] as $form_field_name => $attributes)
             {
                 if(array_key_exists($form_field_name, $_REQUEST))
                 {
                     $form_field_value = trim(stripslashes($_REQUEST[$form_field_name]));

                     if(! empty($form_field_value))
                     {
                         $search_filter = '';

                         if(array_key_exists('callback', $attributes) && ! empty($attributes['callback']))
                         {
                             $form_field_value = call_user_func_array($attributes['callback'], array($form_field_name, $form_field_value, $search_filter, $attributes));
                         }

                         if(empty($search_filter))
                         {
                             switch($attributes['type'])
                             {

                             }
                         }
                     }
                 }
             }
         }
*/
         //Pagination
         //
         if(array_key_exists('sequence', $_REQUEST) && isset($_REQUEST['sequence']))
         {
            $this->items['sequence'] = intval($_REQUEST['sequence']);

            if($this->items['sequence'] > 1)
            {
               $this->items['rowStart'] = ((($this->items['sequence'] * $this->items['rowLimit']) - $this->items['rowLimit']));
            }
         }

         $this->items['rowMax'] = $this->items['rowStart'] + $this->items['rowLimit'] - 1;

         //Column Sort Order
         //
         if(array_key_exists('column_sort_order', $_REQUEST))
         {
             switch($_REQUEST['column_sort_order'])
             {
                 case 'asc':
                 case 'ASC':
                    $this->items['column_sort_order'] = 'ASC';
                 break;
                 case 'desc':
                 case 'DESC':
                    $this->items['column_sort_order'] = 'DESC';
                 break;
             }
         }

        if(array_key_exists('column_sort_field', $_REQUEST) && ! empty($_REQUEST['column_sort_field']))
        {
            /**
            * Get the column to sort. Could be in two different formats:
            *
            * field_in_loop => field_to_sort
            * array_index => field_to_sort
            */
            if(! empty($this->items['columns_sort']) && array_key_exists($_REQUEST['column_sort_field'], $this->items['columns_sort']))
            {
                $this->items['column_sort_field'] = $this->items['columns_sort'][$_REQUEST['column_sort_field']];
            }
            elseif(! empty($this->items['columns_sort']) && in_array($_REQUEST['column_sort_field'], $this->items['columns_sort']))
            {
                $this->items['column_sort_field'] = $_REQUEST['column_sort_field'];
            }

            if(! empty($this->items['column_sort_field']))
            {
                //sort the list - override the order by if applicable
                //
                $this->items['orderBy'] = "{$this->items['column_sort_field']} {$this->items['column_sort_order']}";
            }
        }

         /**
         * Setup query
         *
         * Must end up in a query format
         */
         if(! empty($this->items['table']))
         {
            $this->items['mode']  = 'table';
            $this->items['query'] = "SELECT * FROM {$this->items['table']}";
         }
         elseif(! empty($this->items['sql']))
         {
            $this->items['mode']  = 'sql';
            $this->items['query'] = $this->items['sql'];
         }
         elseif(! empty($this->items['view']))
         {
            $this->items['mode']  = 'view';
            $this->items['query'] = "SELECT * FROM {$this->items['view']}";
         }

         //Build out provided assumed fieldlist
         //
         if(! empty($this->items['fields']))
         {
            $fieldList = implode(',' , array_keys($this->items['fields']));
         }
         else
         {
            switch($this->items['mode'])
            {
               case 'table':
               case 'view':

                  //No fields were defined so grab them all automatically
                  //
                  $getColumns = "SHOW COLUMNS FROM {$this->items[$this->items['mode']]}";

                  $columns = $this->database->get_results($getColumns, ARRAY_A);

                  foreach($columns as $key => $row)
                  {
                     $this->items['fields'][$row['Field']] = ucwords(str_replace('_', ' ', $row['Field']));
                  }

                  $fieldList = implode(',' , array_keys($this->items['fields']));

               break;

               default:
                  $fieldList = '*';
            }
         }

         //Extra columns to select but not to display
         //

         if(! empty($this->items['columns']))
         {
            $fieldList .= ',' . implode(',', $this->items['columns']);
         }

         /**
         * Extract any posted search fields
         */
         if(! empty($this->items['search']['fields']) && is_array($this->items['search']['fields']))
         {
             $filter_groups = array();

             foreach($this->items['search']['fields'] as $form_field => $attributes)
             {
                 if(array_key_exists($form_field, $_REQUEST) && ! empty($_REQUEST[$form_field]))
                 {
                     if(! empty($attributes) && is_array($attributes) && (array_key_exists('database_column', $attributes) && ! empty($attributes['database_column'])))
                     {
                         if(! array_key_exists('ignore', $attributes) || (array_key_exists('ignore', $attributes) && empty($attributes['ignore'])))
                         {
                             $assignment_operator = '=';

                             if(array_key_exists('assignment_operator', $attributes) && ! empty($attributes['assignment_operator']))
                             {
                                 $assignment_operator = $attributes['assignment_operator'];
                             }

                             $search_value = $_REQUEST[$form_field];

                             /**
                             * Callback
                             */
                             if(array_key_exists('callback', $attributes) && ! empty($attributes['callback']))
                             {
                                 list($search_key, $search_value) = call_user_func_array($attributes['callback'], array($form_field,$attributes));

                                 if(! empty($search_value))
                                 {
                                     //$this->items['filter']['AND'][$search_key] = $search_value;
                                     $this->items['filter']['AND'][$assignment_operator][$search_key] = $search_value;
                                 }
                             }
                             else
                             {
                                /**
                                * Database field
                                *
                                * @todo add operator
                                */
                                //$this->items['filter']['AND'][$attributes['database_column']] = $search_value;
                                $this->items['filter']['AND'][$assignment_operator][$attributes['database_column']] = $search_value;
                             }

                             if($this->items['append_filter_url'])
                             {
                                 $this->items['urlParameters'][$form_field] = $search_value;
                             }
                         }
                     }
                 }
             }
         }

        /**
        * Either one of these filter methods
        *
        * $this->items['filter'] = calling page configuration
        *
        * $filter = advanced search
        */

        $filter_final = self::build_filter_string($this->items['filter']);

         if(! empty($this->items['orderBy']))
         {
             if(! stristr($this->items['orderBy'], 'ORDER BY'))
             {
                 $this->items['orderBy'] = "ORDER BY {$this->items['orderBy']}";
             }
         }

         /**
         * Setup Queries
         *
         * @todo setup a callback to return rows
         */
         $getCnt  = "SELECT count(*) as total FROM ({$this->items['query']} $filter_final) b";
         $getRows = "SELECT $fieldList FROM ({$this->items['query']} $filter_final) b {$this->items['orderBy']} LIMIT {$this->items['rowStart']}, {$this->items['rowLimit']}";

         if(! empty($this->items['bind_vars']))
         {
            $getCnt  = $this->database->prepare($getCnt, $this->items['bind_vars']);
            $getRows = $this->database->prepare($getRows, $this->items['bind_vars']);
         }

         $this->sql_last_run = $getRows;

         //Execute Queries
         //californ
         $count = $this->database->get_results($getCnt, ARRAY_A);

         echo $this->database->last_error;

         $rows  = $this->database->get_results($getRows, ARRAY_A);

         echo $this->database->last_error;

         //Define total pages
         //
         $this->items['totalPages'] = ceil($count[0]['total'] / $this->items['rowLimit']);

         $this->output['pagination'] = '';

         //If there are results
         //
         if($count[0]['total'] > 0)
         {
            $dataRows  = array();
            $headers   = array();

            //Headers
            //
            foreach($this->items['fields'] as $column => $title)
            {
              $data_pieces = array();
              $data_pieces['<!--CLASS_DATA-->'] = '';
              $data_pieces['<!--CONTENT-->'] = $title;
              $data_pieces['<!--ONCLICK-->'] = '';

              $header_template = $templates['header_row'];

              if(array_key_exists($column, $this->items['headers_class']))
              {
                  $data_pieces['<!--CLASS_DATA-->'] = $this->items['headers_class'][$column];
              }

              /**
              * Sorting
              */
              if(array_key_exists($column, $this->items['columns_sort']) || stristr($this->items['orderBy'], $column))
              {
                  $sort = 'ASC';
                  $args = array();

                  if(empty($this->items['column_sort_field']) && stristr($this->items['orderBy'], $column))
                  {
                      $this->items['column_sort_field'] = $column;

                      if(stristr($this->items['orderBy'], 'asc'))
                      {
                          $this->items['column_sort_order'] = 'ASC';
                      }
                      elseif(stristr($this->items['orderBy'], 'desc'))
                      {
                          $this->items['column_sort_order'] = 'DESC';
                      }
                  }

                  /**
                  * Get current columns raw sort field if applicable: raw => alias
                  */
                  $sort_column = $this->items['columns_sort'][$column];

                  /**
                  * Current column is actively sorting the list
                  *
                  * Draw an up or down arrow
                  */
                  if(! empty($this->items['column_sort_field']) && $this->items['column_sort_field'] == $sort_column)
                  {
                      switch($this->items['column_sort_order'])
                      {
                          case 'ASC':
                            $data_pieces['<!--CONTENT-->'] .= "&nbsp;&darr;"; //down arrow
                            $sort = 'DESC';
                          break;
                          case 'DESC':
                            $data_pieces['<!--CONTENT-->'] .= "&nbsp;&uarr;"; //up arrow
                          break;
                      }

                      $this->items['column_next_sort_order'] = $sort;
                  }

                  $args['column_sort_order'] = $sort;
                  $args['column_sort_field'] = $column;

                  $url_sort = $this->BuildUrl('', $args);

                  $data_pieces['<!--ONCLICK-->'] = "{$this->items['list_jump_function']}('{$this->items['id']}', '{$this->items['list_jump_action']}', '$url_sort');";

                  $header_template = $templates['header_row_sort'];
              }

               $headers[] = str_replace(array_keys($data_pieces), array_values($data_pieces), $header_template);
            }

            //Pagination
            //
            $this->output['pagination'] = $this->BuildPagination();

            //Data rows
            //
            $classCounter = 1;
            $urlArgs = '';

            foreach($rows as $key => $row)
            {
               $theClass = '';
               $thisRow  = array();

               foreach($this->items['fields'] as $column => $title)
               {
                  $content = stripslashes($row[$column]);

                  if(! empty($this->items['dataLength']) && strlen(strip_tags($row[$column])) > $this->items['dataLength'])
                  {
                      if(! empty($this->items['columns_str_limit']) && in_array($column, $this->items['columns_str_limit']))
                      {
                          $content = trim(substr(htmlentities($row[$column]), 0, $this->items['dataLength'])) . '...';
                      }
                  }

                  if(! empty($this->items['append_criteria']))
                  {
                      $urlArgs .= $this->items['append_criteria'];
                  }

                  $content = str_replace('<!--FILTER-->', $urlArgs . '&sequence=' . $this->items['sequence'], $content);

                  $data_pieces = array();
                  $data_pieces['<!--CLASS_DATA-->'] = '';
                  $data_pieces['<!--CONTENT-->'] = $content;

                  if(array_key_exists($column, $this->items['columns_class']))
                  {
                      $data_pieces['<!--CLASS_DATA-->'] = $this->items['columns_class'][$column];
                  }

                  $thisRow[] = str_replace(array_keys($data_pieces), array_values($data_pieces), $templates['list_data']);
               }

               if($classCounter%2)
               {
                  $theClass = $this->items['altRow'];
               }

               /**
               * reserved gobble list key word
               */
               if(array_key_exists('set_row_class', $row) && ! empty($row['set_row_class']))
               {
                   $theClass = $row['set_row_class'];
               }

               $dataRows[] = str_replace(array('<!--CLASS-->','<!--CONTENT-->'), array($theClass,implode('', $thisRow)), $templates['list_row']);

               $classCounter++;
            }

            $pieces = array('<!--HEADERS-->'           => implode('', $headers),
                            '<!--PAGINATION-->'        => $this->output['pagination'],
                            '<!--DATA-->'              => implode('', $dataRows),
                            '<!--CLASS-->'             => $this->items['class'],
                            '<!--TABLE_CLASS-->'       => $this->items['table_class'],
                            '<!--ID-->'                => $this->items['id'],
                            '<!--TITLE-->'             => $this->items['title'],
                            '<!--PERSISTENT_ARGS-->'   => $this->BuildUrl(),
                            '<!--SEARCH_FORM-->'       => $this->BuildSearchForm(),
                            '<!--BOTTOM_PAGINATION-->' => $this->items['bottomNav']
                            );
         }
         else
         {
            $pieces = array('<!--HEADERS-->'           => '',
                            '<!--PAGINATION-->'        => $this->output['pagination'],
                            '<!--DATA-->'              => str_replace('<!--CONTENT-->', $this->items['noDataMsg'], $templates['list_data_row']),
                            '<!--CLASS-->'             => $this->items['class'],
                            '<!--TABLE_CLASS-->'       => $this->items['table_class'],
                            '<!--ID-->'                => $this->items['id'],
                            '<!--TITLE-->'             => $this->items['title'],
                            '<!--PERSISTENT_ARGS-->'   => $this->BuildUrl(),
                            '<!--SEARCH_FORM-->'       => $this->BuildSearchForm(),
                            '<!--BOTTOM_PAGINATION-->' => $this->items['bottomNav']
                            );
         }

         return str_replace(array_keys($pieces), array_values($pieces), $templates['list_wrapper']);
      }

        /**
        * build_filter_string
        *
        * @param mixed $filter_array
        * @return string
        */
        public static function build_filter_string($filter_array = array())
        {
            global $wpdb;

            $filter_final = '';

            if(! empty($filter_array))
            {
                $filter_final = array();

                foreach($filter_array as $group_operator => $filter_operators)
                {
                    $filter = array();

                    foreach($filter_operators as $operator => $filter_key_val)
                    {
                        if(! empty($operator) && ! empty($filter_key_val) && is_array($filter_key_val))
                        {
                            foreach($filter_key_val as $filter_key => $filter_value)
                            {
                                $bind_value = '';
                                $bind_like = '';

                                if(! is_null($filter_value))
                                {
                                    $bind_value = $filter_value;
                                    $wpdb->escape_by_ref($bind_value);

                                    /**
                                    * Assignment operator
                                    */
                                    switch($operator)
                                    {
                                        case 'like':
                                        case 'LIKE':
                                            $bind_like = '%';
                                        break;
                                    }

                                    $bind_value = "'{$bind_value}{$bind_like}'";
                                }

                                $filter[] = "{$filter_key} $operator {$bind_value}";
                            }
                        }
                    }

                    if(! empty($filter))
                    {
                        $filter_final[] = "(" . implode(" $group_operator ", $filter) . ")";
                    }
                }

                if(! empty($filter_final))
                {
                    $filter_final = ' AND ' . implode(" AND ", $filter_final);
                }
            }

            return $filter_final;
        }

        /**
        * @todo $this->items['filter']
        *
        *
        */
        Public function BuildUrl($sequence = '', $args = array(), &$preserved = array())
        {
            /**
            * What the hell is going on here
            */
            if(! empty($this->items['column_sort_field']) && ! array_key_exists('column_sort_field', (array) $args))
            {
                $preserved['column_sort_field'] = $this->items['column_sort_field'];
                $preserved['column_sort_order'] = $this->items['column_sort_order'];

                $urlArgs[] = "column_sort_field=" . urlencode($this->items['column_sort_field']);
                $urlArgs[] = "column_sort_order=" . urlencode($this->items['column_sort_order']);
            }

            if(! empty($args) && is_array($args))
            {
                foreach($args as $name => $value)
                {
                    $preserved[$name] = $value;
                    $urlArgs[] = "$name=" . urlencode($value);
                }
            }

            foreach($this->items['urlParameters'] as $name => $value)
            {
                $preserved[$name] = $value;
                $urlArgs[] = "$name=" . urlencode($value);
            }

            if(empty($sequence))
            {
                $sequence = $this->items['sequence'];
            }

            $preserved['sequence'] = $sequence;
            $urlArgs[] = "sequence={$sequence}";

            $urlArgs = implode('&', $urlArgs);

            return $urlArgs;
        }

        public function BuildSearchForm()
        {
            return "search form";
        }

      /**
      * BuildPagination()
      *
      * Builds out the native pagination
      *
      * @todo sequence should be dynamic
      * @todo text Previous and Next should be configurable
      * @todo need a BuildUrl function
      * @todo make template pieces for pagination
      */
      public function BuildPagination()
      {
         $pageRange = 3;
         $pageNext  = 1;
         $pagePrev  = 1;

         $prevUrl   = '';
         $nextUrl   = '';
         $prevArgs  = '';
         $nextArgs  = '';

         $urlArgs   = array();

         $showPrev  = false;
         $prevBtn   = $this->template['list_pagination_nav_row_passive'];
         $prevTxt   = 'Previous';

         $showNext  = false;
         $nextBtn   = $this->template['list_pagination_nav_row_passive'];
         $nextTxt   = 'Next';

         //Active/Clickable Next Button
         //
         if($this->items['sequence'] < $this->items['totalPages'])
         {
            $nextUrl = $this->items['target'] . "&" . $this->BuildUrl($this->items['sequence']+1);
            $nextArgs = $this->BuildUrl($this->items['sequence']+1);
            $nextBtn = $this->template['list_pagination_nav_row_active'];
         }

         //Active/Clickable Previous Button
         //
         if($this->items['sequence'] > 1)
         {
            $prevUrl = $this->items['target'] . "&" . $this->BuildUrl($this->items['sequence']-1);
            $prevArgs = $this->BuildUrl($this->items['sequence']-1);
            $prevBtn = $this->template['list_pagination_nav_row_active'];
         }

         if(! empty($nextBtn))
         {
             $pieces = array();
             $pieces['<!--URL-->'] = $nextUrl;
             $pieces['<!--TEXT-->'] = $nextTxt;
             $pieces['<!--ONCLICK-->'] = "{$this->items['list_jump_function']}('{$this->items['id']}', '{$this->items['list_jump_action']}', '$nextArgs');";

             $nextBtn = str_replace(array_keys($pieces), array_values($pieces), $nextBtn);
         }

         if(! empty($prevBtn))
         {
             $pieces = array();
             $pieces['<!--URL-->'] = $prevUrl;
             $pieces['<!--TEXT-->'] = $prevTxt;
             $pieces['<!--ONCLICK-->'] = "{$this->items['list_jump_function']}('{$this->items['id']}', '{$this->items['list_jump_action']}', '$prevArgs');";

             $prevBtn = str_replace(array_keys($pieces), array_values($pieces), $prevBtn);
         }

         if($this->items['totalPages'] < $pageRange)
         {
            $pageRange = $this->items['totalPages'];
         }

         $paginationOutput[] = '<div><ul class="pagination">';
         $paginationOutput[] = $prevBtn;

         if($this->items['sequence'] > $pageRange)
         {
             $jumpOneArgs = $this->BuildUrl(1);
            $pieces = array();
            $pieces['<!--URL-->'] = $this->items['target'] . "&$jumpOneArgs";
            $pieces['<!--ONCLICK-->'] = "{$this->items['list_jump_function']}('{$this->items['id']}', '{$this->items['list_jump_action']}', '$jumpOneArgs');";

            $paginationOutput[] = str_replace(array_keys($pieces), array_values($pieces), $this->template['list_pagination_nav_first_jump']);
         }

         //Create a range of $pageRange or less numbers--Take $pageRange-1 off and add $pageRange-1 or as much as you can either way
         //
         $startingPoint = $this->items['sequence'];
         $kill = ($pageRange-1);

         while($kill > 0)
         {
            $kill--;

            if($startingPoint <= 1)
            {
               break;
            }
            else
            {
               $startingPoint--;
            }
         }

         $endPoint = $this->items['sequence'];
         $kill = 2;

         while($kill > 0)
         {
            $kill--;

            if($endPoint < $this->items['totalPages'])
            {
               $endPoint++;
            }
            else
            {
               break;
            }
         }

         for($page=$startingPoint; $page<=$endPoint; $page++)
         {
            if($page == $this->items['sequence'])
            {
               $paginationOutput[] = '<li><span class="pagination-active">' . $page . '</span></li>';
            }
            else
            {
                $jumpLastArgs = $this->BuildUrl($page);
                $pieces = array();
                $pieces['<!--URL-->'] = $this->items['target'] . "&$jumpLastArgs";
                $pieces['<!--PAGE-->'] = $page;
                $pieces['<!--ONCLICK-->'] = "{$this->items['list_jump_function']}('{$this->items['id']}', '{$this->items['list_jump_action']}', '$jumpLastArgs');";

                $paginationOutput[] = str_replace(array_keys($pieces), array_values($pieces), $this->template['list_pagination_nav_single_jump']);
            }
         }

         if(! in_array($this->items['totalPages'], array(($this->items['sequence']+1),($this->items['sequence']+2))) && $this->items['sequence'] != $this->items['totalPages'])
         {
             $jumpTotalArgs = $this->BuildUrl($this->items['totalPages']);
             $pieces = array();
             $pieces['<!--URL-->'] = $this->items['target'] . "&$jumpTotalArgs";
             $pieces['<!--TOTAL_PAGES-->'] = $this->items['totalPages'];
             $pieces['<!--ONCLICK-->'] = "{$this->items['list_jump_function']}('{$this->items['id']}', '{$this->items['list_jump_action']}', '$jumpTotalArgs');";

             $paginationOutput[] = str_replace(array_keys($pieces), array_values($pieces), $this->template['list_pagination_nav_max_jump']);
         }

         $paginationOutput[] = $nextBtn;
         $paginationOutput[] = '</ul></div>';

         if($this->items['totalPages'] <= 1)
         {
            //$paginationOutput = array();
         }

         return implode('', $paginationOutput);
      }

        public function build_search_form()
        {
            if(count($this->items['search']['fields']) > 0)
            {
                foreach($this->items['search']['fields'] as $form_field_name => $attributes)
                {
                    switch($attributes['type'])
                    {

                    }
                }
            }
        }

        public function init_admin_head()
        {
            wp_register_style('style.css', plugin_dir_url( __FILE__ ) . 'css/style.css');
            wp_enqueue_style('style.css');

            wp_register_script('ready.js', plugin_dir_url( __FILE__ ) . 'js/ready.js');
            wp_enqueue_script('ready.js');

            wp_register_script('list.js', plugin_dir_url( __FILE__ ) . 'js/list.js');
            wp_enqueue_script('list.js');

            if(! empty($this->items['search']['id_field']) && ! empty($this->items['search']['fields']) && is_array($this->items['search']['fields']))
            {
                echo $this->items['search']['template_advanced'];

                echo $this->build_search_form();

                echo "
                jQuery(document).ready(
                function()
                {
                    var list_options =
                    {
                        id_search_template: '{$this->items['search']['id_template_advanced']}'
                    }

                    jQuery('#{$this->items['search_id']}').gobble_list_search(list_options);
                });
                ";

                wp_register_script('gobble-search.js', plugin_dir_url( __FILE__ ) . 'js/gobble-search.js');
                wp_enqueue_script('gobble-search.js');
            }
        }
   }
}

if(! isset($gobbleList))
{
   $gobbleList = new GobbleList;
}

?>