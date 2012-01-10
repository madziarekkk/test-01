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

pimcore.registerNS("pimcore.object.tags.numeric");
pimcore.object.tags.numeric = Class.create(pimcore.object.tags.abstract, {

    type: "numeric",

    initialize: function (data, fieldConfig) {

        this.data = data;
        this.fieldConfig = fieldConfig;
    },

    getGridColumnEditor: function(field) {
        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        if(field.layout.noteditable) {
            return null;
        }
        // NUMERIC
        if (field.type == "numeric") {
            editorConfig.decimalPrecision = 20;
            return new Ext.ux.form.SpinnerField(editorConfig);
        }
    },

    getGridColumnFilter: function(field) {
        return {type: 'numeric', dataIndex: field.key};
    },

    getLayoutEdit: function () {

        var input = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            itemCls: "object_field"
        };

        if (!isNaN(this.data)) {
            input.value = this.data;
        }

        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        }

        input.decimalPrecision = 20;

        this.component = new Ext.ux.form.SpinnerField(input);

        return this.component;
    },


    getLayoutShow: function () {

        var input = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            cls: "object_field"
        };

        if (this.data) {
            input.value = this.data;
        }

        if (this.fieldConfig.width) {
            input.width = this.fieldConfig.width;
        }

        this.component = new Ext.form.TextField(input);
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        if(this.isRendered()) {
            return this.component.getValue().toString();
        }
        return null;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {

        if(!this.isRendered() && (!empty(this.getInitialData() || this.getInitialData() === 0) )) {
            return false;
        } else if (!this.isRendered()) {
            return true;
        }

        if (this.getValue()) {
            return false;
        }
        return true;
    }
});