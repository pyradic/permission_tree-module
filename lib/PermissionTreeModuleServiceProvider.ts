import { ServiceProvider } from '@pyro/platform';
import * as admin from '@pyro/admin-theme'
import { debug } from 'debug';
import {PermissionTreeModuleVuePlugin} from './PermissionTreeModuleVuePlugin';
const log = debug('permission_tree.PermissionTreeServiceProvider');


export class PermissionTreeModuleServiceProvider extends ServiceProvider {
    public register(): any | Promise<any> {
        this.app.hooks.start.tap('PermissionTreeServiceProvider', Vue => {
            log('hooks.start', { Vue });
            // this.app.instance('permission_tree.permissions', PLATFORM_DATA.permission_tree.permissions)
            // this.app.instance('permission_tree.disabled', PLATFORM_DATA.permission_tree.disabled)
            Vue.use(PermissionTreeModuleVuePlugin)
        });
        console.log('admin',admin)
    }
}

