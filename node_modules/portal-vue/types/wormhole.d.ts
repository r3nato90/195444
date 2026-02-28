import type { Name, Transport, TransportCloser, TransportInput, TransportsHub, Wormhole } from './types';
export declare function createWormhole(asReadonly?: boolean): Wormhole;
export declare const wormhole: Readonly<{
    open: (t: TransportInput) => void;
    close: (t: TransportCloser) => void;
    getContentForTarget: (t: Name, returnAll?: boolean | undefined) => Transport[];
    transports: TransportsHub;
}>;
