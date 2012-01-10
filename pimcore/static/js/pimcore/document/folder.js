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

pimcore.registerNS("pimcore.document.folder");
pimcore.document.folder = Class.create(pimcore.document.document, {

    type: "folder",

    initialize: function(id) {

        pimcore.plugin.broker.fireEvent("preOpenDocument", this, "folder");

        this.addLoadingPanel();
        this.id = intval(id);
        this.getData();
    },

    init: function () {

        if (this.isAllowed("properties")) {
            this.properties = new pimcore.document.properties(this, "document");
        }
        if (this.isAllowed("permissions")) {
            this.permissions = new pimcore.document.permissions(this);
        }

        this.dependencies = new pimcore.element.dependencies(this, "document");
    },

    getSaveData : function () {
        var parameters = {};

        parameters.id = this.id;

        if (this.isAllowed("properties")) {
            // properties
            try {
                parameters.properties = Ext.encode(this.properties.getValues());
            }
            catch (e) {
                //console.log(e);
            }
        }

        return parameters;
    },

    addTab: function () {

        var tabTitle = this.data.key;
        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");
        var tabId = "document_" + this.id;
        this.tab = new Ext.Panel({
            id: tabId,
            title: tabTitle,
            closable:true,
            layout: "border",
            items: [
                this.getLayoutToolbar(),
                this.getTabPanel()
            ],
            iconCls: "pimcore_icon_" + this.data.type,
            document: this
        });

        this.tab.on("beforedestroy", function () {
            Ext.Ajax.request({
                url: "/admin/misc/unlock-element",
                params: {
                    id: this.data.id,
                    type: "document"
                }
            });
        }.bind(this));

        // remove this instance when the panel is closed
        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove("document_" + this.id);

        }.bind(this));

        this.tab.on("activate", function () {
            this.tab.doLayout();
            pimcore.layout.refresh();
        }.bind(this));

        this.tab.on("afterrender", function (tabId) {
            this.tabPanel.activate(tabId);
            pimcore.plugin.broker.fireEvent("postOpenDocument", this, "folder");
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.tabPanel.add(this.tab);

        // recalculate the layout
        pimcore.layout.refresh();
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
             });
             */

            var buttons = [];

            if (this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }

            /*if(this.isAllowed("delete")) {
             buttons.push(this.toolbarButtons.remove);
             }*/

            buttons.push("-");
            buttons.push({
                text: this.data.id,
                disabled: true
            });

            this.toolbar = new Ext.Toolbar({
                id: "document_toolbar_" + this.id,
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

        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("permissions")) {
            items.push(this.permissions.getLayout());
        }

        items.push(this.dependencies.getLayout());

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
    }
});

