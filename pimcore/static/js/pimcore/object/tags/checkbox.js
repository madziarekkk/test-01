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

pimcore.registerNS("pimcore.object.tags.checkbox");
pimcore.object.tags.checkbox = Class.create(pimcore.object.tags.abstract, {

    type: "checkbox",

    initialize: function (data, fieldConfig) {

        this.data = "";

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
    },

    getGridColumnConfig: function(field) {
        return new Ext.grid.CheckColumn({
            header: ts(field.label),
            dataIndex: field.key,
            renderer: function (key, value, metaData, record, rowIndex, colIndex, store) {
                if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.css += " grid_value_inherited";
                }
                metaData.css += ' x-grid3-check-col-td';
                return String.format('<div class="x-grid3-check-col{0}">&#160;</div>', value ? '-on' : '');
            }.bind(this, field.key)
        });
    },

    getGridColumnFilter: function(field) {
        return {type: 'boolean', dataIndex: field.key};
    },    

    getLayoutEdit: function () {

        var checkbox = {
            fieldLabel: this.fieldConfig.title,
            name: this.fieldConfig.name,
            itemCls: "object_field"
        };


        if (this.fieldConfig.width) {
            checkbox.width = this.fieldConfig.width;
        }

        this.component = new Ext.form.Checkbox(checkbox);

        this.component.setValue(this.data);

        return this.component;
    },


    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {
        return this.component.getValue();
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {
        return false;
    }
});