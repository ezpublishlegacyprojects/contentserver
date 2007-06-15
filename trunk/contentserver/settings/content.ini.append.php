# Deprecated
#[NodeSettings]
#IncomingNode=4

[ContentServerSettings]
#User in Remote System
User=admin
Password=publish
Server=localhost
Port=80
#User in local System
LocalSystemUser=contentserver
MatchRuleOrder[]
MatchRuleOrder[]=path_name
MatchRuleOrder[]=remote_id
ForceUpdateButton=disabled
ClearAllButton=disabled

[ContentServer]
#Client=enabled
#Server=enabled
#NodeExportClassList[]=article
#SubtreeExportClassList[]=folder
