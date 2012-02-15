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

         if(! empty($wpdb))
         {
            $this->database = &$wpdb;
         }

         $this->template = $widgetTemplate;
         $this->output   = array();

         //All optional parameters
         //
         $this->items = array();
         $this->items['sql']            = '';
         $this->items['view']           = '';
         $this->items['name']           = '';
         $this->items['id']             = '';
         $this->items['title']          = '';
         $this->items['dataLength']     = 25;
         $this->items['noDataMsg']      = 'Currently no data exists.';
         $this->items['target']         = 'admin.php?';
         $this->items['pagerClass']     = 'pagination';
         $this->items['class']          = 'listContainer';
         $this->items['colSortOrder']   = 'ASC';
         $this->items['orderBy']        = '';
         $this->items['rowStart']       = 0;
         $this->items['rowLimit']       = 10;
         $this->items['rowMax']         = 0;
         $this->items['sequence']       = 1;
         $this->items['totalPages']     = 0;
         $this->items['filter']         = array();
         $this->items['fields']         = array();
         $this->items['columns']        = array();
         $this->items['urlParameters']  = array();
         $this->items['tpl_pieces']     = array();
         $this->items['bottomNav']      = false;
         $this->items['altRow']         = 'alternate';
         $this->items['use_check_all']  = false;
      }

      public function BuildList($list = array())
      {
         $fieldList = '*';
         $theList   = '';

         if(is_array($list) && ! empty($list))
         {
            $this->items = array_merge($this->items,$list);
         }

         //Merge/Replace any passed in template pieces
         //
         if(! empty($this->items['tpl_pieces']))
         {
            $this->template = array_merge($this->template,$this->items['tpl_pieces']);
         }

         //Pagination
         //
         if(array_key_exists('sequence', $_GET) && isset($_GET['sequence']))
         {
            $this->items['sequence'] = intval($_GET['sequence']);

            if($this->items['sequence'] > 1)
            {
               $this->items['rowStart'] = ((($this->items['sequence'] * $this->items['rowLimit']) - $this->items['rowLimit']));
            }
         }

         $this->items['rowMax'] = $this->items['rowStart'] + $this->items['rowLimit'] - 1;

         //Column Sort Order
         //
         if(array_key_exists('col_sort_order', $_GET) && $_GET['col_sort_order'] != 'ASC')
         {
            $this->items['colSortOrder'] = 'DESC';
         }
         else
         {
            $this->items['colSortOrder'] = 'ASC';
         }

         //Assumed view
         //
         if(! empty($this->items['table']))
         {
            $this->items['view'] = $this->items['table'];
         }

         //Build out provided assumed fieldlist
         //
         if(! empty($this->items['fields']))
         {
            $fieldList = implode(',' , array_keys($this->items['fields']));
         }
         else
         {
            //No fields were defined so grab them all automatically
            //
            $getColumns = "SHOW COLUMNS FROM {$this->items['view']}";

            $columns = $this->database->get_results($getColumns, ARRAY_A);

            foreach($columns as $key => $row)
            {
               $this->items['fields'][$row['Field']] = ucwords(str_replace('_', ' ', $row['Field']));
            }

            $fieldList = implode(',' , array_keys($this->items['fields']));
         }

         //Extra columns to select but not to display
         //
         if(! empty($this->items['columns']))
         {
            $fieldList .= ',' . implode(',', $this->items['columns']);
         }

         $filter = '';

         if(! empty($this->items['filter']))
         {
            $filter = array();

            foreach($this->items['filter'] as $key => $value)
            {
               $filter[]   = "$key = %s";
               $bindVars[] = $value;
            }

            $filter = ' WHERE ' . implode(' AND ', $filter);
         }

         //Setup Queries
         //
         $getCnt  = "SELECT count(*) as total FROM {$this->items['view']} $filter";
         $getRows = "SELECT $fieldList FROM {$this->items['view']} $filter {$this->items['orderBy']} LIMIT {$this->items['rowStart']}, {$this->items['rowLimit']}";

         $getCnt  = $this->database->prepare($getCnt, $bindVars);
         $getRows = $this->database->prepare($getRows, $bindVars);

         //Execute Queries
         //
         $count = $this->database->get_results($getCnt, ARRAY_A);
         $rows  = $this->database->get_results($getRows, ARRAY_A);

         //Define total pages
         //
         $this->items['totalPages'] = ceil($count[0]['total'] / $this->items['rowLimit']);

         //If there are results
         //
         if($count[0]['total'] > 0)
         {
            $dataRows  = array();
            $headers   = array();

            //Pagination
            //
            $this->output['pagination'] = $this->BuildPagination();

            //Headers
            //
            foreach($this->items['fields'] as $column => $title)
            {
               $headers[] = str_replace('<!--CONTENT-->', $title, $this->template['header_row']);
            }

            //Data rows
            //
            $classCounter = 1;

            foreach($rows as $key => $row)
            {
               $theClass = '';
               $thisRow  = array();

               foreach($this->items['fields'] as $column => $title)
               {
                  $content = $row[$column];

                  if(strlen(strip_tags($row[$column])) > $this->items['dataLength'])
                  {
                     $content = trim(substr(htmlentities($row[$column]), 0, $this->items['dataLength'])) . '...';
                  }

                  $content = str_replace('<!--FILTER-->', $urlArgs . '&sequence=' . $this->items['sequence'], $content);

                  $thisRow[] = str_replace('<!--CONTENT-->', $content, $this->template['list_data']);
               }

               if($classCounter%2)
               {
                  $theClass = $this->items['altRow'];
               }

               $dataRows[] = str_replace(array('<!--CLASS-->','<!--CONTENT-->'), array($theClass,implode('', $thisRow)), $this->template['list_row']);

               $classCounter++;
            }

            $pieces = array('<!--HEADERS-->'           => implode('', $headers),
                            '<!--PAGINATION-->'        => $this->output['pagination'],
                            '<!--DATA-->'              => implode('', $dataRows),
                            '<!--CLASS-->'             => $this->items['class'],
                            '<!--ID-->'                => $this->items['id'],
                            '<!--TITLE-->'             => $this->items['title'],
                            '<!--BOTTOM_PAGINATION-->' => $this->items['bottomNav']
                            );
         }
         else
         {
            $pieces = array('<!--HEADERS-->'           => '',
                            '<!--PAGINATION-->'        => $this->output['pagination'],
                            '<!--DATA-->'              => str_replace('<!--CONTENT-->', $this->items['noDataMsg'], $this->template['list_data_row']),
                            '<!--CLASS-->'             => $this->items['class'],
                            '<!--ID-->'                => $this->items['id'],
                            '<!--TITLE-->'             => $this->items['title'],
                            '<!--BOTTOM_PAGINATION-->' => $this->items['bottomNav']
                            );
         }

         return str_replace(array_keys($pieces), array_values($pieces), $this->template['list_wrapper']);
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

         $urlArgs   = array();

         $showPrev  = false;
         $prevBtn   = $this->template['list_pagination_nav_row_passive'];
         $prevTxt   = 'Previous';

         $showNext  = false;
         $nextBtn   = $this->template['list_pagination_nav_row_passive'];
         $nextTxt   = 'Next';

         //Build URL Parameters
         //
         foreach($this->items['urlParameters'] as $name => $value)
         {
            $urlArgs[] = "$name=" . urlencode($value);
         }

         $urlArgs = implode('&', $urlArgs);

         //Active/Clickable Next Button
         //
         if($this->items['sequence'] < $this->items['totalPages'])
         {
            $nextUrl = $this->items['target'] . "&sequence=" .  ($this->items['sequence'] + 1) . '&' . $urlArgs;
            $nextBtn = $this->template['list_pagination_nav_row_active'];
         }

         //Active/Clickable Previous Button
         //
         if($this->items['sequence'] > 1)
         {
            $prevUrl = $this->items['target'] . "&sequence=" .  ($this->items['sequence'] - 1) . '&' . $urlArgs;
            $prevBtn = $this->template['list_pagination_nav_row_active'];
         }

         $nextBtn = str_replace(array('<!--URL-->','<!--TEXT-->'), array($nextUrl,$nextTxt), $nextBtn);

         $prevBtn = str_replace(array('<!--URL-->','<!--TEXT-->'), array($prevUrl,$prevTxt), $prevBtn);

         if($this->items['totalPages'] < $pageRange)
         {
            $pageRange = $this->items['totalPages'];
         }

         $paginationOutput[] = '<ul id="pagination">';
         $paginationOutput[] = $prevBtn;

         if($this->items['sequence'] > 3)
         {
            $pageUrl = $this->items['target'] . "&sequence=1" . '&' . $urlArgs;

            $paginationOutput[] = '<li><span><a href="'.$pageUrl.'">1</a></span></li>';
         }

         //Create a range of 5 or less numbers--Take 2 off and add 2 or as much as you can either way
         //
         $startingPoint = $this->items['sequence'];
         $kill = 2;

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
               $paginationOutput[] = '<li><span class="active">' . $page . '</span></li>';
            }
            else
            {
               $pageUrl = $this->items['target'] . "&sequence=" .  $page . '&' . $urlArgs;

               $paginationOutput[] = '<li><span><a href="'.$pageUrl.'">'.$page.'</a></span></li>';
            }
         }

         if($this->items['sequence'] != ($this->items['totalPages'] + 3))
         {
            $pageUrl = $this->items['target'] . "&sequence=" .  $this->items['totalPages'] . '&' . $urlArgs;

            $paginationOutput[] = '<li><span><a href="'.$pageUrl.'">'.$this->items['totalPages'].'</a></span></li>';
         }

         $paginationOutput[] = $nextBtn;
         $paginationOutput[] = '</ul>';

         if($this->items['totalPages'] <= 1)
         {
            $paginationOutput = array();
         }

         return implode('', $paginationOutput);
      }

      public function __destruct()
      {
      }
   }
}

if(! isset($gobbleList))
{
   $gobbleList = new GobbleList;
}
?>