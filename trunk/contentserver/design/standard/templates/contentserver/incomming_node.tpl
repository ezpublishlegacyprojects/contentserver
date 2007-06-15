<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Contentserver Incoming Folder'|i18n( 'contentserver' )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-toolbar">

<div class="block">
<div class="left">
    <p>

        <span class="current">Incoming Folder</span>
        <a href={'contentserver/view'|ezurl}>Imports</a>
        <a href={'contentserver/client'|ezurl}>Webclient</a>
    </p>
</div>
<div class="break"></div>

<p>This interface shows all imported content objects that were not auto-placed in the content object tree.</p>

</div>

</div>




{let page_limit=15
     list_count=fetch( content, list_count, hash( parent_node_id, $node.node_id ) )
     children=fetch( content, list, hash( parent_node_id, $node.node_id,
                                             sort_by, $node.sort_array,
                                             limit, $page_limit,
                                             offset, $view_parameters.offset ) )}

{section show=$children}

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
        </tr>
        {section loop=$:children sequence=array( bglight, bgdark )}
            {let cs=fetch('contentserver','objectinformation', hash( 'id', $:item.object.remote_id ))}

            <tr class="{$:sequence}">
                <td>
                    <a href={$:item.url_alias|ezurl}>{node_view_gui view=line content_node=$:item}</a>
                </td>
                <td>
                    {$:item.object.class_name|wash}
                </td>
                <td>
                    {$cs.csi.created|l10n( 'shortdate' )}
                </td>
                <td>
                    <a href={concat( 'contentserver/info/',$cs.csi.id)|ezurl}><img src={'contentserver_info.gif'|ezimage} title="View extra information"></a>
                </td>
            </tr>
            {/let}
        {/section}
        </table>

    {include name=navigator
             uri='design:navigator/google.tpl'
             page_uri=concat('/content/view','/full/',$node.node_id)
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