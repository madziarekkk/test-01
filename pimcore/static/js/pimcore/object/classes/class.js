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

pimcore.registerNS("pimcore.object.classes.klass");
pimcore.object.classes.klass = Class.create({

    disallowedDataTypes: [],
    uploadUrl: '/admin/class/import-class',
    exportUrl: "/admin/class/export-class",



    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;

        this.addLayout();
        this.initLayoutFields();
    },

    getUploadUrl: function(){
        return this.uploadUrl + '?pimcore_admin_sid=' + pimcore.settings.sessionId + "&id=" + this.getId();
    },

    getExportUrl: function() {
        return  this.exportUrl + "?id=" + this.getId();
    },

    addLayout: function () {

        this.editpanel = new Ext.Panel({
            region: "center",
            bodyStyle: "padding: 20px;",
            autoScroll: true
        });

        this.tree = new Ext.tree.TreePanel({
            xtype: "treepanel",
            region: "center",
            enableDD: true,
            autoScroll: true,
            root: {
                id: "0",
                root: true,
                text: t("base"),
                reference: this,
                leaf: true,
                isTarget: true,
                listeners: this.getTreeNodeListeners()
            }
        });

        var displayId = this.data.key ? this.data.key : this.data.id; // because the field-collections use that also

        var panelButtons = [];

        panelButtons.push({
            text: t("import"),
            iconCls: "pimcore_icon_class_import",
            handler: this.upload.bind(this)
        });

        panelButtons.push({
            text: t("export"),
            iconCls: "pimcore_icon_class_export",
            handler: function() {
                location.href = this.getExportUrl();
            }.bind(this)
        });


        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        });


        var name = "";
        if(this.data.name) {
            name = this.data.name + " ( ID: " + displayId + ")";
        } else {
            name = "ID: " + displayId;
        }

        this.panel = new Ext.Panel({
            border: false,
            layout: "border",
            closable: true,
            title: name,
            id: "pimcore_class_editor_panel_" + this.getId(),
            items: [
                {
                    region: "west",
                    title: t("layout"),
                    layout: "border",
                    width: 300,
                    split: true,
                    items: [this.tree]
                },
                this.editpanel
            ],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);

        this.editpanel.add(this.getRootPanel());
        this.setCurrentNode("root");
        this.parentPanel.getEditPanel().activate(this.panel);

        pimcore.layout.refresh();
    },

    getId: function(){
        return  this.data.id;
    },

    upload: function() {


        this.uploadWindow = new Ext.Window({
            layout: 'fit',
            title: t('import_class_defintion'),
            closeAction: 'close',
            width:400,
            height:400,
            modal: true
        });

        var uploadPanel = new Ext.ux.SwfUploadPanel({ 
            border: false,
            upload_url: this.getUploadUrl(),
            debug: false,
            post_params: {
                id: this.data.id
            },
            flash_url: "/pimcore/static/js/lib/ext-plugins/SwfUploadPanel/swfupload.swf",
            single_select: false,
            file_queue_limit: 1,
            file_types: "*.xml",
            single_file_select: true,
            confirm_delete: false,
            remove_completed: true,
            listeners: {
                "fileUploadComplete": function (win) {

                    Ext.Ajax.request({
                        url: "/admin/class/get",
                        params: {
                            id: this.data.id
                        },
                        success: function(win, response) {
                            this.data = Ext.decode(response.responseText);
                            this.parentPanel.getEditPanel().removeAll();
                            this.addLayout();
                            this.initLayoutFields();
                            pimcore.layout.refresh();
                            win.hide();
                        }.bind(this, win)
                    });


                }.bind(this, this.uploadWindow)
            }
        });

        this.uploadWindow.add(uploadPanel);


        this.uploadWindow.show();
        this.uploadWindow.setWidth(401);
        this.uploadWindow.doLayout();


    },

    reload: function(response) {
        
    },

    initLayoutFields: function () {

        if (this.data.layoutDefinitions) {
            if (this.data.layoutDefinitions.childs) {
                for (var i = 0; i < this.data.layoutDefinitions.childs.length; i++) {
                    this.tree.getRootNode().appendChild(this.recursiveAddNode(this.data.layoutDefinitions.childs[i], this.tree.getRootNode()));
                }
                this.tree.getRootNode().expand();
            }
        }
    },

    recursiveAddNode: function (con, scope) {

        var fn = null;
        var newNode = null;

        if (con.datatype == "layout") {
            fn = this.addLayoutChild.bind(scope, con.fieldtype, con);
        }
        else if (con.datatype == "data") {
            fn = this.addDataChild.bind(scope, con.fieldtype, con);
        }

        newNode = fn();

        if (con.childs) {
            for (var i = 0; i < con.childs.length; i++) {
                this.recursiveAddNode(con.childs[i], newNode);
            }
        }

        return newNode;
    },


    getTreeNodeListeners: function () {

        var listeners = {
            "click" : this.onTreeNodeClick,
            "contextmenu": this.onTreeNodeContextmenu
        };
        return listeners;
    },



    onTreeNodeClick: function () {

        this.attributes.reference.saveCurrentNode();
        this.attributes.reference.editpanel.removeAll();

        if (this.attributes.object) {

            if (this.attributes.object.datax.locked) {
                return;
            }

            this.attributes.reference.editpanel.add(this.attributes.object.getLayout());
            this.attributes.reference.setCurrentNode(this.attributes.object);
        }

        if (this.attributes.root) {
            this.attributes.reference.editpanel.add(this.attributes.reference.getRootPanel());
            this.attributes.reference.setCurrentNode("root");
        }

        this.attributes.reference.editpanel.doLayout();
    },

    onTreeNodeContextmenu: function () {
        this.select();

        var menu = new Ext.menu.Menu();

        // specify which childs a layout can have
        // the child-type "data" is a placehoder for all data components
        var allowedTypes = {
            accordion: ["panel","region","tabpanel","text"],
            fieldset: ["data","text"],
            panel: ["data","region","tabpanel","button","accordion","fieldset","panel","text","html"],
            region: ["panel","accordion","tabpanel","text","localizedfields"],
            tabpanel: ["panel", "region", "accordion","text","localizedfields"],
            button: [],
            text: [],
            root: ["panel","region","tabpanel","accordion","text"],
            localizedfields: ["checkbox","select","date","datetime","time","image","input","link","numeric","slider","table","wysiwyg","textarea","panel","tabpanel","accordion","fieldset","text","html","region","multiselect", "countrymultiselect","languagemultiselect","objects","multihref","href","hotspotimage","geopoint","geobounds","geopolygon","structuredTable"]
        };

        var parentType = "root";

        if (this.attributes.object) {
            parentType = this.attributes.object.type;
        }

        var childsAllowed = false;
        if (allowedTypes[parentType] && allowedTypes[parentType].length > 0) {
            childsAllowed = true;
        }

        if (childsAllowed) {
            // get available layouts
            var layoutMenu = [];
            var layouts = Object.keys(pimcore.object.classes.layout);

            for (var i = 0; i < layouts.length; i++) {
                if (layouts[i] != "layout") {
                    if (in_array(layouts[i], allowedTypes[parentType])) {
                        layoutMenu.push({
                            text: pimcore.object.classes.layout[layouts[i]].prototype.getTypeName(),
                            iconCls: pimcore.object.classes.layout[layouts[i]].prototype.getIconClass(),
                            handler: this.attributes.reference.addLayoutChild.bind(this, layouts[i])
                        });
                    }

                }
            }

            // get available data types
            var dataMenu = [];
            var dataComps = Object.keys(pimcore.object.classes.data);

            var parentRestrictions;
            var groups = new Array();
            var groupNames = ["text","numeric","date","select","relation","structured","geo","other"];
            for (var i = 0; i < dataComps.length; i++) {

                // check for disallowed types
                if (in_array(dataComps[i], this.attributes.reference.disallowedDataTypes)) {
                    continue;
                }

                if (dataComps[i] != "data") { // class data is an abstract class => disallow
                    if (in_array("data", allowedTypes[parentType]) || in_array(dataComps[i], allowedTypes[parentType])) {

                        // check for restrictions from a parent field (eg. localized fields)
                        if(in_array("data", allowedTypes[parentType])) {
                            parentRestrictions = this.attributes.reference.getRestrictionsFromParent(this);
                            if(parentRestrictions != null) {
                                if(!in_array(dataComps[i], allowedTypes[parentRestrictions])) {
                                    continue;
                                }
                            }
                        }

                        var group = pimcore.object.classes.data[dataComps[i]].prototype.getGroup();
                        if (!groups[group]) {
                            if (!in_array(group, groupNames)) {
                                groupNames.push(group);
                            }
                            groups[group] = new Array();
                        }
                        groups[group].push({
                            text: pimcore.object.classes.data[dataComps[i]].prototype.getTypeName(),
                            iconCls: pimcore.object.classes.data[dataComps[i]].prototype.getIconClass(),
                            handler: this.attributes.reference.addDataChild.bind(this, dataComps[i])
                        });
                    }
                }
            }

            for (i = 0; i < groupNames.length; i++) {
                if (groups[groupNames[i]] && groups[groupNames[i]].length > 0) {
                    dataMenu.push(new Ext.menu.Item({
                        text: t(groupNames[i]),
                        iconCls: "pimcore_icon_data_group_" + groupNames[i],
                        hideOnClick: false,
                        menu: groups[groupNames[i]]
                    }));
                }
            }

            if (layoutMenu.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('add_layout_component'),
                    iconCls: "pimcore_icon_add",
                    hideOnClick: false,
                    menu: layoutMenu
                }));
            }

            if (dataMenu.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('add_data_component'),
                    iconCls: "pimcore_icon_add",
                    hideOnClick: false,
                    menu: dataMenu
                }));
            }
        }

        var deleteAllowed = true;

        if (this.attributes.object) {
            if (this.attributes.object.datax.locked) {
                deleteAllowed = false;
            }
        }

        if (this.id != 0 && deleteAllowed) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: this.attributes.reference.removeChild.bind(this)
            }));
        }

        menu.show(this.ui.getAnchor());
    },

    getRestrictionsFromParent: function (node) {
        if(node.attributes.object.type == "localizedfields") {
            return "localizedfields";
        } else {
            if(node.parentNode && node.parentNode.getDepth() > 0) {
                var parentType = this.getRestrictionsFromParent(node.parentNode);
                if(parentType != null) {
                    return parentType;
                }
            }
        }

        return null;
    },

    setCurrentNode: function (cn) {
        this.currentNode = cn;
    },

    saveCurrentNode: function () {
        if (this.currentNode) {
            if (this.currentNode != "root") {
                this.currentNode.applyData();
            }
            else {
                // save root node data
                var items = this.rootPanel.findBy(function() {
                    return true;
                });

                for (var i = 0; i < items.length; i++) {
                    if (typeof items[i].getValue == "function") {
                        this.data[items[i].name] = items[i].getValue();
                    }
                }
            }
        }
    },

    getRootPanel: function () {
        this.allowInheritance = new Ext.form.Checkbox({
            xtype: "checkbox",
            fieldLabel: t("allow_inherit"),
            name: "allowInherit",
            checked: this.data.allowInherit,
            listeners: {
                "check": function(field, checked) {
                    if(checked == true) {
                        this.allowVariants.setDisabled(false);
                    } else {
                        this.allowVariants.setValue(false);
                        this.allowVariants.setDisabled(true);
                    }
                    console.log("blaaa");
                }.bind(this)
            }
        });


        this.allowVariants = new Ext.form.Checkbox({
            xtype: "checkbox",
            fieldLabel: t("allow_variants"),
            name: "allowVariants",
            checked: this.data.allowVariants,
            disabled: !this.data.allowInherit
        });



        this.rootPanel = new Ext.form.FormPanel({
            title: t("basic_configuration"),
            bodyStyle: "padding: 10px;",
            layout: "pimcoreform",
            labelWidth: 200,
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "name",
                    width: 300,
                    value: this.data.name
                },
                this.allowInheritance,
                this.allowVariants,
                {
                    xtype: "textfield",
                    fieldLabel: t("parent_class"),
                    name: "parentClass",
                    width: 400,
                    value: this.data.parentClass
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("icon"),
                    name: "icon",
                    width: 400,
                    value: this.data.icon,
                    style: "padding-right: 30px;",
                    enableKeyEvents: true,
                    listeners: {
                        "keyup": function (el) {
                            el.getEl().applyStyles("background:url(" + el.getValue() + ") right center no-repeat;");
                        },
                        "afterrender": function (el) {
                            el.getEl().applyStyles("background:url(" + el.getValue() + ") right center no-repeat;");
                        }
                    }
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("preview_url"),
                    name: "previewUrl",
                    width: 400,
                    value: this.data.previewUrl
                },
                {
                    xtype: "displayfield",
                    hideLabel: true,
                    width: 600,
                    value: "<b>" + t('visibility_of_system_properties') + "</b>",
                    cls: "pimcore_extra_label_headline"
                },
                {
                    xtype: "checkbox",
                    fieldLabel: "ID (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.id",
                    checked: this.data.propertyVisibility.grid.id
                },
                {
                    xtype: "checkbox",
                    fieldLabel: "ID (" + t("search") + ")",
                    name: "propertyVisibility.search.id",
                    checked: this.data.propertyVisibility.search.id
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("path") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.path",
                    checked: this.data.propertyVisibility.grid.path
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("path") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.path",
                    checked: this.data.propertyVisibility.search.path
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("published") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.published",
                    checked: this.data.propertyVisibility.grid.published
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("published") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.published",
                    checked: this.data.propertyVisibility.search.published
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("modificationDate") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.modificationDate",
                    checked: this.data.propertyVisibility.grid.modificationDate
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("modificationDate") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.modificationDate",
                    checked: this.data.propertyVisibility.search.modificationDate
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("creationDate") + " (" + t("gridview") + ")",
                    name: "propertyVisibility.grid.creationDate",
                    checked: this.data.propertyVisibility.grid.creationDate
                },
                {
                    xtype: "checkbox",
                    fieldLabel: t("creationDate") + " (" + t("search") + ")",
                    name: "propertyVisibility.search.creationDate",
                    checked: this.data.propertyVisibility.search.creationDate
                }
            ]
        });

        return this.rootPanel;
    },

    addLayoutChild: function (type, initData) {

        var nodeLabel = t(type);

        if (initData) {
            if (initData.name) {
                nodeLabel = initData.name;
            }
        }
        var newNode = new Ext.tree.TreeNode({
            type: "layout",
            reference: this.attributes.reference,
            draggable: true,
            iconCls: "pimcore_icon_" + type,
            text: nodeLabel,
            listeners: this.attributes.reference.getTreeNodeListeners()
        });
        newNode.attributes.object = new pimcore.object.classes.layout[type](newNode, initData);

        this.appendChild(newNode);

        this.renderIndent();
        this.expand();

        return newNode;
    },

    addDataChild: function (type, initData) {

        var nodeLabel = t(type);

        if (initData) {
            if (initData.name) {
                nodeLabel = initData.name;
            }
        }

        var isLeaf = true;

        // localizedfields can be a drop target
        if(type == "localizedfields") {
            isLeaf = false;
        }

        var newNode = new Ext.tree.TreeNode({
            text: nodeLabel,
            type: "data",
            reference: this.attributes.reference,
            leaf: isLeaf,
            iconCls: "pimcore_icon_" + type,
            listeners: this.attributes.reference.getTreeNodeListeners()
        });

        newNode.attributes.object = new pimcore.object.classes.data[type](newNode, initData);

        this.appendChild(newNode);

        this.renderIndent();
        this.expand();

        return newNode;
    },

    removeChild: function () {
        if (this.id != 0) {
            if (this.attributes.reference.currentNode == this.attributes.object) {
                this.currentNode = null;
                var f = this.attributes.reference.onTreeNodeClick.bind(this.attributes.reference.tree.getRootNode());
                f();
            }
            this.remove();
        }
    },

    getNodeData: function (node) {

        var data = {};

        if (node.attributes.object) {
            if (typeof node.attributes.object.getData == "function") {
                data = node.attributes.object.getData();

                data.name = trim(data.name);

                // field specific validation
                var fieldValidation = true;
                if(typeof node.attributes.object.isValid == "function") {
                    fieldValidation = node.attributes.object.isValid();
                }

                if (fieldValidation && in_arrayi(data.name,this.usedFieldNames) == false) {
                    if(data.datatype == "data") {
                        this.usedFieldNames.push(data.name);
                    }

                    node.getUI().removeClass("tree_node_error");
                }
                else {
                    node.getUI().addClass("tree_node_error");
                    pimcore.helpers.showNotification(t("error"), t("some_fields_cannot_be_saved"), "error");

                    this.getDataSuccess = false;
                    return false;
                }
            }
        }

        data.childs = null;
        if (node.childNodes.length > 0) {
            data.childs = [];

            for (var i = 0; i < node.childNodes.length; i++) {
                data.childs.push(this.getNodeData(node.childNodes[i]));
            }
        }

        return data;
    },

    getData: function () {

        this.getDataSuccess = true;

        this.usedFieldNames = [];

        var rootNode = this.tree.getRootNode();
        var nodeData = this.getNodeData(rootNode);

        return nodeData;
    },

    save: function () {

        this.saveCurrentNode();

        delete this.data.layoutDefinitions;

        var m = Ext.encode(this.getData());
        var n = Ext.encode(this.data);

        if (this.getDataSuccess) {
            Ext.Ajax.request({
                url: "/admin/class/save",
                method: "post",
                params: {
                    configuration: m,
                    values: n,
                    id: this.data.id
                },
                success: this.saveOnComplete.bind(this)
            });
        }
    },

    saveOnComplete: function () {
        this.parentPanel.tree.getRootNode().reload();
        pimcore.globalmanager.get("object_types_store").reload();


        pimcore.helpers.showNotification(t("success"), t("class_saved_successfully"), "success");
    }
});