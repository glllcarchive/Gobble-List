<?php
/*
Plugin Name: GobbleList
Plugin URI: https://github.com/alisonmf/Gobble-List
Description: Generic Paginated List function
Version: 1
Author: AMF&MCFW
Author URI: https://github.com/alisonmf/Gobble-List
*/

require_once('tpl/tpl.php');

if(! class_exists("GobbleList"))
{
   class GobbleList
   {
      public function __construct()
      {
      }

      public static function BuildList($list = array())
      {
         global $wpdb;
         global $widgetTemplate;

         $fieldList = '*';
         $theList   = '';

         $items = array('sql'          => '',
                        'view'         => '',
                        'name'         => '',
                        'id'           => '',
                        'title'        => '',
                        'dataLength'   => 25,
                        'noDataMsg'    => 'Currently no data exists.',
                        'target'       => 'admin.php?',
                        'pagerClass'   => 'pagination',
                        'class'        => 'listContainer',
                        'colSortOrder' => 'ASC',
                        'orderBy'      => '',
                        'rowStart'     => 0,
                        'rowLimit'     => 10,
                        'rowMax'       => 0,
                        'sequence'     => 1,
                        'totalPages'   => 0,
                        'filter'       => array(),
                        'fields'       => array(),
                        'columns'      => array(),
                        'urlParameters'=> array(),
                        'bottomNav'    => false,
                        'altRow'       => 'alternate',
                        );

         if(is_array($list))
         {
            foreach($list as $key => $value)
            {
               if(array_key_exists($key, $items))
               {
                  $items[$key] = $value;
               }
            }
         }

         //Pagination
         //
         if(array_key_exists('sequence', $_GET) && isset($_GET['sequence']))
         {
            $items['sequence'] = intval($_GET['sequence']);

            if($items['sequence'] > 1)
            {
               $items['rowStart'] = ((($items['sequence'] * $items['rowLimit']) - $items['rowLimit']));
            }
         }

         $items['rowMax'] = $items['rowStart'] + $items['rowLimit'] - 1;

         //Column Sort Order
         //
         if(array_key_exists('col_sort_order', $_GET) && $_GET['col_sort_order'] != 'ASC')
         {
            $items['colSortOrder'] = 'DESC';
         }
         else
         {
            $items['colSortOrder'] = 'ASC';
         }

         //Assumed view
         //
         if(! empty($items['table']))
         {
            $items['view'] = $items['table'];
         }

         //Build out provided assumed fieldlist
         //
         if(! empty($items['fields']))
         {
            $fieldList = implode(',' , array_keys($items['fields']));
         }
         else
         {
            //No fields were defined so grab them all automatically
            //
            $getColumns = "SHOW COLUMNS FROM {$items['view']}";

            $columns = $wpdb->get_results($getColumns, ARRAY_A);

            foreach($columns as $key => $row)
            {
               $items['fields'][$row['Field']] = ucwords(str_replace('_', ' ', $row['Field']));
            }

            $fieldList = implode(',' , array_keys($items['fields']));
         }

         //Extra columns to select but not to display
         //
         if(! empty($items['columns']))
         {
            $fieldList .= ',' . implode(',', $items['columns']);
         }

         $filter = '';

         if(! empty($items['filter']))
         {
            $filter = array();

            foreach($items['filter'] as $key => $value)
            {
               $filter[]   = "$key = %s";
               $bindVars[] = $value;
            }

            $filter = ' WHERE ' . implode(' AND ', $filter);
         }

         //Setup Queries
         //
         $getCnt  = "SELECT count(*) as total FROM {$items['view']} $filter";
         $getRows = "SELECT $fieldList FROM {$items['view']} $filter {$items['orderBy']} LIMIT {$items['rowStart']}, {$items['rowLimit']}";

         $getCnt  = $wpdb->prepare($getCnt, $bindVars);
         $getRows = $wpdb->prepare($getRows, $bindVars);

         //Execute Queries
         //
         $count = $wpdb->get_results($getCnt, ARRAY_A);
         $rows  = $wpdb->get_results($getRows, ARRAY_A);

         //Define total pages
         //
         $items['totalPages'] = ceil($count[0]['total'] / $items['rowLimit']);

         //If there are results
         //
         if($count[0]['total'] > 0)
         {
            $dataRows  = array();
            $headers   = array();

            $pageRange = 3;
            $pageNext  = 1;
            $pagePrev  = 1;

            $prevUrl   = '';
            $nextUrl   = '';

            $urlArgs   = array();

            $showPrev  = false;
            $prevBtn   = $widgetTemplate['list_pagination_nav_row_passive'];
            $prevTxt   = 'Previous';

            $showNext  = false;
            $nextBtn   = $widgetTemplate['list_pagination_nav_row_passive'];
            $nextTxt   = 'Next';

            //Build URL Parameters
            //
            foreach($items['urlParameters'] as $name => $value)
            {
               $urlArgs[] = "$name=" . urlencode($value);
            }

            $urlArgs = implode('&', $urlArgs);

            //Active/Clickable Next Button
            //
            if($items['sequence'] < $items['totalPages'])
            {
               $nextUrl = $items['target'] . "&sequence=" .  ($items['sequence'] + 1) . '&' . $urlArgs;
               $nextBtn = $widgetTemplate['list_pagination_nav_row_active'];
            }

            //Active/Clickable Previous Button
            //
            if($items['sequence'] > 1)
            {
               $prevUrl = $items['target'] . "&sequence=" .  ($items['sequence'] - 1) . '&' . $urlArgs;
               $prevBtn = $widgetTemplate['list_pagination_nav_row_active'];
            }

            $nextBtn = str_replace(array('<!--URL-->','<!--TEXT-->'), array($nextUrl,$nextTxt), $nextBtn);

            $prevBtn = str_replace(array('<!--URL-->','<!--TEXT-->'), array($nextUrl,$prevTxt), $prevBtn);

            if($items['totalPages'] < $pageRange)
            {
               $pageRange = $items['totalPages'];
            }

            $paginationOutput[] = '<ul id="pagination">';
            $paginationOutput[] = $prevBtn;

            if($items['sequence'] > 3)
            {
               $pageUrl = $items['target'] . "&sequence=1" . '&' . $urlArgs;

               $paginationOutput[] = '<li><span><a href="'.$pageUrl.'">1</a></span></li>';
            }

            //Create a range of 5 or less numbers--Take 2 off and add 2 or as much as you can either way
            //
            $startingPoint = $items['sequence'];
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

            $endPoint = $items['sequence'];
            $kill = 2;

            while($kill > 0)
            {
               $kill--;

               if($endPoint < $items['totalPages'])
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
               if($page == $items['sequence'])
               {
                  $paginationOutput[] = '<li><span class="active">' . $page . '</span></li>';
               }
               else
               {
                  $pageUrl = $items['target'] . "&sequence=" .  $page . '&' . $urlArgs;

                  $paginationOutput[] = '<li><span><a href="'.$pageUrl.'">'.$page.'</a></span></li>';
               }
            }

            if($items['sequence'] != ($items['totalPages'] + 3))
            {
               $pageUrl = $items['target'] . "&sequence=" .  $items['totalPages'] . '&' . $urlArgs;

               $paginationOutput[] = '<li><span><a href="'.$pageUrl.'">'.$items['totalPages'].'</a></span></li>';
            }

            $paginationOutput[] = $nextBtn;
            $paginationOutput[] = '</ul>';

            if($items['totalPages'] <= 1)
            {
               $paginationOutput = array();
            }

            $paginationOutput = implode('', $paginationOutput);

            //Headers
            //
            foreach($items['fields'] as $column => $title)
            {
               $headers[] = str_replace('<!--CONTENT-->', $title, $widgetTemplate['header_row']);
            }

            //Data rows
            //
            $classCounter = 1;

            foreach($rows as $key => $row)
            {
               $theClass = '';
               $thisRow  = array();

               foreach($items['fields'] as $column => $title)
               {
                  $content = $row[$column];

                  if(strlen(strip_tags($row[$column])) > $items['dataLength'])
                  {
                     $content = trim(substr(htmlentities($row[$column]), 0, $items['dataLength'])) . '...';
                  }

                  $content = str_replace('<!--FILTER-->', $urlArgs . '&sequence=' . $items['sequence'], $content);

                  $thisRow[] = str_replace('<!--CONTENT-->', $content, $widgetTemplate['list_data']);
               }

               if($classCounter%2)
               {
                  $theClass = $items['altRow'];
               }

               $dataRows[] = str_replace(array('<!--CLASS-->','<!--CONTENT-->'), array($theClass,implode('', $thisRow)), $widgetTemplate['list_row']);

               $classCounter++;
            }

            $pieces = array('<!--HEADERS-->'           => implode('', $headers),
                            '<!--PAGINATION-->'        => $paginationOutput,
                            '<!--DATA-->'              => implode('', $dataRows),
                            '<!--CLASS-->'             => $items['class'],
                            '<!--ID-->'                => $items['id'],
                            '<!--TITLE-->'             => $items['title'],
                            '<!--BOTTOM_PAGINATION-->' => $items['bottomNav']
                            );
         }
         else
         {
            $pieces = array('<!--HEADERS-->'           => '',
                            '<!--PAGINATION-->'        => $paginationOutput,
                            '<!--DATA-->'              => str_replace('<!--CONTENT-->', $items['noDataMsg'], $widgetTemplate['list_data_row']),
                            '<!--CLASS-->'             => $items['class'],
                            '<!--ID-->'                => $items['id'],
                            '<!--TITLE-->'             => $items['title'],
                            '<!--BOTTOM_PAGINATION-->' => $items['bottomNav']
                            );
         }

         $template = $widgetTemplate['list_wrapper'];

         foreach($pieces as $tag => $content)
         {
            $template = str_replace($tag, $content, $template);
         }

         return $template;
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