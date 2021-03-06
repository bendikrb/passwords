<template>
    <div id="app-content">
        <div class="app-content-left settings">
            <breadcrumb :show-add-new="false"/>
            <section class="security">
                <translate tag="h1" say="Security"/>
                <translate tag="h3" say="Password Generator"/>

                <translate tag="label" for="setting-security-level" say="Password strength"/>
                <select id="setting-security-level" v-model="settings['user.password.generator.strength']">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
                <settings-help text="A higher strength results in longer, more complex passwords"/>

                <translate tag="label" for="setting-include-numbers" say="Include numbers"/>
                <input type="checkbox" id="setting-include-numbers" v-model="settings['user.password.generator.numbers']">
                <span></span>

                <translate tag="label" for="setting-include-special" say="Include special characters"/>
                <input type="checkbox" id="setting-include-special" v-model="settings['user.password.generator.special']">
                <span></span>
            </section>
            <section class="ui">
                <translate tag="h1" say="User Interface"/>

                <translate tag="h3" say="General"/>
                <translate tag="label" for="setting-section-default" say="Initial section"/>
                <select id="setting-section-default" v-model="settings['client.ui.section.default']">
                    <translate tag="option" value="all" say="All Passwords"/>
                    <translate tag="option" value="favourites" say="Favourites"/>
                    <translate tag="option" value="folders" say="Folders"/>
                    <translate tag="option" value="tags" say="Tags"/>
                    <translate tag="option" value="recent" say="Recent"/>
                </select>
                <settings-help text="The initial section to be shown when the app is opened"/>

                <translate tag="label" for="setting-password-title" say="Set title from"/>
                <select id="setting-password-title" v-model="settings['client.ui.password.field.title']">
                    <translate tag="option" value="label" say="Name"/>
                    <translate tag="option" value="website" say="Website"/>
                    <translate tag="option" value="user" say="Username"/>
                </select>
                <settings-help text="Show the selected property as title in the list view"/>

                <translate tag="h3" say="Passwords List Click Action"/>
                <translate tag="label" for="setting-password-click" say="On password row click"/>
                <select id="setting-password-click" v-model="settings['client.ui.password.click.action']">
                    <translate tag="option" value="showDetails" say="Show details"/>
                    <translate tag="option" value="copyPassword" say="Copy password"/>
                    <translate tag="option" value="copyWebsite" say="Copy website URL"/>
                </select>
                <settings-help text="Perform the selected action when clicking on an item in the list view"/>

                <translate tag="label" for="setting-password-sorting" say="Sort by"/>
                <select id="setting-password-sorting" v-model="settings['client.ui.password.field.sorting']">
                    <translate tag="option" value="byTitle" say="Title field"/>
                    <translate tag="option" value="label" say="Name"/>
                    <translate tag="option" value="website" say="Website"/>
                    <translate tag="option" value="user" say="Username"/>
                </select>
                <settings-help text="Sorts passwords by the selected property when sorting by name is selected"/>

                <translate tag="label" for="setting-password-menu" say="Add copy options in menu"/>
                <input type="checkbox" id="setting-password-menu" v-model="settings['client.ui.password.menu.copy']">
                <settings-help text="Shows options to copy the password and user name in the menu"/>

                <translate tag="label" for="setting-password-tags" say="Show tags in list view"/>
                <input type="checkbox" id="setting-password-tags" v-model="settings['client.ui.list.tags.show']">
                <settings-help text="Show the tags for each password in the list view. Increases loading times"/>
            </section>
            <section class="notifications">
                <translate tag="h1" say="Notifications"/>

                <translate tag="h3" say="Send Emails for"/>
                <translate tag="label" for="setting-mail-security" say="Security issues"/>
                <input type="checkbox" id="setting-mail-security" v-model="settings['user.mail.security']">
                <settings-help text="Sends you e-mails about compromised passwords and other security issues"/>

                <translate tag="label" for="setting-mail-shares" say="Passwords shared with me"/>
                <input type="checkbox" id="setting-mail-shares" v-model="settings['user.mail.shares']">
                <settings-help text="Sends you e-mails when other people share passwords with you"/>

                <translate tag="h3" say="Show Notifications for"/>
                <translate tag="label" for="setting-notification-security" say="Security issues"/>
                <input type="checkbox" id="setting-notification-security" v-model="settings['user.notification.security']">
                <settings-help text="Notifies you about compromised passwords and other security issues"/>

                <translate tag="label" for="setting-notification-sharing" say="Passwords shared with me"/>
                <input type="checkbox" id="setting-notification-sharing" v-model="settings['user.notification.shares']">
                <settings-help text="Notifies you when other people share passwords with you"/>

                <translate tag="label" for="setting-notification-errors" say="Other errors"/>
                <input type="checkbox" id="setting-notification-errors" v-model="settings['user.notification.errors']">
                <settings-help text="Notifies you when a background operation fails"/>
            </section>
            <section class="tests" v-if="nightly">
                <translate tag="h1" say="Field tests"/>

                <translate tag="label" for="setting-test-encryption" say="Encryption support"/>
                <input type="button" id="setting-test-encryption" value="Test" @click="runTests($event)">
                <settings-help text="Checks if your passwords, folders and tags can be encrypted without issues"/>
            </section>
            <section class="danger">
                <translate tag="h1" say="Danger Zone"/>

                <translate tag="label" for="danger-reset" say="Reset all settings"/>
                <translate tag="input" type="button" id="danger-reset" value="Reset" @click="resetSettingsAction"/>
                <settings-help text="Reset all settings on this page to their defaults"/>

                <translate tag="label" for="danger-purge" say="Delete everything"/>
                <translate tag="input" type="button" id="danger-purge" value="Delete" @click="resetUserAccount"/>
                <settings-help text="Start over and delete all configuration, passwords, folders and tags"/>
            </section>
        </div>
        <div id="settings-reset" class="loading" v-if="locked"></div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Messages from '@js/Classes/Messages';
    import Translate from '@vue/Components/Translate';
    import Breadcrumb from '@vue/Components/Breadcrumb';
    import SettingsHelp from '@vue/Components/SettingsHelp';
    import SettingsManager from '@js/Manager/SettingsManager';
    import EncryptionTestHelper from '@js/Helper/EncryptionTestHelper';

    export default {
        components: {
            Breadcrumb,
            SettingsHelp,
            Translate
        },
        data() {
            return {
                settings: SettingsManager.getAll(),
                nightly : process.env.NIGHTLY_FEATURES,
                noSave  : false,
                locked  : false
            };
        },
        methods   : {
            saveSettings() {
                if(this.noSave) return;
                for(let i in this.settings) {
                    if(!this.settings.hasOwnProperty(i)) continue;
                    let value = this.settings[i];

                    if(SettingsManager.get(i) !== value) SettingsManager.set(i, value);
                }
            },
            async runTests($event) {
                $event.target.setAttribute('disabled', 'disabled');
                let result = await EncryptionTestHelper.runTests();
                if(result) Messages.info('The client side encryption test completed successfully on this browser', 'Test successful');
                $event.target.removeAttribute('disabled');
            },
            resetSettingsAction() {
                Messages.confirm('This will reset all settings to their defaults. Do you want to continue?', 'Reset all settings')
                        .then(() => { this.resetSettings(); });
            },
            async resetSettings() {
                this.locked = true;
                this.noSave = true;
                for(let i in this.settings) {
                    if(this.settings.hasOwnProperty(i)) this.settings[i] = await SettingsManager.reset(i);
                }
                this.noSave = false;
                this.locked = false;
            },
            async resetUserAccount() {
                try {
                    let form = await Messages.form(
                        {password: {type: 'password'}},
                        'DELETE EVERYTHING',
                        'Do you want to delete all your settings, passwords, folders and tags?\nIt will NOT be possible to undo this.'
                    );
                    if(form.password) {
                        this.performUserAccountReset(form.password);
                    }
                } catch(e) {

                }
            },
            async performUserAccountReset(password) {
                try {
                    this.locked = true;
                    let response = await API.resetUserAccount(password);

                    if(response.status === 'accepted') {
                        this.locked = false;
                        Messages.confirm(['You have to wait {seconds} seconds before you can reset your account.', {seconds: response.wait}], 'Account reset requested')
                                .then(() => { this.performUserAccountReset(password); });
                    } else if(response.status === 'ok') {
                        window.localStorage.removeItem('passwords.settings');
                        window.localStorage.removeItem('pwFolderIcon');
                        location.href = location.href.replace(location.hash, '');
                    }
                } catch(e) {
                    console.error(e);
                    Messages.alert('Invalid Password');
                }
            }
        },
        watch     : {
            settings: {
                handler(value, oldValue) {
                    this.saveSettings();
                },
                deep: true
            }
        }
    };
