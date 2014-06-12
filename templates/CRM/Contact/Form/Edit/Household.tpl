{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 | Customization EE-atWork                                            |
 | Author       :   Erik Hommel (hommel@ee-atwork.nl)                 |
 | Project      :   Implementatie CiviCRM                             |
 | Customer     :   De Goede Woning Apeldoorn                         |
 | Date         :   12 Jan 2011 (v3.3.0)                              |
 | Marker       :   CoreCorp4                                         |
 | Description  :   Remove nick name for household                    |
 +--------------------------------------------------------------------+
*}
{* tpl for building Household related fields *}
<script type="text/javascript">
{literal}
cj(function($) {
{/literal}
  var cid=parseFloat("{$contactId}");//parseInt is octal by default
  var contactHousehold = "{crmURL p='civicrm/ajax/rest' q='entity=Contact&action=get&json=1&contact_type=Household&return=household_name,email&rowCount=50' h=0}";
  var viewIndividual = "{crmURL p='civicrm/contact/view' q='reset=1&cid=' h=0}";
  var editIndividual = "{crmURL p='civicrm/contact/add' q='reset=1&action=update&cid=' h=0}";
  var lastnameMsg;
{literal}
  $(document).ready(function() {
    if (cj('#contact_sub_type *').length == 0) {//if they aren't any subtype we don't offer the option
      cj('#contact_sub_type').parent().hide();
    }
    cj('#household_name').blur(function () {
      // Close msg if it exists
      lastnameMsg && lastnameMsg.close && lastnameMsg.close();
      if (this.value == '') return;
      cj.getJSON(contactHousehold,{household_name:cj('#household_name').val()},
        function(data){
          if (data.is_error == 1 || data.count == 0) {
            return;
          }
          var msg = "<em>{/literal}{ts escape='js'}If the household you were trying to add is listed below, click their name to view or edit their record{/ts}{literal}:</em>";
          if ( data.count == 1 ) {
            var title = "{/literal}{ts escape='js'}Similar Contact Found{/ts}{literal}";
          } else {
            var title = "{/literal}{ts escape='js'}Similar Contacts Found{/ts}{literal}";
          }
          msg += '<ul class="matching-contacts-actions">';
          cj.each(data.values, function(i,contact){
            if ( !(contact.email) ) {
              contact.email = '';
            }
          msg += '<li><a href="'+viewIndividual+contact.id+'">'+ contact.household_name +'</a> '+contact.email+'</li>';
        });
        msg += '</ul>';
        lastnameMsg = CRM.alert(msg, title);
        cj('.matching-contacts-actions a').click(function(){
          // No confirmation dialog on click
          global_formNavigate = true;
          return true;
        });
      });
    });
  });
});
</script>
{/literal}
<table class="form-layout-compressed">
    <tr>
       <td>{$form.household_name.label}<br/>
         {$form.household_name.html}
       </td>

	   {* Customization CoreCorp 4 remove nick name *}
       {* <td>{$form.nick_name.label}<br/>
       {* {$form.nick_name.html}</td>
       {* end CoreCorp4 *}

       <td>{if $action == 1 and $contactSubType}&nbsp;{else}
              {$form.contact_sub_type.label}<br />
              {$form.contact_sub_type.html}
           {/if}
       </td>
     </tr>
</table>
