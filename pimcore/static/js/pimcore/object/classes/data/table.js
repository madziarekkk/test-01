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

pimcore.registerNS("pimcore.object.classes.data.table");
pimcore.object.classes.data.table = Class.create(pimcore.object.classes.data.data, {

    type: "table",

    initialize: function (treeNode, initData) {
        this.type = "table";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible","visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getGroup: function () {
            return "structured";
    },

    getTypeName: function () {
        return t("table");
    },

    getIconClass: function () {
        return "pimcore_icon_table";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("rows"),
                name: "rows",
                value: this.datax.rows
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("cols"),
                name: "cols",
                value: this.datax.cols
            },
            {
                xtype: "textarea",
                fieldLabel: t("data"),
                name: "data",
                width: 300,
                height: 300,
                value: this.datax.data
            }
        ]);

        return this.layout;
    }

});
