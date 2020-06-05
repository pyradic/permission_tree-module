<template>
    <div class="card permissions">
        <input type="hidden" ref="input"></input>
        <div class="card-header">
            <h5 class="card-title">Permissions</h5>
        </div>
        <ul class="list-group list-group-flush controls">
            <li class="list-group-item">
                <el-button class="control" size="mini" @click="switchDataViewType">Switch to {{ dataViewType === 'expanded' ? 'compact' : 'normal' }} view</el-button>
                <el-button class="control" size="mini" @click="expandAll">Expand all</el-button>
                <el-button class="control" size="mini" @click="collapseAll">Collapse all</el-button>
            </li>
        </ul>
        <div class="card-block card-body">
            <el-tree ref="tree"
                     :class="classes"
                     :data="dataViewType === 'expanded' ? dataExpanded : dataCompact"
                     show-checkbox
                     :default-expand-all="defaultExpandAll"
                     node-key="key"
                     :check-on-click-node="false"
                     auto-expand-parent
                     @check="handleCheck"
                     @node-click="handleNodeClick"
                     @check-change="handleCheckChange"
                     :expand-on-click-node="false"
                     :render-content="renderNodeContent"
                     :default-checked-keys="defaultEnabledKeys"
                     highlight-current
                     :props="defaultProps">
            </el-tree>
        </div>
    </div>
