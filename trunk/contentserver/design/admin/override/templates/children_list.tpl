<div class="content-navigation-childlist">
    <table class="list" cellspacing="0">
    <tr>
        {* Remove column *}
        <th class="remove"><img src={'toggle-button-16x16.gif'|ezimage} alt="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" title="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}" onclick="ezjs_toggleCheckboxes( document.children, 'DeleteIDArray[]' ); return false;" /></th>

        {* Name column *}
        <th class="name">{'Name'|i18n( 'design/admin/node/view/full' )}</th>

        {* Class type column *}
        <th class="class">{'Type'|i18n( 'design/admin/node/view/full' )}</th>

        {* Priority column *}
        {section show=eq( $node.sort_array[0][0], 'priority' )}
            <th class="priority">{'Priority'|i18n( 'design/admin/node/view/full' )}</th>
        {/section}
        {* CONTENT SERVER column *}
        <th class="edit">&nbsp;</th>
{section show=ezini('ContentServer','Client','content.ini')|eq('enabled')}
        <th class="edit">&nbsp;</th>
{/section}
        {* Edit column *}
        <th class="edit">&nbsp;</th>
    </tr>

    {section var=Nodes loop=$children sequence=array( bglight, bgdark )}
    {let child_name=$Nodes.item.name|wash
         node_name=$node.name}

        <tr class="{$Nodes.sequence}">

        {* Remove checkbox *}
        <td>
        {section show=$Nodes.item.can_remove}
            <input type="checkbox" name="DeleteIDArray[]" value="{$Nodes.item.node_id}" title="{'Use these checkboxes to select items for removal. Click the "Remove selected" button to actually remove the selected items.'|i18n( 'design/admin/node/view/full' )|wash()}" />
            {section-else}
            <input type="checkbox" name="DeleteIDArray[]" value="{$Nodes.item.node_id}" title="{'You do not have permissions remove this item.'|i18n( 'design/admin/node/view/full' )}" disabled="disabled" />
        {/section}
        </td>

        {* Name *}
        <td>{node_view_gui view=line content_node=$Nodes.item}</td>

        {* Class type *}
        <td class="class">{$Nodes.item.class_name|wash()}</td>

        {* Priority *}
        {section show=eq( $node.sort_array[0][0], 'priority' )}
            <td>
            {section show=$node.can_edit}
                <input type="text" name="Priority[]" size="3" value="{$Nodes.item.priority}" title="{'Use the priority fields to control the order in which the items appear. Use positive and negative integers. Click the "Update priorities" button to apply the changes.'|i18n( 'design/admin/node/view/full' )|wash()}" />
                <input type="hidden" name="PriorityID[]" value="{$Nodes.item.node_id}" />
                {section-else}
                <input type="text" name="Priority[]" size="3" value="{$Nodes.item.priority}" title="{'You are not allowed to update the priorities because you do not have permissions to edit <%node_name>.'|i18n( 'design/admin/node/view/full',, hash( '%node_name', $node_name ) )|wash}" disabled="disabled" />
            {/section}
            </td>
        {/section}
        <td>
        {* START CONTENT SERVER BUTTON *}

{default node_name=$Nodes.item.name
         node_url=$Nodes.item.url_alias
         Contentserveritem=$Nodes.item
         cs=fetch( 'contentserver', 'objectinformation', hash( 'id', $Nodes.item.object.remote_id ) )
}
{*$Nodes.item.object.remote_id*}
{*$cs|attribute(show,2)*}

{section show=$cs.status|eq(2)}
    <a href={concat('contentserver/info/',$Contentserveritem.object.remote_id, '?RedirectURI=',$Contentserveritem.parent.url_alias)|ezurl}>
        <img src={'export_grey.gif'|ezimage} alt="{'Imported on %date'|i18n( 'contentserver',,hash( '%date',$cs.csi.created|l10n( 'shortdate' ) ) )}" title="{'Imported on %date'|i18n( 'contentserver',,hash( '%date',$cs.csi.created|l10n( 'shortdate' ) ) )}" />
    </a>
{/section}

{section show=$cs.status|eq(1)}
    <a href={concat('contentserver/edit/',$Contentserveritem.object.remote_id, '?RedirectURI=',$Nodes.item.parent.url_alias)|ezurl}>
        <img src={'export_red.gif'|ezimage} alt="{'Exported on %date'|i18n( 'contentserver',,hash( '%date',$cs.cse.created|l10n( 'shortdate' ) ) )}" title="{'Exported on %date'|i18n( 'contentserver',,hash( '%date',$cs.cse.created|l10n( 'shortdate' ) ) )}" />
    </a>
{/section}

{section show=$cs.status|eq(5)}
        {section show=$cs.export_type|eq(2)}
            <a href={concat('contentserver/edit/',$Contentserveritem.object.remote_id, '?RedirectURI=',$Contentserveritem.parent.url_alias)|ezurl}>
                <img src={'export_green.gif'|ezimage} alt="{'Export subtree'|i18n( 'contentserver' )}" title="{'Export subtree'|i18n( 'contentserver' )}" />
            </a>
        {section-else}
            <a href={concat('contentserver/edit/',$Contentserveritem.object.remote_id, '?RedirectURI=',$Contentserveritem.parent.url_alias)|ezurl}>
                <img src={'export_green.gif'|ezimage} alt="{'Export node'|i18n( 'contentserver' )}" title="{'Export node'|i18n( 'contentserver' )}" />
            </a>
        {/section}
{/section}
{section show=or( $cs.status|eq(0), $cs.status|eq(4) )}
    <img src={'export_disabled.gif'|ezimage} alt="{'Contentserver not available'|i18n( 'contentserver' )}" title="{'Contentserver not available'|i18n( 'contentserver' )}" />
{/section}

{/default}

        {* END CONTENT SERVER BUTTON *}
        </td>
{section show=ezini('ContentServer','Client','content.ini')|eq('enabled')}
  <td>
    <a href={concat('contentserver/changeremoteid/',$Nodes.item.node_id, '?RedirectURI=',$node.parent.url_alias)|ezurl}>
      <img src={'changeid.gif'|ezimage} alt="{'Change RemoteID Path'|i18n( 'contentserver' )}" title="{'Change RemoteID Path'|i18n( 'contentserver' )}" />
    </a>
  </td>
{/section}
        <td>
        {* Edit button *}

        {section show=$Nodes.item.can_edit}
            <a href={concat( 'content/edit/', $Nodes.item.contentobject_id )|ezurl}><img src={'edit.gif'|ezimage} alt="{'Edit'|i18n( 'design/admin/node/view/full' )}" title="{'Edit <%child_name>.'|i18n( 'design/admin/node/view/full',, hash( '%child_name', $child_name ) )|wash}" /></a>
        {section-else}
            <img src={'edit-disabled.gif'|ezimage} alt="{'Edit'|i18n( 'design/admin/node/view/full' )}" title="{'You do not have permissions to edit %child_name.'|i18n( 'design/admin/node/view/full',, hash( '%child_name', $child_name ) )|wash}" /></a>
        {/section}

        </td>
  </tr>

{/let}
{/section}

</table>
</div>

