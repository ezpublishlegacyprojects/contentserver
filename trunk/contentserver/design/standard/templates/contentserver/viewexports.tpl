<form name="contentserver" method="post" action={"contentserver/viewexports"|ezurl}>

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Exported Objects'|i18n( 'contentserver' )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-toolbar">

<div class="block">

<p>This interface shows all exported content objects.</p>
<div class="block" style="padding: 20px">
<label>Find ID:</label><div class="break"></div>
<input type="text" name="ID" value="{section show=$id}{$id}{/section}"/>
<input class="button" type="submit" name="Find" value="Search" />
</div>
</div>

</div>

{let page_limit=30}


{section show=$list}

        <table class="list">
        <tr>
            <th class="name">
                {"Name"|i18n("contentserver")}
            </th>
            <th>
                {"Class"|i18n("contentserver")}
            </th>
            <th>
                {"Created"|i18n("contentserver")}
            </th>
            <th>
            </th>
            <th>
            </th>
         </tr>
        {section loop=$list sequence=array( bglight, bgdark ) max=$page_limit offset=$view_parameters.offset}
            <tr class="{$:sequence}">
                <td>
                    <a href={$:item.object.main_node.url_alias|ezurl}>{node_view_gui view=line content_node=$:item.object.main_node}</a>
                </td>
                <td>
                    {$:item.object.class_name|wash}
                </td>
                <td>
                    {$:item.created|l10n( 'shortdate' )}
                </td>
                <td>
                    <a href={concat( 'contentserver/info/',$:item.id)|ezurl}><img src={'contentserver_info.gif'|ezimage} title="View extra information"></a>
                </td>
                <td>
                <a href={concat('contentserver/edit/',$:item.id, '?RedirectURI=','contentserver/viewexports')|ezurl}>
                    <img src={'export_red.gif'|ezimage} alt="{'Modify Export'|i18n( 'contentserver' )}" title="{'Modify Export'|i18n( 'contentserver' )}" />
                </a>
                </td>
            </tr>
        {/section}
        </table>

    {include name=navigator
             uri='design:navigator/google.tpl'
             page_uri=concat( '/contentserver/view/' )
             item_count=$list_count
             view_parameters=$view_parameters
             item_limit=$page_limit}


{/section}

{* DESIGN: Content END *}</div></div></div>


<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">

</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>

{/let}

</form>