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

pimcore.registerNS("pimcore.object.klass");
pimcore.object.klass = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_classes",
                title: t("classes"),
                iconCls: "pimcore_icon_classes",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getClassTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_classes");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("classes");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getClassTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                id: "pimcore_panel_classes_tree",
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                border: true,
                width: 200,
                split: true,
                root: {
                    nodeType: 'async',
                    id: '0'
                },
                loader: new Ext.tree.TreeLoader({
                    dataUrl: '/admin/class/get-tree/',
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: this.getTreeNodeListeners(),
                        reference: this,
                        allowDrop: false,
                        allowChildren: false,
                        isTarget: false,
                        iconCls: "pimcore_icon_class",
                        leaf: true
                    }
                }),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("add_class"),
                            iconCls: "pimcore_icon_class_add",
                            handler: this.addClass.bind(this)
                        }
                    ]
                }
            });

            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: "center"
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'click' : this.onTreeNodeClick,
            "contextmenu": this.onTreeNodeContextmenu
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function () {

        if(Ext.getCmp("pimcore_class_editor_panel_" + this.id)) {
            this.attributes.reference.getEditPanel().activate(Ext.getCmp("pimcore_class_editor_panel_" + this.id));
            return;
        }

        if (this.id > 0) {
            Ext.Ajax.request({
                url: "/admin/class/get",
                params: {
                    id: this.id
                },
                success: this.attributes.reference.addClassPanel.bind(this.attributes.reference)
            });
        }
    },

    addClassPanel: function (response) {

        var data = Ext.decode(response.responseText);

        /*if (this.classPanel) {
            this.getEditPanel().removeAll();
            delete this.classPanel;
        }*/

        var classPanel = new pimcore.object.classes.klass(data, this);
        pimcore.layout.refresh();
    },

    onTreeNodeContextmenu: function () {
        this.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_class_delete",
            handler: this.attributes.reference.deleteClass.bind(this)
        }));

        menu.show(this.ui.getAnchor());
    },

    addClass: function () {
        Ext.MessageBox.prompt(t('add_class'), t('enter_the_name_of_the_new_class'), this.addClassComplete.bind(this), null, null, "");
    },

    addClassComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z]+/);
        var forbiddennames = ["abstract","class","data","folder","list","permissions","resource","concrete","interface", "service", "fieldcollection", "localizedfield", "objectbrick"];

        if (button == "ok" && value.length > 2 && regresult == value && !in_array(value, forbiddennames)) {
            Ext.Ajax.request({
                url: "/admin/class/add",
                params: {
                    name: value
                },
                success: function () {
                    this.tree.getRootNode().reload();

                    // update object type store
                    pimcore.globalmanager.get("object_types_store").reload();
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('add_class'), t('problem_creating_new_class'));
        }
    },

    deleteClass: function () {
        Ext.Ajax.request({
            url: "/admin/class/delete",
            params: {
                id: this.id
            }
        });

        this.attributes.reference.getEditPanel().removeAll();
        this.remove();

        // refresh the object tree
        pimcore.globalmanager.get("layout_object_tree").tree.getRootNode().reload();

        // update object type store
        pimcore.globalmanager.get("object_types_store").reload();
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").activate("pimcore_classes");
    }

});