</script>

<style lang="scss">
    .app-content-left.settings {
        padding      : 10px;
        margin-right : -2em;

        #controls {
            display : none;
        }

        h1 {
            font-size   : 2.25em;
            font-weight : 200;
            margin      : 0.25em 0 1em;
        }

        h3 {
            margin-bottom : 0;
        }

        section {
            display               : grid;
            grid-template-columns : 3fr 2fr 30px;
            width                 : 420px;
            max-width             : 25%;
            float                 : left;
            padding               : 0 2em 4em 0;

            h1,
            h3 {
                grid-column-start : 1;
                grid-column-end   : 4;
            }

            label {
                line-height : 40px;
            }

            select {
                justify-self : end;
                width        : 100%;
            }

            input {
                justify-self : end;
                max-height   : 34px;

                &[type=checkbox] {
                    cursor : pointer;
                }
            }

            &.danger {

                input[type=button] {
                    transition : color .2s ease-in-out, border-color .2s ease-in-out, background-color .2s ease-in-out;

                    &:hover {
                        background-color : $color-red;
                        border-color     : $color-red;
                        color            : $color-white;
                    }
                }
            }
        }

        @media all and (max-width : $width-extra-large) {
            padding : 10px 0 0 10px;

            section {
                width     : 33%;
                max-width : 33%;
                padding   : 0 2em 4em 0;
            }
        }

        @media all and (max-width : $width-large) {
            section {
                width     : 50%;
                max-width : 50%;
                padding   : 0 2em 4em 0;
            }
        }

        @media all and (max-width : $width-medium) {
            padding : 44px 0 0 10px;
            #controls {
                display  : flex;
                position : fixed;
                width    : 100%;
                margin   : 0 -10px;
            }
        }

        @media all and (max-width : $width-medium) {
            margin-right : 0;
            padding      : 44px 0 0 10px;

            #controls {
                display  : flex;
                position : fixed;
                width    : 100%;
                margin   : 0 -10px;
            }

            section {
                width     : 100%;
                max-width : 100%;
                padding   : 0 0 4em 0;
            }
        }
    }

    #settings-reset {
        position         : fixed;
        top              : 0;
        right            : 0;
        bottom           : 0;
        left             : 0;
        background-color : transparentize($color-black, 0.9);
        cursor           : wait;
        z-index: 2000;
    }
</style>
