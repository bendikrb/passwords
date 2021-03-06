<template>
    <div id="controls">
        <div id="app-navigation-toggle" class="icon-menu" @click="showNavigation()"></div>
        <div class="breadcrumb">
            <div class="crumb svg" data-dir="/">
                <router-link :to="getBaseRoute"><img class="svg" :src="getHomeIcon" alt="Home"></router-link>
            </div>
            <div class="crumb svg" v-for="(item, index) in getItems" :class="{current:index === getItems.length - 1}">
                <router-link :to="item.path" :data-folder-id="item.folderId" :data-drop-type="item.dropType">{{ item.label }}</router-link>
            </div>
            <div class="actions creatable" v-if="showAddNew" :class="{active: showMenu}">
                <span class="button new" @click="toggleMenu()"><span class="icon icon-add"></span></span>
                <div class="newPasswordMenu popovermenu bubble menu menu-left open" @click="toggleMenu()">
                    <ul>
                        <li>
                        <span class="menuitem" v-if="newFolder" @click="createFolder">
                            <span class="icon icon-folder svg"></span>
                            <translate class="displayname" say="New Folder"/>
                        </span>
                        </li>
                        <li>
                        <span class="menuitem" v-if="newTag" @click="createTag">
                            <span class="icon icon-tag svg"></span>
                            <translate class="displayname" say="New Tag"/>
                        </span>
                        </li>
                        <li>
                        <span class="menuitem" v-if="newPassword" @click="createPassword()">
                            <span class="icon icon-filetype-text svg"></span>
                            <translate class="displayname" say="New Password"/>
                        </span>
                        </li>
                        <li>
                        <span class="menuitem" v-if="restoreAll" @click="restoreAllEvent">
                            <span class="icon icon-history svg"></span>
                            <translate class="displayname" say="Restore All Items"/>
                        </span>
                        </li>
                        <li>
                        <span class="menuitem" v-if="deleteAll" @click="deleteAllEvent">
                            <span class="icon icon-delete svg"></span>
                            <translate class="displayname" say="Delete All Items"/>
                        </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import $ from "jquery";
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import TagManager from '@js/Manager/TagManager';
    import Localisation from '@js/Classes/Localisation';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {
        components: {
            Translate
        },

        props: {
            newPassword: {
                type     : Boolean,
                'default': true
            },
            newFolder  : {
                type     : Boolean,
                'default': false
            },
            newTag     : {
                type     : Boolean,
                'default': false
            },
            deleteAll  : {
                type     : Boolean,
                'default': false
            },
            restoreAll  : {
                type     : Boolean,
                'default': false
            },
            showAddNew : {
                type     : Boolean,
                'default': true
            },
            items      : {
                type     : Array,
                'default': () => { return []; }
            },
            folder     : {
                type     : String,
                'default': null
            },
            tag        : {
                type     : String,
                'default': null
            }
        },
        data() {
            return {
                showMenu: false
            };
        },

        computed: {
            getHomeIcon() {
                return API.baseUrl + 'core/img/places/home.svg';
            },
            getBaseRoute() {
                let route = this.$route.path;

                return route.substr(0, route.indexOf('/', 1));
            },
            getItems() {
                if(this.items.length === 0) {
                    return [
                        {path: this.$route.path, label: Localisation.translate(this.$route.name)}
                    ];
                }

                return this.items;
            }
        },

        methods: {
            toggleMenu() {
                this.showMenu = !this.showMenu;
                this.showMenu ? $(document).click(this.menuEvent):$(document).off('click', this.menuEvent);
            },
            menuEvent($e) {
                if($($e.target).closest('.actions.creatable').length !== 0) return;
                this.showMenu = false;
                $(document).off('click', this.menuEvent);
            },
            createFolder() {
                FolderManager.createFolder(this.folder);
            },
            createTag() {
                TagManager.createTag();
            },
            createPassword() {
                PasswordManager.createPassword(this.folder, this.tag);
            },
            showNavigation() {
                $('#app-content').toggleClass('mobile-open');
            },
            deleteAllEvent() {
                this.$emit('deleteAll');
            },
            restoreAllEvent() {
                this.$emit('restoreAll');
            }
        }
    };
</script>

<style lang="scss">
    #controls {
        position : sticky;

        .actions.creatable {
            margin-left : 10px;
            display     : inline-block;
            position    : relative;
            order       : 2;

            .newPasswordMenu {
                max-height : 0;
                margin     : 0;
                overflow   : hidden;
                transition : max-height 0.25s ease-in-out;
            }

            &.active .newPasswordMenu {
                overflow   : visible;
                max-height : 75px;
                animation  : 0.25s delay-overflow;
            }
        }

        .breadcrumb {
            display : flex;

            .crumb {
                white-space : nowrap;

                &.current {
                    font-weight : 600;
                }
            }
        }

        #app-navigation-toggle {
            display : none !important;
        }

        @keyframes delay-overflow {
            0% { overflow : hidden; }
            99% { overflow : hidden; }
            100% { overflow : visible; }
        }

        @media(max-width : $tablet-width) {
            padding-left : 0 !important;
            overflow-x   : auto;

            #app-navigation-toggle {
                display          : block !important;
                position         : sticky;
                min-width        : 44px;
                top              : 0;
                background-color : $color-white;
                opacity          : 1;
                color            : transparentize($color-black, 0.4);

                &:hover {
                    color : $color-black
                }
            }
        }
    }

    .edge {
        .bubble,
        .popovermenu,
        #app-navigation .app-navigation-entry-menu {
            border : none !important;

            &:after {
                border : none !important;
            }
        }
    }
</style>