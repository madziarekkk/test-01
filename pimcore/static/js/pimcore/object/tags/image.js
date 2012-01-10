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

pimcore.registerNS("pimcore.object.tags.image");
pimcore.object.tags.image = Class.create(pimcore.object.tags.abstract, {

    type: "image",
    dirty: false,

    initialize: function (data, fieldConfig) {
        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig: function(field) {

        return {header: ts(field.label), width: 100, sortable: false, dataIndex: field.key, renderer: function (key, value, metaData, record) {
            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
            }

            if (value && value.id) {
                return '<img src="/admin/asset/get-image-thumbnail/id/' + value.id + '/width/88/height/88/frame/true" />';
            }
        }.bind(this, field.key)};
    },    

    getLayoutEdit: function () {

        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 100;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 100;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            tbar: [{
                xtype: "tbspacer",
                width: 20,
                height: 16,
                cls: "pimcore_icon_droptarget"
            },
            {
                xtype: "tbtext",
                text: "<b>" + this.fieldConfig.title + "</b>"
            },"->",{
                xtype: "button",
                iconCls: "pimcore_icon_edit",
                handler: this.openImage.bind(this)
            }, {
                xtype: "button",
                iconCls: "pimcore_icon_delete",
                handler: this.empty.bind(this)
            },{
                xtype: "button",
                iconCls: "pimcore_icon_search",
                handler: this.openSearchEditor.bind(this)
            }],
            cls: "object_field",
            bodyCssClass: "pimcore_droptarget_image"
        };

        this.component = new Ext.Panel(conf);


        this.component.on("render", function (el) {

            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                ddGroup: "element",
                getTargetFromEvent: function(e) {
                    return this.reference.component.getEl();
                },

                onNodeOver : function(target, dd, e, data) {

                    if (data.node.attributes.type == "image") {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    } else {
                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    }

                },

                onNodeDrop : this.onNodeDrop.bind(this)
            });


            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

            if (this.data) {
                this.updateImage();
            }

        }.bind(this));


        return this.component;
    },


    getLayoutShow: function () {

        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 100;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 100;
        }

        var conf = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            title: this.fieldConfig.title,
            cls: "object_field"
        };

        this.component = new Ext.Panel(conf);

        this.component.on("render", function (el) {
            if (this.data) {
                this.updateImage();
            }
        }.bind(this));

        return this.component;
    },

    onNodeDrop: function (target, dd, e, data) {
        if (data.node.attributes.type == "image") {
            if(this.data != data.node.attributes.id) {
                this.dirty = true;
            }
            this.data = data.node.attributes.id;

            this.updateImage();
            return true;
        }
    },
    
    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["asset"],
            subtype: {
                asset: ["image"]
            }
        });
    },
    
    addDataFromSelector: function (item) {
        if (item) {
            if(this.data != item.id) {
                this.dirty = true;
            }
            
            this.data = item.id;

            this.updateImage();
            return true;
        }
    },
    
    openImage: function () {
        if(this.data) {
            pimcore.helpers.openAsset(this.data, "image");
        }
    },
    
    updateImage: function () {
        var path = "/admin/asset/get-image-thumbnail/id/" + this.data + "/width/" + (this.fieldConfig.width - 20) + "/height/" + (this.fieldConfig.height - 20) + "/contain/true";
        this.getBody().setStyle({
            backgroundImage: "url(" + path + ")",
            BackgroundPosition: "center center",

        });
        this.getBody().repaint();
    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html (only in configure)
        var bodyId = Ext.get(this.component.getEl().dom).query(".x-panel-body")[0].getAttribute("id");
        return Ext.get(bodyId);
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('empty'),
            iconCls: "pimcore_icon_delete",
            handler: function (item) {
                item.parentMenu.destroy();

                this.empty();
            }.bind(this)
        }));

        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "pimcore_icon_open",
            handler: function (item) {
                item.parentMenu.destroy();

                this.openImage();
            }.bind(this)
        }));
        
        menu.add(new Ext.menu.Item({
            text: t('search'),
            iconCls: "pimcore_icon_search",
            handler: function (item) {
                item.parentMenu.destroy();
                this.openSearchEditor();
            }.bind(this)
        }));
        
        menu.showAt(e.getXY());

        e.stopEvent();
    },
    
    empty: function () {
        this.data = null;
        this.getBody().setStyle({
            backgroundImage: "url(/pimcore/static/img/icon/drop-40.png)"
        });
        this.dirty = true;
        this.getBody().repaint();
    },
    
    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {
        if (this.getValue()) {
            return false;
        }
        return true;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        return this.dirty;
    }
});