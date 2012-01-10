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

pimcore.registerNS("pimcore.layout.portlets.modificationStatistic");
pimcore.layout.portlets.modificationStatistic = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.modificationStatistic";
    },

    getName: function () {
        return t("modification_statistic");
    },

    getIcon: function () {
        return "pimcore_icon_portlet_modification_statistic";
    },

    getLayout: function () {

        var store = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/portal/portlet-modification-statistics',
            root: 'data',
            fields: ['timestamp','datetext',"objects",'documents',"assets"]
        });

        store.load();


        var panel = new Ext.Panel({
            layout:'fit',
            height: 275,
            items: {
                xtype: 'linechart',
                store: store,
                xField: 'datetext',
                series: [
                    {
                        type: 'line',
                        displayName: 'Documents',
                        yField: 'documents',
                        style: {
                            color:0x01841c
                        }
                    },
                    {
                        type:'line',
                        displayName: 'Assets',
                        yField: 'assets',
                        style: {
                            color: 0x15428B
                        }
                    },
                    {
                        type:'line',
                        displayName: 'Objects',
                        yField: 'objects',
                        style: {
                            color: 0xff6600
                        }
                    }
                ]
            }
        });


        this.layout = new Ext.ux.Portlet(Object.extend(this.getDefaultConfig(), {
            title: this.getName(),
            iconCls: this.getIcon(),
            height: 275,
            layout: "fit",
            items: [panel]
        }));

        return this.layout;
    }
});
