Ext.define('Plugin.forum.Panel', {

    extend:'Ext.grid.GridPanel',
    
    columns: [
        {
            header: "", width: 25, sortable: false, dataIndex: 'icon_pub', 
            renderer: function (value, p, r){
                if (value == '1')
                    return '';
                    else return '<img src="/cms/images/globe_c_s.gif" title="'+_('Ожидает премодерации')+'" width="14" height="14" />';
            }
        },{
            header: "", width: 25, sortable: false, dataIndex: 'icon_close', 
            renderer: function (value, p, r) {
                if (value == '1')
                    return '<img src="/cms/images/lock_small.gif" title="'+_('Тема закрыта')+'" width="10" height="11" />';
                    else return '';
            }
        },
        {header: _('Заголовок'), width: 75, dataIndex: 'name', flex: 1},
        {header: _('Дата создания'), width: 105, dataIndex: 'dat', renderer: Ext.util.Format.dateRenderer('d.m.Y H:i')},
        {header: _('Автор'), width: 150, dataIndex: 'autor'},
        {header: _('Ответов'), width: 55, dataIndex: 'answers'}
    ],
    
    initComponent : function() {
   
        this.store = Ext.create('Ext.data.JsonStore', {
            autoDestroy: true,
            fields: ['icon_pub','icon_close','name',{name: 'dat', type: 'date', dateFormat: 'timestamp'},'autor','answers','disabled'],
            totalProperty: 'total',
            remoteSort: true,
            pageSize: Cetera.defaultPageSize,
            sorters: [{property: "dat", direction: "DESC"}],
            proxy: {
                type: 'ajax',
                url: '/plugins/forum/scripts/data_posts.php',
                simpleSortMode: true,
                extraParams: {'id': 0, 'math_subs': 0, 'parent': 0},
                reader: {
                    root: 'rows',
                    idProperty: 'id'
                }
            }
        });               
        
        this.forumList = new Ext.data.JsonStore({
            autoDestroy: true,
            fields: ['id','name'],             
            proxy: {
                type: 'ajax',
                url: '/plugins/forum/scripts/data_forums.php', 
                reader: {
                    root: 'rows',
                    idProperty: 'id'
                }
            }                    
        });
        
        this.breadCrumb = new Ext.Toolbar({hidden: true});
        
        this.tbar = new Ext.Container({
            items: [
                new Ext.Toolbar({
                    border: false,
                    items: [
                        {
                            id: 'tb_forum_new',
                            iconCls:'icon-new',
                            tooltip:_('Создать'),
                            handler: function () { this.edit(this.forumId,0); },
                            scope: this
                        }, '-',
                        {
                            id: 'tb_forum_edit',
                            disabled: true,
                            iconCls:'icon-edit',
                            tooltip:_('Редактировать'),
                            handler: function () { this.edit(0,this.getSelectionModel().getSelection()[0].getId()); },
                            scope: this
                        },
                        {
                            id: 'tb_forum_delete',
                            disabled: true,
                            iconCls:'icon-delete',
                            tooltip:_('Удалить'),
                            handler: function () { this.deleteMat(); },
                            scope: this
                        },'-',{
                            id: 'tb_forum_pub',
                            disabled: true,
                            iconCls:'icon-pub',
                            tooltip:_('Опубликовать'),
                            handler: function() { this.call('pub'); },
                            scope: this
                        },{
                            id: 'tb_forum_unpub',
                            disabled: true,
                            iconCls:'icon-unpub',
                            tooltip:_('Отменить публикацию'),
                            handler: function() { this.call('unpub'); },
                            scope: this
                        },'-',{
                            id: 'tb_forum_close',
                            disabled: true,
                            iconCls:'icon-close',
                            tooltip:_('Закрыть тему'),
                            handler: function() { this.call('close'); },
                            scope: this
                        },{
                            id: 'tb_forum_open',
                            disabled: true,
                            iconCls:'icon-open',
                            tooltip:_('Открыть тему'),
                            handler: function() { this.call('open'); },
                            scope: this
                        },
                        '-',
                        {
                            id: 'tb_forum_down',
                            disabled: true,
                            iconCls:'icon-down',
                            tooltip:_('Показать ответы'),
                            handler: function() { this.showAnswers(); },
                            scope: this
                        }
                    ]
                }),
                this.breadCrumb
            ]
        });
        
        this.getSelectionModel().on({
            'selectionchange' : function(sm){
                var hs = sm.hasSelection();
                var sf = this.store.sorters.first().property;
                Ext.getCmp('tb_forum_edit').setDisabled(!hs);
                Ext.getCmp('tb_forum_delete').setDisabled(!hs);
                Ext.getCmp('tb_forum_close').setDisabled(!hs);
                Ext.getCmp('tb_forum_open').setDisabled(!hs);
                Ext.getCmp('tb_forum_down').setDisabled(!hs);
                Ext.getCmp('tb_forum_pub').setDisabled(!hs || !this.allow_pub);
                Ext.getCmp('tb_forum_unpub').setDisabled(!hs || !this.allow_pub);
            },
            'beforerowselect' : function(sm, rowIndex, keepExisting, record) {
                if (record.getComponent('disabled')) return false;
            },
            scope:this
        });
        
        this.on({
            'beforedestroy': function() {
                mainTree.getSelectionModel().removeListener('selectionchange', this.catalogChanged, this);
                treeContainer.remove(this.forums);
                if (this.replaceWin) this.replaceWin.close();
                if (this.propertiesWin) this.propertiesWin.close();
                this.replaceWin = false;
                this.propertiesWin = false;
            },
            'celldblclick' : function() {
                this.edit(0,this.getSelectionModel().getSelection()[0].getId());
            },
            'activate' : function() {
                treeContainer.get(0).hide();
                this.forums.show();
                
            },
            'deactivate' : function() {
                this.forums.hide();
                treeContainer.get(0).show();
            },
            scope: this
        });
        
        this.forums = new Ext.grid.GridPanel({
            store: this.forumList,
            columns: [
                {dataIndex: 'name', flex: 1}
            ],
            viewConfig: {stripeRows: true},
            title: _('Форумы'),
            anchor:'100% 100%',
            border: false,
            stateful: true,
            stateId: 'grid',
            hideHeaders: true,
            
            selModel: {
                mode: 'SINGLE'
            },
            
            loadMask: true,
            tbar: [
                {
                    id: 'tb_forums_reload',
                    iconCls:'icon-reload',
                    tooltip:_('Обновить'),
                    handler: function () { 
                        this.forumList.reload();
                    },
                    scope: this
                },'-',{
                    icon: '/plugins/forum/images/replace_icon.gif',
                    tooltip:_('Автозамена'),
                    handler: function () { 
                        if (!this.replaceWin)
                            this.replaceWin = Ext.create('Plugin.forum.ReplaceWindow');
                        this.replaceWin.show(); 
                    },
                    scope: this
                }, '-', 
                {
                    id: 'tb_forum_add',
                    iconCls:'icon-new',
                    tooltip:_('Новый форум'),
                    handler: function () { 
                        this.getPropertiesWin().show(0);
                    },
                    scope: this
                },{
                    id: 'tb_forum_prop',
                    disabled: true,
                    iconCls:'icon-props',
                    tooltip:_('Свойства'),
                    handler: function () { 
                        this.getPropertiesWin().show(this.forums.getSelectionModel().getSelection()[0].getId()); 
                    },
                    scope: this
                },{
                    id: 'tb_forum_del',
                    iconCls:'icon-delete',
                    disabled: true,
                    tooltip:_('Удалить форум'),
                    handler: function () { 
                        Ext.MessageBox.confirm(_('Подтверждение'), _('Вы действительно хотите удалить форум?'), function(btn) {
                            if (btn == 'yes') {
                                Ext.Ajax.request({
                                   url: '/plugins/forum/scripts/action_forums.php',
                                   params: { 
                                    action: 'delete_forum', 
                                    id: this.forums.getSelectionModel().getSelection()[0].getId() 
                                   },
                                   success: function() {
                                        this.forumList.reload();
                                   },
                                   scope: this
                                });
                            }
                        }, this);
                    },
                    scope: this
                }
            ]    
        });
        
        treeContainer.add(this.forums);
        treeContainer.doLayout(); 
        
        this.bbar = new Ext.PagingToolbar({
            store: this.store
        });
        
        this.fireEvent('activate');
        
        this.forumList.load({
            callback: function() {
                this.forums.getSelectionModel().select(0);
                this.forumChanged();
                this.forums.getSelectionModel().addListener('selectionchange', this.forumChanged, this);
            },
            scope: this
        });

        this.callParent();
        
        this.getView().getRowClass = function(record, rowIndex, rowParams, store){ 
             if (record.get('disabled')) return 'disabled';
        }         
    },

    border: false,
    loadMask: true,
  
    forumId: 0,       // текущий раздел
    allow_own: false,   // право работать со своими материалами
    allow_all: false,   // право работать со всеми материалами
    allow_pub: false,   // право на публикацию материалов
    mat_type: 0,
    path: [],
                   
    edit: function(idcat, id) {
    
        if (!this.editWindow) {
            this.editWindow = Ext.create('Cetera.window.MaterialEdit', { 
                listeners: {
                    hide: {
                        fn: function(win){
                            win.remove(win.content, true);
                            this.store.load();
                        },
                        scope: this
                    }
                }
            });
        }
        
        var mat_type = this.mat_type;
        var win = this.editWindow;
                
        Ext.Loader.loadScript({
            url: '/cms/include/ui_material_edit.php?type='+this.mat_type+'&idcat='+idcat+'&id='+id+'&height='+this.editWindow.height,
            onLoad: function() { 
                var cc = safe_new('MaterialEditor'+mat_type, {win: win});
                if (cc) cc.show();
            }
        });

    }, 
      
    deleteMat: function() {
        Ext.MessageBox.confirm(_('Удалить сообщение'), _('Вы уверены?'), function(btn) {
            if (btn == 'yes') this.call('delete');
        }, this);
    },
      
    call: function(action, cat) {
        Ext.Ajax.request({
            url: '/plugins/forum/scripts/action_forums.php',
            params: { 
                action: action, 
                id: this.forumId, 
                'sel[]': this.getSelected(),
                cat: cat
            },
            scope: this,
            success: function(resp) {
                this.store.load();
            }
        });
    },
    
    getSelected: function() {
        var a = this.getSelectionModel().getSelection();
        ret = [];
        for (var i=0; i<a.length; i++) ret[i] = a[i].getId();
        return ret;
    },  
    
    reload: function() {
        this.store.proxy.extraParams.id = this.forumId;
        this.store.load({params:{
            start: 0,
            limit: Cetera.defaultPageSize
        }});
    },
       
    forumChanged: function() {      
        if (this.forums.getSelectionModel().getSelection()[0]) {
            this.forumId = this.forums.getSelectionModel().getSelection()[0].getId();  
            Ext.Ajax.request({
                url: '/cms/include/action_materials.php',
                params: { action: 'permissions', id: this.forumId },
                scope: this,
                success: function(resp) {
                    var obj = Ext.decode(resp.responseText);
                    this.allow_own = obj.right[0];
                    this.allow_all = obj.right[1];
                    this.allow_pub = obj.right[2];
                    this.mat_type  = obj.right[4];
                    
                    Ext.getCmp('tb_forum_new').setDisabled(!this.allow_own && !this.allow_all);
                    this.reload();
                }
            });
            Ext.getCmp('tb_forum_prop').setDisabled(false);
            Ext.getCmp('tb_forum_del').setDisabled(false);
            this.enable();
        } else {
            this.store.removeAll();
            Ext.getCmp('tb_forum_prop').setDisabled(true);
            Ext.getCmp('tb_forum_del').setDisabled(true);
            this.disable();
        }
    },
    
    showAnswers: function() {
        var sel = this.getSelectionModel().getSelection()[0];
        var idx = this.path.length;
        var but = this.breadCrumb.addButton({
            text: '> ' + sel.getComponent('name'),
            handler: function() { this.back(idx); },
            scope: this
        });
        this.path[idx] = {
            button: but,
            id: this.store.proxy.extraParams.parent
        };
        this.store.proxy.extraParams.parent = sel.id;
        if (idx == 0) this.breadCrumb.show();
        this.doLayout();
        this.reload();
    },
    
    back: function(idx) {
        this.store.proxy.extraParams.parent = this.path[idx].id;
        for (var i = idx; i < this.path.length; i++)
            this.breadCrumb.remove(this.path[i].button);
        this.path.length = idx;
        if (idx == 0) this.breadCrumb.hide();
        this.doLayout();
        this.reload();
    },
    
    getPropertiesWin: function() {
        if (!this.propertiesWin) {
            this.propertiesWin = Ext.create('Plugin.forum.PropertiesWindow')
            this.propertiesWin.on('forumChanged', function(id, name) {
                if (id)
                    this.forumList.getById(id).set('name', name);
                    else this.forumList.reload();
            }, this);
        }
        return this.propertiesWin;
    }
});