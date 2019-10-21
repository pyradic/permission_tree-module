import { ServiceProvider } from '@pyro/platform';
import * as admin from '@pyro/admin-theme'
import { debug } from 'debug';
import PermissionTreeVuePlugin from './PermissionTreeVuePlugin';
const log = debug('permission_tree.PermissionTreeServiceProvider');


export class PermissionTreeServiceProvider extends ServiceProvider {
    public register(): any | Promise<any> {
        this.app.hooks.start.tap('PermissionTreeServiceProvider', Vue => {
            log('hooks.start', { Vue });
            this.app.instance('permission_tree.permissions', PLATFORM_DATA.permission_tree.permissions)
            Vue.use(PermissionTreeVuePlugin)
        });
        console.log('admin',admin)
    }
}

