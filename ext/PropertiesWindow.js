Ext.define('Plugin.forum.PropertiesWindow', {
	
	extend:'Ext.Window',
		
    closeAction: 'hide',
    title: _('Разрешения'),
    width: 450,
    height: 450,
    layout: 'fit',
    modal: true,
    resizable: false,
    layout: 'vbox',
    border: false,
    
    forumId: 0,
    selectedGroup: 0,
    permissions: [],
    
    initComponent : function() {
    
        this.permissions[101] = {name:_('создавать темы'), groups:[]};
		this.permissions[102] = {name:_('отвечать в темах'), groups:[]};
		this.permissions[5]   = {name:_('редактировать свои сообщения'), groups:[]};
		this.permissions[6]   = {name:_('редактировать любые сообщения'), groups:[]};
		this.permissions[103] = {name:_('создаваемые темы премодерируемы'), groups:[]};
		this.permissions[104] = {name:_('создаваемые сообщения премодерируемы'), groups:[]};	
       
        // список разрешений
        this.permGrid = new Ext.grid.GridPanel({
            enableHdMenu     : false,
            enableColumnMove : false,
            enableColumnResize: false,
            store            : new Ext.data.SimpleStore({
                fields: ['id', 'name', 'checked'], data: []
            }),
            columns          : [
            		{id: 'name', header: _('Разрешения'), dataIndex: 'name', flex: 1},
            		{
                    xtype: 'checkcolumn',
                    header: _('Разрешить'),
                    dataIndex: 'checked',
                    width: 65,
                    listeners: {
                        checkchange: function (c, rowIndex, checked, eOpts) {
                            var record = this.permGrid.store.getAt( rowIndex );
                            var groups = this.permissions[record.getId()].groups;
                            if (checked) {
                                groups.push(this.selectedGroup)
                            } else {
                                groups.remove(this.selectedGroup);
                            }
                        }, 
                        scope: this
                    }
                }
        	  ],
            selModel: new Ext.selection.RowModel({
                listeners: { 'beforeselect' : function() { return false; } }
            }),
            height: 155
        });
        
        // список групп
        this.groupsGrid = new Ext.grid.GridPanel({
            loadMask: true,
            store : new Ext.data.JsonStore({
                root: 'rows',
                fields: ['id', 'name'],
                url: 'include/data_groups.php?all=1',
                listeners: {
                    'load': {
                        fn: function() {
                            this.groupsGrid.getSelectionModel().select(0);
                        },
                        scope: this
                    }
                }
            }),
            selModel: new Ext.selection.RowModel({
                listeners: {
                    'select' : {
                        fn: function(sm,r,i) {
                            this.selectedGroup = parseInt(r.data['id']);
                            var a = [];
                            Ext.each(this.permissions, function(item, index) {
                                if (item) a.push([index, item.name, this.selectedGroup==Config.groupAdmin || item.groups.indexOf(this.selectedGroup)>=0]);
                            }, this);
                            this.permGrid.getStore().loadData(a);
                            this.checkPermGrid();
                        },
                        scope: this
                    },
                    'deselect' : {
                        fn: function() {
                            this.checkPermGrid();
                        },
                        scope: this
                    }
                }
            }),
            columns: [
        		{width: 20, renderer: function(v, m) { m.css = 'icon-users'; } },
        		{dataIndex: 'name', flex: 1}
        	],
            listeners: {
                viewready: {
                    fn: function(grid) {
                        grid.store.load();
                    }
                }
            },
            hideHeaders      : true,
            height           : 120
        });
        
        this.tabs = new Ext.TabPanel({
            xtype:'tabpanel',
            activeTab: 0,
            plain:true,
            border: false,
            activeTab: 0,
            bodyStyle:'background: none',
            height: 400,
            defaults:{bodyStyle:'background:none; padding:5px'},
            items: [{
                title:_('Основные'),
                layout: 'form',
                defaults: { anchor: '0' },
                defaultType: 'textfield',
                items: [
                    {
                        fieldLabel: _('Имя'),
                        name: 'name',
                        allowBlank:false
                    }, 
                    {
                        fieldLabel: _('Alias'),
                        name: 'alias',
                        allowBlank:false,
                        regex: /^[\.\-\_A-Z0-9]+$/i
                    },
                    Ext.create('Cetera.field.Folder', {
                        name: 'parentid',
                        fieldLabel:_('Размещение'),
                        allowBlank:false,
                        nolink: 1,
                        rule: Config.permissions.PERM_CAT_ADMIN
                    }),
                    Ext.create('Cetera.field.File', {
                        fieldLabel: _('Картинка'),
                        name: 'pic'
                    }), 
                    {
                        xtype:'htmleditor',
                        name:'describ',
                        fieldLabel:_('Описание'),
                        height: 250
                    }
                ]
            },{
                title: _('Разрешения'),
                id: 'forum_permissions',
                items: [
                    {
                        xtype : 'label',
                        html  : _('Группы')+':',
                        style: 'padding: 3px;'
                    }, 
                    this.groupsGrid, 
                    this.permGrid
                ],
                listeners: {
                    'activate': { fn: function(){
                            this.groupsGrid.getView().refresh();
                    }, scope: this} 
                }
            }]
        });
        
        this.form = new Ext.FormPanel({
            labelWidth: 75,
            border: false,
            width: 438,
            waitMsgTarget: true,
            bodyStyle:'background: none',
            method: 'POST',
            url: '/plugins/forum/scripts/action_forums.php',
            items: this.tabs
        });
        
        this.items = this.form;
        
        this.buttons = [{
            text: _('Ok'),
            scope: this,
            handler: this.submit
        },{
            text: _('Отмена'),
            scope: this,
            handler: function(){ this.hide(); }
        }];
    
        ForumsPropertiesWindow.superclass.initComponent.call(this);
    },
    
    show : function(id) {
        this.form.getForm().reset();
        this.groupsGrid.store.reload();
        this.permGrid.store.removeAll();
        this.tabs.setActiveTab(0);
        
        this.forumId = id;
        if (id > 0) {
            Ext.Ajax.request({
                url: '/plugins/forum/scripts/action_forums.php',
                params: { 
                    action: 'get_forum', 
                    id: this.forumId
                },
                scope: this,
                success: function(resp) {
                    var obj = Ext.decode(resp.responseText);
                    for (var index in obj.data.permissions)
                        this.permissions[parseInt(index)].groups = obj.data.permissions[index];
                    this.form.getForm().setValues(obj.data);
                    var p = this.form.getForm().findField('parentid');
                    p.setDisplayValue(obj.data.parentname);
                    p.path = obj.data.parentpath;
                    this.setTitle(_('Свойства') + ': ' + obj.data.name);
                    ForumsPropertiesWindow.superclass.show.call(this);
                }
            });
        } else {
            for (var index in this.permissions)
                this.permissions[index].groups = [];
            var p = this.form.getForm().findField('parentid');
            p.setDisplayValue('');
            p.path = '';
            this.setTitle(_('Новый форум'));
            ForumsPropertiesWindow.superclass.show.call(this);
        }
    },
    
    checkPermGrid: function() {
        this.permGrid.setDisabled(this.selectedGroup==Config.groupAdmin);
    },
    
    submit: function() {
        var params = {
            action: 'save_forum', 
            id: this.forumId
        };
        Ext.each(this.permissions, function(item, index) {
            if (item) params['permissions['+index+'][]'] = item.groups;
        }, this);
        this.form.getForm().submit({
            params: params,
            waitMsg:_('Подождите...'),
            scope: this,
            success: function(resp) {
                this.fireEvent('forumChanged', this.forumId, this.form.getForm().findField('name').getValue());
                this.hide();
            }
        });
    }
});
