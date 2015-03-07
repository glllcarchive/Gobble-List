<?php

$widgetTemplate = array();

$widgetTemplate['list_wrapper'] = '
<div class="<!--CLASS-->" id="<!--ID-->">
    <div id="paginationBlock">
        <!--PAGINATION-->
    </div>

   <table class="<!--TABLE_CLASS-->">
   	<thead>
      <tr class="gl-list-headers">
         <!--HEADERS-->
      </tr>
    </thead>
    <tdbody>
      <!--DATA-->
    </tdbody>
   </table>
   <div class=""><!--BOTTOM_PAGINATION--></div>
   <input type="hidden" name="persistent_args" id="persistent_args" value="<!--PERSISTENT_ARGS-->">
</div>
';

$widgetTemplate['header_row'] = '<th class="row-title <!--CLASS_DATA-->"><!--CONTENT--></th>';

$widgetTemplate['header_row_sort'] = '<th class="row-title row-title-sort <!--CLASS_DATA-->" onclick="<!--ONCLICK--> return false;"><!--CONTENT--></th>';

$widgetTemplate['list_row'] = '<tr class="<!--CLASS-->"><!--CONTENT--></tr>';

$widgetTemplate['list_data'] = '<td class="<!--CLASS_DATA-->"><!--CONTENT--></td>';

$widgetTemplate['list_data_row'] = '<tr><td><!--CONTENT--></td></tr>';

$widgetTemplate['list_pagination_wrapper'] = '<ul class="pagination"><!--CONTENT--></ul>';

$widgetTemplate['list_pagination_row'] = '<li><!--CONTENT--></li>';

$widgetTemplate['list_pagination_nav_row_active_ajax'] = '<li><span><a onclick="<!--ONCLICK--> return false;"><!--TEXT--></a></span></li>';
$widgetTemplate['list_pagination_nav_row_active_link'] = '<li><span><a href="<!--URL-->"><!--TEXT--></a></span></li>';

$widgetTemplate['list_pagination_nav_row_passive'] = '<li><span><!--TEXT--></span></li>';

$widgetTemplate['list_pagination_nav_first_jump_ajax'] = '<li><span><a href="" onclick="<!--ONCLICK--> return false;">1</a></span></li>';
$widgetTemplate['list_pagination_nav_first_jump_link'] = '<li><span><a href="<!--URL-->">1</a></span></li>';

$widgetTemplate['list_pagination_nav_single_jump_ajax'] = '<li><span><a onclick="<!--ONCLICK--> return false;"><!--PAGE--></a></span></li>';
$widgetTemplate['list_pagination_nav_single_jump_link'] = '<li><span><a href="<!--URL-->"><!--PAGE--></a></span></li>';

$widgetTemplate['list_pagination_nav_max_jump_ajax'] = '<li><span><a onclick="<!--ONCLICK--> return false;"><!--PAGE--></a></span></li>';
$widgetTemplate['list_pagination_nav_max_jump_link'] = '<li><span><a href="<!--URL-->"><!--PAGE--></a></span></li>';

$widgetTemplate['list_pagination_nav_max_jump_ajax'] = '<li><span><a onclick="<!--ONCLICK--> return false;"><!--TOTAL_PAGES--></a></span></li>';
$widgetTemplate['list_pagination_nav_max_jump_link'] = '<li><span><a href="<!--URL-->"><!--TOTAL_PAGES--></a></span></li>';
?>