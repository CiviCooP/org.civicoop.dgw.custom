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
 | Project      :   Issues CiviCRM (incident 08 07 11 002)            |
 | Customer     :   De Goede Woning Apeldoorn                         |
 | Date         :   26 Mar 2012                                       |
 | Marker       :   DGW26                                             |
 | Description  :   Add client Address to report template             |
 |                                                                    |
 | Incident BOSB1401851 (CiviCooP)                                    |
 | Author       :   Erik Hommel (erik.hommel@civicoop.org)            |
 | Date         :   12 Feb 2014                                       |
 |                                                                    |
 | Incident BOS14071072 (CiviCooP)                                    |
 | Date         :   09 Sept 2014                                      |
 | Author       :   Jan-Derek Vos (j.vos@bosqom.nl)                   |
 | Marker       :   DGW19                                             |
 | Description  :   Gevoelige informatie act. type alleen beschikbaar |
 |                  voor leden groep 18 (Consulenten Wijk & Buurt)    |
 +--------------------------------------------------------------------+
*}
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
<head>
  <title>{$pageTitle}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <base href="{crmURL p="" a=true}" /><!--[if IE]></base><![endif]-->
  <style type="text/css" media="screen, print">@import url({$config->userFrameworkResourceURL}css/print.css);</style>
</head>

<body>
<div id="crm-container" class="crm-container">
<h1>{$pageTitle}</h1>
<div id="report-date">{$reportDate}</div>
<h2>{ts}Case Summary{/ts}</h2>
<table class="report-layout">
    <tr>
      <th class="reports-header">{ts}Client{/ts}</th>
      <th class="reports-header">{ts}Case Type{/ts}</th>
         <th class="reports-header">{ts}Status{/ts}</th>
        <th class="reports-header">{ts}Start Date{/ts}</th>
      <th class="reports-header">{ts}Case ID{/ts}</th>
    </tr>
    <tr>
        <td class="crm-case-report-clientName">{$case.clientName}</td>
        <td class="crm-case-report-caseType">{$case.caseType}</td>
        <td class="crm-case-report-status">{$case.status}</td>
        <td class="crm-case-report-start_date">{$case.start_date}</td>
        <td class="crm-case-report-{$caseId}">{$caseId}</td>
    </tr>
    
    {* DGW26 Add client Address to report *}
	{* retrieve client address with API *}
	{crmAPI var="naw" entity="Contact" action="getsingle" sequential="1" contact_id=$clientID}
        <tr>
            <th class="reports-header">Straat</th>
            <th class="reports-header">Plaats</th>
            <th class="reports-header" colspan="3">Telefoon(s) en email(s)</th>
        </tr>
        <tr>
            <td class="crm-case-report-clientName">{$naw.street_address}</td>
            <td class="crm-case-report-clientName">{$naw.postal_code}&nbsp;{$naw.city}</td>
            {crmAPI var="phones" entity="Phone" action="get" sequential="1" contact_id=$clientID}
            {crmAPI var="emails" entity="Email" action="get" sequential="1" contact_id=$clientID}
            <td class="crm-case-report-clientName" colspan="3">
                {foreach from=$phones.values item=clientPhone}
                    {$clientPhone.phone}<br />
                {/foreach}
                {foreach from=$emails.values item=clientEmail}
                    {$clientEmail.email}<br />
                {/foreach}
            </td>
        </tr>
        {* end DGW26 *}
        
