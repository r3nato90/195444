import type { App } from 'vue';
import Portal from './components/portal';
import PortalTarget from './components/portal-target';
import { provideWormhole, useWormhole } from './composables/wormhole';
import type { Wormhole as TWormhole } from './types';
import { createWormhole } from './wormhole';
export { mountPortalTarget } from './utils/mountPortalTarget';
export interface PluginOptions {
    portalName?: string | false;
    portalTargetName?: string | false;
    MountingPortalName?: string;
    wormhole?: TWormhole;
}
export default function install(app: App, options?: PluginOptions): void;
export declare const Wormhole: Readonly<{
    open: (t: import("./types").TransportInput) => void;
    close: (t: import("./types").TransportCloser) => void;
    getContentForTarget: (t: import("./types").Name, returnAll?: boolean | undefined) => import("./types").Transport[];
    transports: import("./types").TransportsHub;
}>;
export declare const version: string;
export { install, Portal, PortalTarget, useWormhole, provideWormhole, TWormhole, createWormhole, };
