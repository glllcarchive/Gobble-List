
function reset_list_filter_response(response)
{
    var args = '';

    if(response && response['args'] != '')
    {
        args = response['args'];
    }

    if(response && response['refresh_list_id'] != '' && response['refresh_list_action'] != '')
    {
        list_jump(response['refresh_list_id'], response['refresh_list_action'], args);
    }
}

function reset_list_filter(list_id, list_action)
{
    jQuery.post(ajaxurl, '&action=reset_list_filter&list_id=' + list_id + '&list_action=' + list_action, reset_list_filter_response ,'json');
}

function list_jump_response(response)
{
    ajaxStatus(response['list_id'], 0);

    if(response['list'] != '')
    {
        jQuery(document.getElementById(response['list_id'])).after(response['list']).remove();
    }

    if(response['search_form'] != '')
    {
        jQuery('.advanced-search-container').html(response['search_form']);
    }

    //jQuery('#input_search_lead_list').gobble_search();
}

function list_jump(list_id, action, args)
{
    //ajaxStatus(list_id, 1);

    jQuery.post(ajaxurl, jQuery('#' + list_id).find('#persistent_args').val() + '&action='+action+'&list_id=' + list_id + '&' + args, list_jump_response ,'json');
}

/**
* ListSearchAheadResponse()
*/
var ListSearchAheadGlobalElement = '';
var ListSearchAheadQueueElement = '';
var ListSearchAheadInProgress = false;
var ListSearchAheadInProgressUrl;
var ListSearchAheadInProgressTarget;
var ListSearchAheadInProgressObj;
var ListSearchAheadInProgressCallback;

function ListSearchAheadResponse()
{
   if(ListSearchAheadInProgress)
   {
      ListSearchAheadInProgress = false;

      if(ListSearchAheadQueueElement != '')
      {
         (ListSearchAheadQueueElement)(ListSearchAheadInProgressUrl,ListSearchAheadInProgressTarget,ListSearchAheadInProgressObj, ListSearchAheadInProgressCallback);

         ListSearchAheadQueueElement = '';
      }
   }
}

/**
* ListSearchAhead()
*/
function ListSearchAhead(url, id, element, callback)
{
   if(! ListSearchAheadInProgress && jQuery(element).length && typeof(jQuery(element).val()) != 'undefined' && jQuery(element).val() != jQuery(element).attr('title'))
   {
      ListSearchAheadInProgress = true;
      ListJumpMin(url+ '&search_filter=' + escape(uni2ent(jQuery(element).val())), id, callback);
   }
}

/**
* ListSearchAheadQueue()
*/
function ListSearchAheadQueue(url, id, element, callback)
{
   if (typeof(theForm) == "undefined" && typeof(theForm) == 'null')
   {
      callback = 'null';
   }

   if(! ListSearchAheadInProgress)
   {
      ListSearchAheadGlobalElement = element;

      setTimeout("ListSearchAhead('"+url+"', '"+id+"', ListSearchAheadGlobalElement, " + callback + ")", 500);
   }
   else
   {
      ListSearchAheadInProgressUrl = url;
      ListSearchAheadInProgressTarget = id;
      ListSearchAheadInProgressObj = element;
      ListSearchAheadInProgressCallback = callback;

      ListSearchAheadQueueElement = ListSearchAhead;
   }
}

function list_search(url, list_id, element, callback)
{
   var inputField = jQuery(element).closest('div.inputOuter').find('input.search-ahead');

   if(inputField.length)
   {
      ListSearchAheadQueue(url, listId, inputField, callback);
   }
}

function ajaxStatus(eToHide, fadeInOut)
{
   if(document.getElementById(eToHide))
   {
      elmToHide  = document.getElementById(eToHide);

      eHider = "loading" + eToHide;

      if(document.getElementById(eHider))
      {
         elmHider = document.getElementById(eHider);
      }
      else
      {
         var overLay = '<div style="position:relative;top:0px;"><div class="ajaxLoad" id="' + eHider + '" style="height:' + jQuery(elmToHide).height() + 'px;width:' + jQuery(elmToHide).width() + 'px;top:-' + jQuery(elmToHide).height() + 'px;"></div></div>';

         jQuery(elmToHide).append(overLay);

         elmHider = document.getElementById(eHider);
      }

      if(typeof(fadeInOut) == 'number')
      {
         if(fadeInOut > 0)
         {
            fadeInOut = 1;
            jQuery(elmHider).fadeTo("fast", .20);
         }
         else
         {
            fadeInOut = 0;
            jQuery(elmHider).remove();
         }
      }
   }
}

jQuery(document).ready(
function($)
{

});