</table>
<h2>{ts}Case Roles{/ts}</h2>
<table class ="report-layout">
    <tr>
      <th class="reports-header">{ts}Case Role{/ts}</th>
      <th class="reports-header">{ts}Name{/ts}</th>
         <th class="reports-header">{ts}Phone{/ts}</th>
        <th class="reports-header">{ts}Email{/ts}</th>
    </tr>

    {foreach from=$caseRelationships item=row key=relId}
       <tr>
          <td class="crm-case-report-caserelationships-relation">{$row.relation}</td>
          <td class="crm-case-report-caserelationships-name">{$row.name}</td>
          <td class="crm-case-report-caserelationships-phone">{$row.phone}</td>
          <td class="crm-case-report-caserelationships-email">{$row.email}</td>
       </tr>
    {/foreach}
    {foreach from=$caseRoles item=relName key=relTypeID}
         {if $relTypeID neq 'client'}
           <tr>
               <td>{$relName}</td>
               <td>{ts}(not assigned){/ts}</td>
               <td></td>
               <td></td>
           </tr>
         {else}
           <tr>
               <td class="crm-case-report-caseroles-role">{$relName.role}</td>
               <td class="crm-case-report-caseroles-sort_name">{$relName.sort_name}</td>
               <td class="crm-case-report-caseroles-phone">{$relName.phone}</td>
               <td class="crm-case-report-caseroles-email">{$relName.email}</td>
           </tr>
         {/if}
  {/foreach}
</table>
<br />

{if $otherRelationships}
    <table  class ="report-layout">
         <tr>
        <th class="reports-header">{ts}Client Relationship{/ts}</th>
        <th class="reports-header">{ts}Name{/ts}</th>
        <th class="reports-header">{ts}Phone{/ts}</th>
        <th class="reports-header">{ts}Email{/ts}</th>
      </tr>
        {foreach from=$otherRelationships item=row key=relId}
        <tr>
            <td class="crm-case-report-otherrelationships-relation">{$row.relation}</td>
            <td class="crm-case-report-otherrelationships-name">{$row.name}</td>
            <td class="crm-case-report-otherrelationships-phone">{$row.phone}</td>
            <td class="crm-case-report-otherrelationships-email">{$row.email}</td>
        </tr>
        {/foreach}
    </table>
    <br />
{/if}

{if $globalRelationships}
    <table class ="report-layout">
         <tr>
         <th class="reports-header">{$globalGroupInfo.title}</th>
          <th class="reports-header">{ts}Phone{/ts}</th>
         <th class="reports-header">{ts}Email{/ts}</th>
      </tr>
        {foreach from=$globalRelationships item=row key=relId}
        <tr>
            <td class="crm-case-report-globalrelationships-sort_name">{$row.sort_name}</td>
            <td class="crm-case-report-globalrelationships-phone">{$row.phone}</td>
            <td class="crm-case-report-globalrelationships-email">{$row.email}</td>
        </tr>
      {/foreach}
    </table>
{/if}

