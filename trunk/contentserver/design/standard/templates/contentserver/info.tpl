{let cs=fetch( 'contentserver', 'objectinformation', hash( 'id', $node.object.remote_id ) )}
<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Contentserver Object Information'|i18n( 'contentserver' )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">
<table class="list">
  <tr class="bglight">
    <th scope="row">Object</th>
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
</table>



{section show=$cs.csi}
Import Information
<table class="list">
  <tr class="bglight">
    <th scope="row">Received</th>
    <td>
    {$cs.csi.created|l10n( 'shortdate' )} by {$cs.csi.remote_host}
	</td>
  </tr>
  <tr class="bgdark">
    <th scope="row">Type</th>
    <td>
        {section show=$cs.csi.import_type|eq(2)}Subtree{section-else}Node{/section}
	</td>
  </tr>
  <tr class="bglight">
    <th scope="row">Update</th>
    <td>
    {section show=$cs.csi.updateflag|eq(1)}Never{section-else}Always{/section}
	</td>
  </tr>
  <tr class="bgdark">
    <th scope="row">Expires</th>
    <td>
     {section show=$cs.csi.expires}{$cs.csi.expires|l10n( 'shortdate' )}{section-else}Never{/section}
	</td>
  </tr>
  <tr class="bglight">
    <th scope="row">Remote Paths</th>
    <td>
    {section name=Import loop=$cs.csi.data_array}
    <a href="http://{$cs.csi.remote_host}/{$:item}">{$:item|shorten( 55, '...')}</a><br />
    {/section}
    </td>
  </tr>  
                  
</table>
{/section}

{section show=$cs.cse}
Export Information
<table class="list">
  <tr class="bglight">
    <th scope="row">Exported</th>
    <td>
    {$cs.cse.created|l10n( 'shortdate' )} as {section show=$cs.cse.export_type|eq(2)}Subtree{section-else}Node{/section}
	</td>
  </tr>
  <tr class="bglight">
    <th scope="row">Type</th>
    <td>
        {section show=$cs.export_type|eq(2)}Subtree{section-else}Node{/section}
	</td>
  </tr>
  <tr class="bglight">
    <th scope="row">Update</th>
    <td>
    {section show=$cs.cse.updateflag|eq(1)}Never{section-else}Always{/section}
	</td>
  </tr>
  <tr class="bglight">
    <th scope="row">Expires</th>
    <td>
     {section show=$cs.cse.export_type|eq(0)}Never{section-else}{$cs.cse.expires|l10n( 'shortdate' )}{/section}
	</td>
  </tr>
</table>
{/section}

{* DESIGN: Content END *}</div></div></div></div></div></div>

</div>
{/let}