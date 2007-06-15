{section show=$Output}

<pre style="color:green;">
{$Output}
</pre>

{/section}
<form name="contentserver" method="post" action={'contentserver/client'|ezurl}>
<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Contentserver webclient'|i18n( 'contentserver' )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-toolbar">

<div class="block">
<div class="left">
    <p>
        <a href={'/incoming'|ezurl}>Incoming Folder</a>
        <a href={'contentserver/view'|ezurl}>Imports</a>
        <span class="current">Webclient</span>
    </p>
</div>
<div class="break"></div>

  <p>Content Server Webclient</p>
  <p>Use this interface only if your site is not configured to run a cron job.</p>
  <p>If you wish to update a single object, enter its RemoteID in the empty form field below.</p>
</div>
</div>

<table class="list">
  <tr class="bglight">
    <th scope="row">{'Server'|i18n( 'contentserver' )}</th>
    <td>
        <input name="Server" type="text" value="{$Server}" />
    </td>
  </tr>
  <tr class="bgdark">
    <th scope="row">{'Port'|i18n( 'contentserver' )}</th>
    <td>
        <input class="half" name="Port" type="text" value="{$Port}" />
    </td>
  </tr>
  <tr class="bglight">
    <th scope="row">{'Username'|i18n( 'contentserver' )}</th>
    <td>
        <input name="Username" type="text" value="{$Username}" />
    </td>
  </tr>
  <tr class="bgdark">
    <th scope="row">{'Password'|i18n( 'contentserver' )}</th>
    <td>
        <input name="Password" type="password" value="{$Password}" />
    </td>
  </tr>
  <tr class="bglight">
    <th scope="row">{'RemoteID'|i18n( 'contentserver' )}</th>
    <td>
        <input name="RemoteID" type="text" value="{$RemoteID}" />
    </td>
  </tr>
</table>


{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
    <input class="button" name="Run" type="submit" value="Run" />
    {section show=ezini('ContentServerSettings','ForceUpdateButton','content.ini')|eq('enabled')}
    <input class="button" name="ForceUpdate" type="submit" value="Force Update" />
    {section-else}
    <input class="button-disabled" name="ForceUpdate" type="submit" value="Force Update" disabled="disabled" />
    {/section}
    {section show=ezini('ContentServerSettings','ClearAllButton','content.ini')|eq('enabled')}
    <input class="button" name="ClearAll" type="submit" value="Remove All Imports" />
    <input class="button" name="DownloadPackage" type="submit" value="Download Package" />
    {section-else}
    <input class="button-disabled" name="ClearAll" type="submit" value="Remove All Imports" disabled="disabled" />
    {/section}
    <input class="button" type="submit" name="Cancel" value="Cancel" />
</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>

</form>
