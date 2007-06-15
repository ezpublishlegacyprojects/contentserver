{foreach $errors as $error}
    <div class="message-error">
        <h2>{$error}</h2>
    </div>
{/foreach}
<form name="contentserver" method="post" action={concat( "contentserver/changeremoteid/", $node.node_id )|ezurl}>
<input name="RedirectURI" type="hidden" id="RedirectURI" value="{$RedirectURI}"> 
<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Change remote IDs'|i18n( 'contentserver' )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>


{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">
<table class="list">
  <tr class="bglight">
    <th scope="row">Elemets name</th>
    <th scope="row">Status</th>
    <th scope="row">Tree Level</th>
    <th scope="row">Remote Object ID</th>

  </tr>
{section loop=$node_path sequence=array( bglight, bgdark )}
{let cs=fetch( 'contentserver', 'objectinformation', hash( 'id', $:item.object.remote_id ) ) }
<tr class="{$:sequence}">
    <td>
        {$:item.name}
	</td>
	<td>
	    {section show=$cs.csi}Imported{section-else}none{/section}
	</td>
	<td>
        {$:item.depth}
	</td>
	<td>
	{section show=or( $:item.depth|eq(1), $cs.csi )}
	   <input style="width:90%;" disabled="disabled" name="IDArray[{$:item.object.remote_id}]" type="text" value="{$:item.object.remote_id}" />
    {section-else}
	   <input style="width:90%;" name="IDArray[{$:item.object.remote_id}]" type="text" value="{$:item.object.remote_id}" />
	{/section}

	</td>
</tr>
{/let}
{/section}
</table>
{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">

<input class="button" type="submit" name="Cancel" value="Cancel" />

<input class="button" type="submit" name="Store" value="Store" />

</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>

</form>
