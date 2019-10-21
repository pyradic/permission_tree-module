export namespace AddonPermission {
    export namespace Stream {
        export interface Permission {
            label?: string
            enabled?: boolean
            key?: string
            permission?: string
        }
    }

    export interface Stream {
        label?: string
        field?: string
        key?: string
        permissions?: Stream.Permission[]
    }
}

export interface AddonPermission {
    label?: string
    key?: string
    streams?: AddonPermission.Stream[]
}

export type AddonPermissions = Array<AddonPermission>
