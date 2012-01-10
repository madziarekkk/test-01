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

pimcore.registerNS("pimcore.asset.image");
pimcore.asset.image = Class.create(pimcore.asset.asset, {

    type: "image",

    initialize: function(id) {

        this.setType("image");

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "image");

        this.addLoadingPanel();
        this.id = intval(id);

        this.properties = new pimcore.element.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "asset");
        this.permissions = new pimcore.asset.permissions(this);
        this.dependencies = new pimcore.element.dependencies(this, "asset");

        this.getData();
    },

    getTabPanel: function () {

        var items = [];

        items.push(this.getDisplayPanel());

        if (this.isAllowed("save") || this.isAllowed("publish")) {
            items.push(this.getEditPanel());
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }
        if (this.isAllowed("settings")) {
            items.push(this.scheduler.getLayout());
        }
        if (this.isAllowed("permissions")) {
            items.push(this.permissions.getLayout());
        }
        items.push(this.dependencies.getLayout());

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    getEditPanel: function () {

        if (!this.editPanel) {

            this.editPanel = new Ext.Panel({
                title: t("edit_image"),
                tbar: [
                    {
                        text: t("simple"),
                        iconCls: "pimcore_icon_image_editor_simple",
                        handler: function () {
                            Ext.get("asset_image_edit_" + this.id).dom.src = this.getEditUrlPixlr("express");
                        }.bind(this)
                    },"-",
                    {
                        text: t("advanced"),
                        iconCls: "pimcore_icon_image_editor_advanced",
                        handler: function () {
                            Ext.get("asset_image_edit_" + this.id).dom.src = this.getEditUrlPixlr("editor");
                        }.bind(this)
                    }
                ],
                html: '<iframe src="' + this.getEditUrlPixlr("express") + '" frameborder="0" id="asset_image_edit_' + this.id + '"></iframe>',
                iconCls: "pimcore_icon_tab_edit"
            });
            this.editPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                Ext.get("asset_image_edit_" + this.id).setStyle({
                    width: width + "px",
                    height: (height - 25) + "px"
                });
            }.bind(this));
        }

        return this.editPanel;
    },

    getEditUrlPixlr: function (type) {
        
        var parts = this.data.filename.split(".");
        var imageType = parts[parts.length-1].toLowerCase();
        var validImageTypes = ["png","jpg","gif"];
        
        if(!in_array(imageType,validImageTypes)) {
            imageType = "png";
        }

        var imageUrl = document.location.protocol + "//" + window.location.hostname + "/admin/asset/get-image-thumbnail/id/" + this.id + "/width/1000/aspectratio/true/pimcore_admin_sid/" + pimcore.settings.sessionId + "/" + this.data.filename;
        var targetUrl = document.location.protocol + "//" + window.location.hostname + "/admin/asset/save-image-pixlr/?pimcore_admin_sid=" + pimcore.settings.sessionId + "&id=" + this.id;
        var editorUrl = "http://www.pixlr.com/" + type + "/?image=" + escape(imageUrl) + "&title=" + this.data.filename + "&locktitle=true&locktarget=true&locktype=" + imageType + "&wmode=transparent&target=" + escape(targetUrl);

        if (type == "editor") {
            editorUrl = editorUrl + "&redirect=false";
        }

        return editorUrl;
    },

    getDisplayPanel: function () {

        if (!this.displayPanel) {

            var date = new Date();
            var dc = date.getTime();

            var details = [];

            var downloadDefaultWidth = 800;
            if(this.data.imageInfo) {
                if(this.data.imageInfo.dimensions && this.data.imageInfo.dimensions.width) {
                    downloadDefaultWidth = intval(this.data.imageInfo.dimensions.width);
                }
            }

            this.downloadBox = new Ext.form.FormPanel({
                title: t("download"),
                bodyStyle: "padding: 10px;",
                layout: "pimcoreform",
                items: [{
                    xtype: "combo",
                    triggerAction: "all",
                    name: "format",
                    fieldLabel: t("format"),
                    store: [["JPEG", "JPEG"],["PNG","PNG"]],
                    mode: "local",
                    value: "JPEG",
                    width: 80
                }, {
                    xtype: "spinnerfield",
                    name: "width",
                    fieldLabel: t("width"),
                    value: downloadDefaultWidth
                },{
                    xtype: "spinnerfield",
                    name: "height",
                    fieldLabel: t("height")
                },{
                    xtype: "spinnerfield",
                    name: "quality",
                    fieldLabel: t("quality"),
                    value: 95
                },{
                    xtype: "checkbox",
                    name: "aspectratio",
                    fieldLabel: t("aspect_ratio"),
                    checked: true
                }],
                buttons: [{
                    text: t("download"),
                    iconCls: "pimcore_icon_download",
                    handler: function () {
                        var config = this.downloadBox.getForm().getFieldValues();
                        location.href = "/admin/asset/get-image-thumbnail/id/" + this.id + "/download/true?config=" + Ext.encode(config);
                    }.bind(this)
                }]
            });
            details.push(this.downloadBox);

            if(this.data.imageInfo) {
                if(this.data.imageInfo.dimensions) {

                    var dimensionPanel = new Ext.grid.PropertyGrid({
                        title: t("dimensions"),
                        source: this.data.imageInfo.dimensions,
                        autoHeight: true,
                        clicksToEdit: 1000,
                        viewConfig : {
                            forceFit: true,
                            scrollOffset: 2
                        }
                    });

                    details.push(dimensionPanel);
                }
                if(this.data.imageInfo.exif) {

                    var exifPanel = new Ext.grid.PropertyGrid({
                        title: t("exif_data"),
                        source: this.data.imageInfo.exif,
                        clicksToEdit: 1000,
                        autoHeight: true
                    });

                    details.push(exifPanel);
                }
            }

            this.displayPanel = new Ext.Panel({
                title: t("view"),
                layout: "border",
                iconCls: "pimcore_icon_tab_view",
                items: [{
                    region: "center",
                    html: '&nbsp;',
                    bodyStyle: "background: url(/admin/asset/get-image-thumbnail/id/" + this.id + "/width/400/aspectratio/true/?_dc=" + dc + ") center center no-repeat;"
                },{
                    title: t("image_details"),
                    region: "east",
                    width: 300,
                    items: details,
                    autoScroll: true
                }]
            });
        }

        return this.displayPanel;
    }
});