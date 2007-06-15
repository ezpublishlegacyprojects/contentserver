{section show=$errors.time}
    <div class="message-error">
        <h2>{'Date was not given or incorrect.'|i18n( 'contentserver' )}</h2>
    </div>
{/section}
<form name="contentserver" method="post" action={concat( "contentserver/edit/", $node.object.remote_id )|ezurl}>
<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Contentserver export'|i18n( 'contentserver' )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">
<table class="list">
  <tr class="bglight">
    <th scope="row">Object for export</th>
    <td>
{node_view_gui view=line content_node=$node}
	</td>
  </tr>
  <tr class="bgdark">
    <th scope="row">Remote ID of Object</th>
    <td>
{$node.object.remote_id}
	</td>
  </tr>
  <tr class="bglight">
    <th scope="row">Remote ID of Parent Object</th>
    <td>
{$node.parent.object.remote_id}
	</td>
  </tr>
  {section show=$cse.created}
  <tr class="bglight">
    <th scope="row">Exported</th>
    <td>
    {$cse.created|l10n( 'shortdate' )}
	</td>
  </tr>
{/section}
</table>
{* DESIGN: Content END *}</div></div></div></div></div></div>

</div>

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h2 class="context-title">{'Export options'|i18n( 'contentserver' )}</h2>

{* DESIGN: Mainline *}<div class="header-subline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<input name="RedirectURI" type="hidden" id="RedirectURI" value="{$RedirectURI}"> 

<table class="list">
  <tr class="bglight">
    <th scope="row">Export type</th>
    <td>
	<select name="Type">
	{section show=$Type|eq('1')}  
	  <option value="{$Type}">Subtree</option>
	{section-else}
      <option value="{$Type}">Node</option>
	{/section}
   	</select>
	</td>
  </tr>
  <tr class="bgdark">
    <th scope="row">Expiry</th>
    <td>
        <label>
        <input type="radio" name="Expiry" value="0" {section show=$Expiry|ne(1)}checked{/section}>
    never</label>
        <br>
        <label>
        <input type="radio" name="Expiry" value="1" {section show=$Expiry|eq(1)}checked{/section}>
    to date
    
</label>
<script src={"javascripts/anchorposition.js"|ezdesign} charset="UTF-8" type="text/javascript" language="JavaScript"></script>
<script src={"javascripts/popupwindow.js"|ezdesign} charset="UTF-8" type="text/javascript" language="JavaScript"></script>
<script src={"javascripts/calendarpopup.js"|ezdesign} charset="UTF-8" type="text/javascript" language="JavaScript"></script>
{literal}
<SCRIPT LANGUAGE="JavaScript" ID="js9">
var cal9 = new CalendarPopup();
cal9.setReturnFunction("setMultipleValues");
function setMultipleValues(y,m,d) {
	document.contentserver.Year.value=y;
	document.contentserver.Month.value=m;
	document.contentserver.Day.value=d;
	}
</SCRIPT>
<SCRIPT LANGUAGE="JavaScript">writeSource("js9");</SCRIPT>
{/literal}
<table style="margin-left: 20px;">
  <tr>
    <th>
	{"Day"|i18n("contentserver")}
    </th>
    <th>
        {"Month"|i18n("contentserver")}
    </th>
    <th>
       {"Year"|i18n("contentserver")}
    </th>
  </tr>
    <td>
	<input type="text" name="Day" size="3"  value="{$Day}" READONLY />
    </td>
    <td>
	<input type="text" name="Month" size="3" value="{$Month}" READONLY />
    </td>
    <td>
	<input type="text" name="Year" size="4" value="{$Year}" READONLY />
    </td>
    <td>
	<input type="image" src={"calendar.gif"|ezimage} onClick="cal9.showCalendar('anchor9'); return false;" TITLE="Click to view dates" NAME="anchor9" ID="anchor9" value="Calendar" />
    </td>
  </tr>
</table>
      </td>
  </tr>
  <tr class="bglight">
    <th scope="row">Update</th>
    <td><p>
      <label>
      <input type="radio" name="Update" value="0" {section show=$Update|ne(1)}checked{/section} />
  always</label>
      <br>
      <label>
      <input type="radio" name="Update" value="1" {section show=$Update|eq(1)}checked{/section} />
  never</label>
      <br>
    </p></td>
  </tr>
</table>

{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">

<input class="button" type="submit" name="Store" value="Store" />

<input class="button" type="submit" name="Cancel" value="Cancel" />

</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>

</form>
