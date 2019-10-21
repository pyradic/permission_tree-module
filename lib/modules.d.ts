// noinspection ES6UnusedImports
import tree from 'element-ui/types/tree'

declare module 'element-ui/types/tree' {

    import { AddonPermission } from './interfaces';

    export interface TreeData {
        type: string
        key?:string
        stream?: AddonPermission.Stream
    }
}