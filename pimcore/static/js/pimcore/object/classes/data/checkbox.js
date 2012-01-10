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

pimcore.registerNS("pimcore.object.classes.data.checkbox");
pimcore.object.classes.data.checkbox = Class.create(pimcore.object.classes.data.data, {

    type: "checkbox",

    initialize: function (treeNode, initData) {
        this.type = "checkbox";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("checkbox");
    },

    getIconClass: function () {
        return "pimcore_icon_checkbox";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "checkbox",
                fieldLabel: t("default_value"),
                name: "defaultValue",
                checked: this.datax.defaultValue
            }
        ]);

        return this.layout;
    }

});
