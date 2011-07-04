<?php

$widgetTemplate = array();

$widgetTemplate['list_wrapper'] = '
<div id="paginationBlock">
   <div class="tablenav">
	<div class="tablenav-pages">
		<!--PAGINATION-->
		</div>
	</div>
  </div>
<div class="<!--CLASS-->" id="<!--ID-->">
   <table class="widefat">
   	<thead>
      <tr>
         <!--HEADERS-->
      </tr>
    </thead>
    <tdbody>
      <!--DATA-->
    </tdbody>
   </table>
   <div class=""><!--BOTTOM_PAGINATION--></div>
   <!--OPTION_LINK-->
</div>
';

$widgetTemplate['header_row'] = '<th class="row-title"><!--CONTENT--></th>';

$widgetTemplate['list_row'] = '<tr class="<!--CLASS-->"><!--CONTENT--></tr>';

$widgetTemplate['list_data'] = '<td><!--CONTENT--></td>';

$widgetTemplate['list_data_row'] = '<tr><td><!--CONTENT--></td></tr>';

$widgetTemplate['list_pagination_wrapper'] = '<ul id="pagination"><!--CONTENT--></ul>';

$widgetTemplate['list_pagination_row'] = '<li><!--CONTENT--></li>';

$widgetTemplate['list_pagination_nav_row_active'] = '<li><span><a href="<!--URL-->"><!--TEXT--></a></span></li>';

$widgetTemplate['list_pagination_nav_row_passive'] = '<li><span><!--TEXT--></span></li>';

?>