{* BOS1401851 aangepaste sortering voor De Goede Woning *}
{crmAPI var='caseActivities' entity='CaseActivity' action='get' q='civicrm/ajax/rest' sequential=1 case_id=$caseId}
{foreach from=$caseActivities.values item=caseActivity}
    
    {*/**
     * BOSW1604045 insite - dossier conversie
     * Except activity type 'Let op! Gevoelige dossierinformatie'
     */*}
    {assign var='hideActivity' value=1}
    {if isset($hideActivityTypes)}
      {foreach from=$hideActivityTypes item=hideActivityType}
        {if $hideActivityType eq $caseActivity.activity_type_id}
          {assign var='hideActivity' value=0}
        {/if}
      {/foreach}
    {/if}
    
    {if $hideActivity eq 1}

        {* If using plain textarea, assign class=huge to make input large enough. *}
        {* DGW19 / incident 14 010 13 003 laat details alleen zien als showStuff = 1 *}
        {* this template is used for adding/editing activities for a case. *}
        {assign var='showStuff' value=0}
        {if $caseActivity.activity_type_id eq 110}
            {assign var='txtShow' value="Gevoelige informatie, neem contact op met Consulent Wijk en Ontwikkeling voor meer details!"}
        {/if}
        {* get all groups for user *}
        {crmAPI var="userGroups" entity="GroupContact" action="get" contact_id=$session->get('userID')}
        {foreach from=$userGroups.values item=userGroup}
            {if $caseActivity.activity_type_id eq 110}
                {assign var='groupWijk' value=18}
                {if $userGroup.group_id eq 1}
                    {assign var='showStuff' value=1}
                {/if}
                {if $userGroup.group_id eq $groupWijk}
                    {assign var='showStuff' value=1}
                {/if}
            {else}
                {assign var='showStuff' value=1}
            {/if}
        {/foreach}


        <table class="report-layout">
            <tr class="crm-case-report-activity-status">
                <th scope="row" class="label">{ts}Status{/ts}</th>
                <td class="bold">{$caseActivity.status|escape}</td>
            </tr>
            <tr class="crm-case-report-activity-client">
                <th scope="row" class="label">{ts}Client{/ts}</th>
                <td>
                    {assign var='targetFirst' value='1'}
                    {foreach from=$caseActivity.targets item=target}
                        {if $targetFirst eq '1'}
                            {assign var='targetFirst' value='0'}
                        {else}
                            &comma;&nbsp;
                        {/if}                    
                        {$target.target_contact_name|escape}
                    {/foreach}
                </td>
            </tr>
                <tr class="crm-case-report-activity-type">
                    <th scope="row" class="label">{ts}Activity Type{/ts}</th>
                    <td class="bold">{$caseActivity.activity_type|escape}</td>
                </tr>
                <tr class="crm-case-report-activity-subject">
                    <th scope="row" class="label">{ts}Subject{/ts}</th>
                    <td>{$caseActivity.subject}</td>
                </tr>
                <tr class="crm-case-report-activity-source-name">
                    <th scope="row" class="label">{ts}Created by{/ts}</th>
                    <td>{$caseActivity.source_name|escape}</td>
                </tr>
                <tr class="crm-case-report-activity-medium">
                    <th scope="row" class="label">{ts}Medium{/ts}</th>
                    <td>{$caseActivity.medium|escape}</td>
                </tr>
                <tr class="crm-case-report-activity-location">
                    <th scope="row" class="label">{ts}Location{/ts}</th>
                    <td>{$caseActivity.location|escape}</td>
                </tr>
                <tr class="crm-case-report-activity-activity_date_time">
                    <th scope="row" class="label">{ts}Date{/ts}&sol;{ts}Time{/ts}</th>
                    <td>{$caseActivity.activity_date_time|date_format:"%e %B %Y %R"}</td>
                </tr>
                <tr class="crm-case-report-activity-details">
                    <th scope="row" class="label">{ts}Details{/ts}</th>
                    {*<td>{$caseActivity.details}</td>*}
                    <td>
                        {* If using plain textarea, assign class=huge to make input large enough. *}
                        {* DGW19 / incident 14 010 13 003 laat details alleen zien als showStuff = 1 *}
                        {if $showStuff eq 1}
                            {$caseActivity.details}
                        {else}
                            {$txtShow}
                        {/if}
                        {* end DGW19 tweede deel *}
                    </td>
                </tr>
                <tr class="crm-case-report-activity-priority">
                    <th scope="row" class="label">{ts}Priority{/ts}</th>
                    <td>{$caseActivity.priority}</td>
                </tr>
            <tr class="crm-case-report-activity-assignee">
                <th scope="row" class="label">{ts}Assigned to{/ts}</th>
                <td>
                    {assign var='assigneeFirst' value='1'}
                    {foreach from=$caseActivity.assignees item=assignee}
                        {if $assigneeFirst eq '1'}
                            {assign var='assigneeFirst' value='0'}
                        {else}
                            &comma;&nbsp;
                        {/if}                    
                        {$assignee.assignee_contact_name|escape}
                    {/foreach}
                </td>
            </tr>




        </table>
        <br />
    {/if}
{/foreach}

{* remove original core code
<h2>{ts}Case Activities{/ts}</h2>
{foreach from=$activities item=activity key=key}
  <table  class ="report-layout">
       {foreach from=$activity item=field name=fieldloop}
           <tr class="crm-case-report-activity-{$field.label}">
             <th scope="row" class="label">{$field.label|escape}</th>
             {if $field.label eq 'Activity Type' or $field.label eq 'Status'}
                <td class="bold">{$field.value|escape}</td>
             {elseif $field.label eq 'Details' or $field.label eq 'Subject'}
                <td>{$field.value}</td>
             {else}
                <td>{$field.value|escape}</td>
             {/if}
           </tr>
       {/foreach}
  </table>
  <br />
{/foreach}*}
</div>
</body>
</html>





