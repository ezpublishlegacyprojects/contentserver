<script language="JavaScript1.2" type="text/javascript">
menuArray['ContextMenu']['elements']['menu-cs-changeid']= new Array();
menuArray['ContextMenu']['elements']['menu-cs-changeid']['url'] = {"/contentserver/changeremoteid/%nodeID%"|ezurl};
</script>
<hr/>
<a id="menu-version-history" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )">{"Version History"|i18n("design/admin/popupmenu")}</a>
{* Translate document *}
<form id="menu-form-cs-changeid" method="post" action={"/contentserver/changeremoteid/%nodeID%/"|ezurl}>
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>