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

pimcore.registerNS("pimcore.object.classes.data.image");
pimcore.object.classes.data.image = Class.create(pimcore.object.classes.data.data, {

    type: "image",

    initialize: function (treeNode, initData) {
        this.type = "image";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("image");
    },

    getIconClass: function () {
        return "pimcore_icon_image";
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
            }
        ]);

        return this.layout;
    }

});
