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

pimcore.registerNS("pimcore.object.classes.data.languagemultiselect");
pimcore.object.classes.data.languagemultiselect = Class.create(pimcore.object.classes.data.multiselect, {

    type: "languagemultiselect",

    initialize: function (treeNode, initData) {
        this.type = "languagemultiselect";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("languagemultiselect");
    },

    getIconClass: function () {
        return "pimcore_icon_languagemultiselect";
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
            },{
                xtype: "checkbox",
                labelStyle: "width: 350px",
                fieldLabel: t("only_configured_languages"),
                name: "onlySystemLanguages",
                checked: this.datax.onlySystemLanguages
            }
        ]);

        return this.layout;
    }
});
