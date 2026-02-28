import { type InjectionKey } from 'vue';
import type { Wormhole } from '../types';
export declare const wormholeSymbol: InjectionKey<Readonly<{
    open: (t: import("../types").TransportInput) => void;
    close: (t: import("../types").TransportCloser) => void;
    getContentForTarget: (t: import("../types").Name, returnAll?: boolean | undefined) => import("../types").Transport[];
    transports: import("../types").TransportsHub;
}>>;
export declare function useWormhole(): Readonly<{
    open: (t: import("../types").TransportInput) => void;
    close: (t: import("../types").TransportCloser) => void;
    getContentForTarget: (t: import("../types").Name, returnAll?: boolean | undefined) => import("../types").Transport[];
    transports: import("../types").TransportsHub;
}>;
export declare function provideWormhole(wormhole: Wormhole): void;