</template>
<script lang="ts">

    import { CreateElement } from 'vue';
    import { app, Component, component, prop } from '@crvs/platform';
    import { Button, Checkbox, Col, Icon, Input, Notification, Row, Tree } from 'element-ui';
    import { AddonPermission, AddonPermissions } from './interfaces';
    import { TreeData, TreeNode } from 'element-ui/types/tree';
    import qs from 'qs';

    export interface Data extends TreeData, AddonPermission.Stream, AddonPermission.Stream.Permission {
        type: string
        stream?: AddonPermission.Stream
    }

    export interface OriginalCheckbox {
        name: string,
        value: string,
        checked: boolean,
        el: HTMLInputElement
    }

    export type OriginalCheckboxes = OriginalCheckbox[];

    const log = require('debug')('PermissionComponent')

    export type PermissionTreeViewType = 'compact' | 'expanded';
    @component({
        components: {
            'ElTree'    : Tree,
            'ElCheckbox': Checkbox,
            'ElButton'  : Button,
            'ElInput'   : Input,
            'ElIcon'    : Icon,
            'ElRow'     : Row,
            'ElCol'     : Col
        }
    })
    export default class PermissionTree extends Component {
        name                                 = 'permission-tree'
        $refs: { tree: Tree, input: HTMLInputElement }
        @prop.string() connectForm: string
        @prop.array() permissions: any;
        @prop.array() disabled: string[];
        dataCompact: any                     = [];
        dataExpanded: any                    = []
        dataViewType: PermissionTreeViewType = app.cookies.get('permission_tree.permissions.dataViewType') || 'compact'
        defaultExpandAll: boolean            = true;
        defaultProps                         = {
            children: 'children',
            label   : 'label',
            disabled: 'disabled'
        }
        defaultEnabledKeys                   = []
        // permissions: Record<string, AddonPermission.Stream.Permission>
        originalCheckboxes: OriginalCheckboxes
        perms: any;

        get tree(): Tree {return this.$refs.tree}

        get classes() {
            return {
                'permission-tree'         : true,
                'permission-tree--compact': this.isCompact
            }
        }

        get isCompact() {return this.dataViewType === 'compact'}

        get form(): HTMLFormElement {
            if ( this.connectForm !== undefined ) {
                return document.querySelector(this.connectForm)
            }
        }

        get hasForm() { return this.form !== undefined }

        created() {
            window[ '$tree' ]     = this
            this.perms = {}
            this.checkedKeys      = ''
            this.dataViewType     = this.$py.cookies.get('permission_tree.permissions.dataViewType') || 'compact';
            this.defaultExpandAll = this.$py.cookies.has('permission_tree.permissions.defaultExpandAll') || false;


        }

        beforeMount() {
            this.setDataFromPermissions(this.permissions);
        }

        mounted() {
            this.originalCheckboxes = this.findOriginalCheckboxes()
        }

        saving: Promise<any>;
        queuedSave: any;

        async save() {
            if ( !this.hasForm ) {
                return console.warn('Cannot save() the permission-tree, no form has been connected.')
            }
            let formData = this.collectFormData();
            this.$log('save', { ...formData })
            if ( this.saving ) {
                this.queuedSave = formData
                this.$log('save', 'already saving. setted new queued save')
                return;
            }
            this.saving  = this.saveUntilDone(formData)
            let response = await this.saving;
            this.saving  = null;
            Notification.success({
                title   : null,
                message : 'Permissions saved!',
                position: 'bottom-right'
            })
            return response;
        }

        protected async saveUntilDone(formData: object) {
            return this.postSave(formData).then(response => {
                if ( this.queuedSave ) {
                    let formData    = this.queuedSave
                    this.queuedSave = null
                    return this.saveUntilDone(formData);
                }
                return response;
            });
        }

        protected async postSave(formData: object) {
            let data     = qs.stringify(formData, {});
            let url      = this.form.getAttribute('action');
            let response = await this.$py.http.request({
                url, data,
                method : 'post',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            return response;
        }

        protected collectFormData() {
            let data = {};
            for ( let i = 0; i < this.form.elements.length; i ++ ) {
                let input = this.form.elements.item(i) as HTMLInputElement;
                if ( !input.checked || input.name === '' ) {
                    continue;
                }
                if ( typeof data[ input.name ] === 'undefined' ) {
                    data[ input.name ] = [];
                }
                data[ input.name ].push(input.value);
            }
            data[ '_token' ] = this.form.elements[ '_token' ].value
            data[ 'action' ] = 'save';
            this.$log('collectFormData', data);
            return data;
        }

        findOriginalCheckboxes(): OriginalCheckboxes {
            let inputs = Array.from(this.$refs.input.form) as HTMLInputElement[];

            return inputs
                .filter(el => el.type === 'checkbox' && el.classList.contains('el-checkbox__original') === false)
                .map(el => ({ name: el.name, value: el.value, checked: el.checked, el }))
        }

        checkedKeys: string

        handleCheck(data: TreeData, other) {
            log('handleCheck', { data, other })
            let checkedKeys = other.checkedKeys.join('|');
            if ( checkedKeys !== this.checkedKeys ) {
                this.tree.store._getAllNodes().forEach(node => {
                    if ( node.data.type === 'permission' ) {
                        this.perms[ node.data.key ].enabled = node.checked
                        this.updateOriginalCheckboxByNode(node.data, node.checked)
                    }
                })
                this.checkedKeys        = checkedKeys;
                this.defaultEnabledKeys = this.getEnabledPermissions().map((p: any) => p.key)
                this.save();
            }
        }

        handleNodeClick(data: TreeData, node: TreeNode<any, TreeData>, component: Tree) {
            log('handleNodeClick', { data, node, component })
            if ( data.disabled ) {
                return;
            }
            if ( data.type === 'permission' ) {
                let checked = !node.checked
                this.tree.setChecked(data as any, checked, true)
            } else if ( data.type === 'addon' ) {
                node.expanded = !node.expanded
            } else if ( data.type === 'stream' && this.isCompact === false ) {
                node.expanded = !node.expanded
            }
            // this.tree.store._getAllNodes().filter(node => node.expanded).map(node => node.key)
        }

        handleCheckChange(data: TreeData, checked) {
            return;
            if ( data.type === 'permission' ) {
                log('handleCheckChange', { data, checked, me: this })
                this.perms[ data.key ].enabled = checked
                this.defaultEnabledKeys              = this.getEnabledPermissions().map((p: any) => p.key)
                this.updateOriginalCheckboxByNode(data, checked);
                this.save();
            }
        }

        updateOriginalCheckboxByNode(data: TreeData, checked) {
            let name             = data.stream.field + '[]';
            let value            = data.key;
            let originalCheckbox = this.originalCheckboxes.find(c => c.name === name && c.value === value);
            if ( !originalCheckbox ) {
                return console.warn(`Original checkbox not found for ${name} / ${value}`)
            }
            originalCheckbox.checked = checked
            if ( checked !== originalCheckbox.el.checked ) {
                originalCheckbox.el.click()
                log('originalCheckbox updated', { data, name, value, originalCheckbox })
            }
        }

        getEnabledPermissions() {
            return Object.values(this.perms).filter((p: any) => p.enabled)
        }

        renderNodeContent(h: CreateElement, { node, data, store }: { node: TreeNode<any, TreeData>, data: TreeData, store: any }) {
            if ( data.type === 'addon' ) {
                return h('span', { class: 'text-addon' }, data.label)
            } else if ( data.type === 'stream' ) {
                return h('span', { class: 'text-stream' }, data.label)
            } else if ( data.type === 'permission' ) {
                if ( this.isCompact ) {
                    return h('span', { class: 'addon-child-texts' }, [
                        h('span', { class: 'text-stream' }, data.stream.label),
                        h('span', { class: 'text-permission' }, data.label)
                    ])
                }
                return h('span', { class: 'text-permission' }, data.label)
            }
        }

        expandAll() {
            this.$py.cookies.set('permission_tree.permissions.defaultExpandAll', true)
            this.tree.store._getAllNodes().forEach(node => node.expanded = true)
        }

        collapseAll() {
            this.$py.cookies.unset('permission_tree.permissions.defaultExpandAll')
            this.tree.store._getAllNodes().forEach(node => node.expanded = false)
        }

        switchDataViewType(event?: MouseEvent) {
            let dataViewType: PermissionTreeViewType = this.dataViewType === 'expanded' ? 'compact' : 'expanded';
            log('switchDataViewType', 'from:', this.dataViewType, '  to', dataViewType)
            this.dataViewType = dataViewType;
            this.$py.cookies.set('permission_tree.permissions.dataViewType', dataViewType)
            if ( event ) {
                let el = event.target as HTMLButtonElement
                el.blur();
            }
        }

        setDataFromPermissions(permissions: AddonPermissions) {
            Object.entries(permissions).forEach(([ addonKey, addon ]) => {

                let type                = 'addon'
                const dataAddonExpanded = { key: addon.key, label: addon.label, type, children: [] }
                const dataAddonCompact  = { key: addon.key, label: addon.label, type, children: [] }
                this.dataExpanded.push(dataAddonExpanded)
                this.dataCompact.push(dataAddonCompact)

                Object.entries(addon.streams).forEach(([ streamKey, stream ]) => {
                    type                     = 'stream'
                    const dataStreamExpanded = { key: stream.key, label: stream.label, field: stream.field, type, children: [] }
                    const dataStreamCompact  = { key: stream.key, label: stream.label, field: stream.field, type, children: [] }
                    dataAddonExpanded.children.push(dataStreamExpanded)

                    Object.entries(stream.permissions).forEach(([ permissionKey, permission ]) => {
                        let { key, label, field }          = stream
                        let parentStream                   = { key, label, field }
                        type                               = 'permission'
                        let disabled                       = this.disabled.includes(permission.key)
                        this.perms[ permission.key ] = permission
                        if ( permission.enabled ) {
                            this.defaultEnabledKeys.push(permission.key)
                        }
                        dataStreamExpanded.children.push({ key: permission.key, label: permission.label, type, stream: parentStream, disabled })
                        dataAddonCompact.children.push({ key: permission.key, label: permission.label, type, stream: parentStream, disabled })
                    })
                })

            })
        }


    }
</script>