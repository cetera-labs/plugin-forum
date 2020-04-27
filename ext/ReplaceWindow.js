Ext.define('Plugin.forum.ReplaceWindow', {
	
	extend:'Ext.Window',

    closeAction: 'hide',
    title: _('Автозамена'),
    width: 350,
    height: 450,
    layout: 'fit',
    resizable: false,
    editId: 0,
    
    initComponent : function() {
        this.items = this.buildTree();
           
        ForumsReplaceWindow.superclass.initComponent.call(this);
        
        this.tree.getSelectionModel().on('selectionchange', function(sm, node){
            Ext.getCmp('tb_replace_group_delete').setDisabled(!this.isSelectedGroup());
            Ext.getCmp('tb_replace_new').setDisabled(!this.isSelectedGroup());
            Ext.getCmp('tb_replace_props').setDisabled(!node);
            Ext.getCmp('tb_replace_new').setDisabled(!node);
            Ext.getCmp('tb_replace_delete').setDisabled(this.isSelectedGroup());
            Ext.getCmp('tb_replace_enable').setDisabled(!node);
            Ext.getCmp('tb_replace_disable').setDisabled(!node);
        }, this);
        
        this.buildEditWindow();
        this.editForm.getForm().findField('idgroup').getStore().load();
    },
    
    onDestroy : function(){
		Ext.destroy(this.tree, this.editForm, this.editWindow);
    },
    
    createGroup: function (text) {
        Ext.Ajax.request({
            url: '/plugins/forum/scripts/action_forums.php',
            params: { 
                action: 'new_replace_group', 
                name: text
            },
            scope: this,
            success: function(resp) {
                this.reload(true);
            }
        });
    },
    
    editGroup: function() {
        Ext.MessageBox.prompt(_('Изменить группу'), _('Введите название группы:'), function(btn, text) {
            if (btn == 'ok') {
                Ext.Ajax.request({
                    url: '/plugins/forum/scripts/action_forums.php',
                    params: { 
                        action: 'new_replace_group', 
                        id: this.getSelectedGroupId(),
                        name: text
                    },
                    scope: this,
                    success: function(resp) {
                        this.reload(true);
                    }
                });
            };
        }, this, false, this.tree.getSelectionModel().getSelectedNode().text);
    },
    
    change: function(action) {
        Ext.Ajax.request({
            url: '/plugins/forum/scripts/action_forums.php',
            params: { 
                action: action, 
                node: this.tree.getSelectionModel().getSelectedNode().id
            },
            scope: this,
            success: function(resp) {
                this.reload(false);
            }
        });
    },
    
    reload: function(root) {
        if (root)
            this.tree.loader.load(this.tree.root);
            else {
                var node = this.tree.getSelectionModel().getSelectedNode().parentNode;
                this.tree.loader.load(node, function() {
                    node.expand();
                }, this);
            }
    },
    
    splitSelected: function() {
        var sn = this.tree.getSelectionModel().getSelectedNode();
        if (!sn) return false;
        return sn.id.split('-');
    },
    
    isSelectedGroup: function() {
        var a = this.splitSelected();
        return a[0] == 'group';
    },
    
    getSelectedGroup: function() {
        if (this.isSelectedGroup()) {
            return this.tree.getSelectionModel().getSelectedNode();
        } else {
            return this.tree.getSelectionModel().getSelectedNode().parentNode;
        }
    },
    
    getSelectedGroupId: function() {
        var sn = this.getSelectedGroup();
        var a = sn.id.split('-');
        return a[1];
    },
    
    editClick: function() {
        if (this.isSelectedGroup())
            this.editGroup();
            else {               
                Ext.Ajax.request({
                    url: '/plugins/forum/scripts/action_forums.php',
                    params: {
                        action: 'replace_get_replace',
                        id: this.splitSelected()[1]
                    },
                    scope: this,
                    success: function(resp) {
                        var obj = Ext.decode(resp.responseText);
                        this.editId = this.splitSelected()[1];
                        var win = this.buildEditWindow();
                        this.editForm.getForm().reset();
                        win.setTitle(_('Свойства'));
                        win.show();
                        this.editForm.getForm().setValues(obj.data);
                    }
                });

            } 
    },
    
    newClick: function() {
        this.editId = 0;
        var win = this.buildEditWindow();
        win.setTitle(_('Новая автозамена'));
        this.editForm.getForm().reset();
        this.editForm.getForm().findField('prop').setValue(0);
        
        this.editForm.getForm().findField('idgroup').getStore().load({
            scope: this,
            callback: function() {
                this.editForm.getForm().findField('idgroup').setValue(this.getSelectedGroupId());
            },
        });
        
        win.show();
    },
    
    buildEditWindow: function() {
        if (!this.editWindow) {
            this.editForm = new Ext.form.FormPanel({
                baseCls: 'x-plain',
                labelWidth: 150,
                defaultType: 'textfield',
                method: 'POST',
                url: '/plugins/forum/scripts/action_forums.php',
                defaults : { anchor: '0' },
                bodyStyle: 'margin:10px;',
                items: [{
                    fieldLabel: _('Имя'),
                    allowBlank: false,
                    name: 'name'
                }, new Ext.form.ComboBox({
                    fieldLabel: _('Группа'),
                    name:'idgroup',
                    allowBlank: false,
                    store: new Ext.data.JsonStore({
                        fields: ['id', 'text'],
                        root: 'rows',
                        url: '/plugins/forum/scripts/data_replace.php?store=1&node=root'
                    }),
                    valueField:'id',
                    displayField:'text',
                    mode: 'local',
                    triggerAction: 'all',
                    editable: false,
                }), {
                    fieldLabel: _('Строка для замены'),
                    name: 'pattern'
                }, {
                    fieldLabel: _('На что заменяется'),
                    name: 'replacement'
                }, new Ext.form.ComboBox({
                    fieldLabel: _('Способ замены'),
                    name:'prop',
                    allowBlank: false,
                    store: new Ext.data.SimpleStore({
                        fields: ['id', 'name'],
                        data : [[0, _('Простая замена')], [1, ('Регулярное выражение')]]
                    }),
                    valueField:'id',
                    displayField:'name',
                    mode: 'local',
                    triggerAction: 'all',
                    editable: false
                }), {
                    xtype: 'checkbox',
                    boxLabel: _('включить'),
                    name: 'active',
                    inputValue: '1'
                }]
            });
            
            this.editWindow = new Ext.Window({
                width: 500,
                height:240,
                plain:true,
                closeAction: 'hide',
                items: this.editForm,
                resizable: false,
                modal: true,
                buttons: [{
                        text: _('Ok'),
                        scope: this,
                        handler: function(){
                          this.editForm.getForm().submit({
                            params: {
                                id: this.editId,
                                action: 'replace_save'
                            },
                            scope: this,
                            success: function(form, action) {
                                this.reload(0);
                                this.editWindow.hide();
                            }
                          });
                        }
                    },{
                        text: _('Отмена'),
                        scope: this,
                        handler: function(){ this.editWindow.hide(); }
                }]
            });
        }
        
        return this.editWindow;
    },
    
    buildTree: function() {
        if (this.tree) return this.tree;
        this.tree = new Ext.tree.TreePanel({
            useArrows: true,
            border: false,
            rootVisible: false,
            dataUrl: '</plugins/forum/scripts/data_replace.php',
            containerScroll: true,
            autoScroll: true,
            root: {id: 'root'},
            tbar: [
                {
                    id: 'tb_replace_new_group',
                    iconCls:'icon-new_folder',
                    tooltip:_('Создать группу'),
                    handler: function () {
                        Ext.MessageBox.prompt(_('Создать группу'), _('Введите название группы:'), function(btn, text) {
                            if (btn == 'ok') this.createGroup(text);
                        }, this);
                    },
                    scope: this
                },{
                    id: 'tb_replace_group_delete',
                    iconCls:'icon-folder_delete',
                    tooltip:_('Удалить группу'),
                    disabled: true,
                    handler: function () { 
                        Ext.MessageBox.confirm(_('Удаление группы'), _('Вы уверены'), function(btn) {
                            if (btn == 'yes') this.change('replace_delete'); 
                        }, this);
                    },
                    scope: this
                },'-',{
                    id: 'tb_replace_new',
                    iconCls:'icon-new',
                    tooltip:_('Создать'),
                    disabled: true,
                    handler: this.newClick,
                    scope: this
                },{
                    id: 'tb_replace_delete',
                    iconCls:'icon-delete',
                    tooltip:_('Удалить'),
                    disabled: true,
                    handler: function () {
                        Ext.MessageBox.confirm(_('Удаление'), _('Вы уверены?'), function(btn) {
                            if (btn == 'yes') this.change('replace_delete'); 
                        }, this);
                    },
                    scope: this
                },'-',{
                    id: 'tb_replace_enable',
                    iconCls:'icon-lamp_glow',
                    tooltip:_('Включить'),
                    disabled: true,
                    handler: function () { this.change('replace_enable'); },
                    scope: this
                },{
                    id: 'tb_replace_disable',
                    iconCls:'icon-lamp',
                    tooltip:_('Выключить'),
                    disabled: true,
                    handler: function () { this.change('replace_disable'); },
                    scope: this
                },'-',{
                    id: 'tb_replace_props',
                    iconCls:'icon-props',
                    tooltip:_('Изменить'),
                    disabled: true,
                    handler: this.editClick,
                    scope: this
                }
            ]
        });
        return this.tree;
    }
});
