/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.folder");
pimcore.object.folder = Class.create(pimcore.object.abstract, {

    type: "folder",

    initialize: function(id) {

        pimcore.plugin.broker.fireEvent("preOpenObject", this, "folder");

        this.addLoadingPanel();
        this.id = intval(id);
        this.getData();
    },

    init: function () {

        this.search = new pimcore.object.search(this);

        if (this.isAllowed("properties")) {
            this.properties = new pimcore.element.properties(this, "object");
        }
        if (this.isAllowed("permissions")) {
            this.permissions = new pimcore.object.permissions(this);
        }

        //this.dependencies = new pimcore.element.dependencies(this, "object");
    },


    getData: function () {
        Ext.Ajax.request({
            url: "/admin/object/get-folder/",
            params: {id: this.id},
            success: this.getDataComplete.bind(this)
        });
    },

    getDataComplete: function (response) {
        try {
            this.data = Ext.decode(response.responseText);

            if (typeof this.data.editlock == "object") {
                pimcore.helpers.lockManager(this.id, "object", "folder", this.data);
                throw "object is locked";
            }

            this.init();
            this.addTab();
            
            this.startChangeDetector();
        }
        catch (e) {
            pimcore.helpers.closeObject(this.id);
        }
    },


    addTab: function () {

        var tabTitle = this.data.general.o_key;
        if (this.id == 1) {
            tabTitle = "home";
        }

        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "object_" + this.id;

        this.tab = new Ext.Panel({
            id: tabId,
            title: tabTitle,
            closable:true,
            layout: "border",
            items: [
                this.getLayoutToolbar(),
                this.getTabPanel()
            ],
            iconCls: "pimcore_icon_folder",
            object: this
        });

        this.tab.on("beforedestroy", function () {
            Ext.Ajax.request({
                url: "/admin/misc/unlock-element",
                params: {
                    id: this.id,
                    type: "object"
                }
            });
        }.bind(this));

        // remove this instance when the panel is closed
        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("object_" + this.id);

        }.bind(this));

        this.tab.on("activate", function () {
            this.tab.doLayout();
            pimcore.layout.refresh();
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.activate(tabId);
            pimcore.plugin.broker.fireEvent("postOpenObject", this, "folder");
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);

        // recalculate the layout
        pimcore.layout.refresh();
    },

    activate: function () {
        var tabId = "object_" + this.id;
        this.tabPanel.activate(tabId);
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t('save'),
                iconCls: "pimcore_icon_publish_medium",
                scale: "medium",
                handler: this.save.bind(this)
            });

            /*this.toolbarButtons.remove = new Ext.Button({
             text: t('delete'),
             iconCls: "pimcore_icon_delete_medium",
             scale: "medium",
             handler: this.remove.bind(this)
             });*/

            var buttons = [];

            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }

            /*if(this.isAllowed("delete")) {
             buttons.push(this.toolbarButtons.remove);
             }*/

            buttons.push("-");
            buttons.push({
                text: this.data.general.o_id,
                xtype: 'tbtext'
            });

            this.toolbar = new Ext.Toolbar({
                id: "object_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "document_toolbar",
                items: buttons
            });
        }

        return this.toolbar;
    },

    getTabPanel: function () {

        var items = [];

        var search = this.search.getLayout();
        if (search) {
            items.push(search);
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("permissions")) {
            items.push(this.permissions.getLayout());
        }


        var tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return tabbar;
    },
    
    getSaveData: function () {
        var data = {};

        data.id = this.id;

        // properties
        try {
            data.properties = Ext.encode(this.properties.getValues());
        }
        catch (e) {
            //console.log(e);
        }


        try {
            data.general = Ext.encode(this.data.general);
        }
        catch (e) {
            //console.log(e);
        }
        
        try {
            data.gridconfig = Ext.encode(this.search.getGridConfig());
            data.class_id = this.search.currentClass;
        } catch (e) {
            //console.log(e);
        }
        
        return data;
    },
    
    save : function (task) {

        
        
        Ext.Ajax.request({
            url: '/admin/object/save-folder/task/' + task,
            method: "post",
            params: this.getSaveData(),
            success: function (response) {
                try{
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        pimcore.helpers.showNotification(t("success"), t("your_object_has_been_saved"), "success");
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("error_saving_object"), "error",t(rdata.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("error_saving_object"), "error");    
                }
            }
        });
        
        this.resetChanges();
    },


    remove: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.remove(this.tab);

        var objectNode = pimcore.globalmanager.get("layout_object_tree").tree.getNodeById(this.id)
        var f = pimcore.globalmanager.get("layout_object_tree").remove.bind(objectNode);
        f();
    },

    isAllowed : function (key) {
        return this.data.userPermissions[key];
    }

});

