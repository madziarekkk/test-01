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

pimcore.registerNS("pimcore.settings.profile.panel");
pimcore.settings.profile.panel = Class.create({

    initialize: function () {

        this.getTabPanel();
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "profile",
                title: t("profile"),
                border: false,
                layout: "border",
                closable:true,
                items: [this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("profile");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("profile");
            }.bind(this));


            pimcore.layout.refresh();

        }

        return this.panel;
    },



    getEditPanel: function () {

        if (!this.editPanel) {
            this.editPanel = new Ext.Panel({
                title: "&nbsp;",
                region: "center",
                bodyStyle:'padding:10px;',
                layout: "fit"
            });
        }

        this.addUserPanel();

        return this.editPanel;
    },



    addUserPanel: function () {

        this.forceReloadOnSave = false;
        this.currentUser = pimcore.currentuser;
        if (this.userPanel) {
            this.editPanel.remove(this.userPanel);
        }

        var generalItems = new Array();

        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("username"),
            value: this.currentUser.username,
            width: 300,
            disabled: true
        }));
        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("password"),
            name: "password",
            inputType: "password",
            width: 300
        }));

        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("firstname"),
            name: "firstname",
            value: this.currentUser.firstname,
            width: 300
        }));
        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("lastname"),
            name: "lastname",
            value: this.currentUser.lastname,
            width: 300
        }));
        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("email"),
            name: "email",
            value: this.currentUser.email,
            width: 300
        }));


        generalItems.push(new Array({
            xtype:'combo',
            fieldLabel: t('language'),
            typeAhead:true,
            value: this.currentUser.language,
            mode: 'local',
            listWidth: 100,
            store: pimcore.globalmanager.get("pimcorelanguages"),
            displayField: 'display',
            valueField: 'language',
            forceSelection: true,
            triggerAction: 'all',
            hiddenName: 'language',
            listeners: {
                change: function () {
                    this.forceReloadOnSave = true;
                }.bind(this),
                select: function () {
                    this.forceReloadOnSave = true;
                }.bind(this)
            }
        }));

        var userItems = new Array();

        if (this.currentUser.hasCredentials) {
            userItems.push(new Array({
                xtype: "fieldset",
                title: t("general"),
                items: generalItems
            }));
        }


        this.userPanel = new Ext.form.FormPanel({
            border: false,
            layout: "pimcoreform",
            items: userItems,
            buttons: [
                {
                    text: t("save"),
                    handler: this.saveCurrentUser.bind(this)
                }
            ],
            autoScroll: true
        });

        this.editPanel.add(this.userPanel);
        this.editPanel.setTitle(t("user") + ": " + this.currentUser.username);

        pimcore.layout.refresh();
    },

    saveCurrentUser: function () {
        var values = this.userPanel.getForm().getFieldValues();
        
        Ext.Ajax.request({
            url: "/admin/user/update-current-user",
            method: "post",
            params: {
                id: this.currentUser.id,
                data: Ext.encode(values)
            },
            success: function (response) {
                try{
                    var res = Ext.decode(response.responseText);
                    if (res.success) {

                        if(this.forceReloadOnSave) {
                            this.forceReloadOnSave = false;

                            Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                                if (buttonValue == "yes") {
                                    window.location.reload();
                                }
                            }.bind(this));
                        }

                        pimcore.helpers.showNotification(t("success"), t("user_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error",t(res.message));
                    }
                } catch (e){
                    pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error");    
                }
            }.bind(this)
        });
    },


    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").activate("users");
    }

});