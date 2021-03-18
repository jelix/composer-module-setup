
pluginsPath="app:plugins,lib:jelix-plugins,module:jacl2db/plugins"
modulesPath="lib:jelix-admin-modules,lib:jelix-modules,app:modules"


[simple_urlengine_entrypoints]
index="@classic"
admin="jacl2db~*@classic, jacl2db_admin~*@classic, jauthdb_admin~*@classic, master_admin~*@classic"

[modules]
jelix.access=1
jacl2.access=1
jacl2db.access=1


