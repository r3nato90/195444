import { type VNode } from 'vue';
declare const _default: import("vue").DefineComponent<{
    multiple: {
        type: BooleanConstructor;
        default: boolean;
    };
    name: {
        type: StringConstructor;
        required: true;
    };
    slotProps: {
        type: ObjectConstructor;
        default: () => {};
    };
}, () => VNode<import("vue").RendererNode, import("vue").RendererElement, {
    [key: string]: any;
}>[] | undefined, unknown, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, "change"[], "change", import("vue").VNodeProps & import("vue").AllowedComponentProps & import("vue").ComponentCustomProps, Readonly<import("vue").ExtractPropTypes<{
    multiple: {
        type: BooleanConstructor;
        default: boolean;
    };
    name: {
        type: StringConstructor;
        required: true;
    };
    slotProps: {
        type: ObjectConstructor;
        default: () => {};
    };
}>> & {
    onChange?: ((...args: any[]) => any) | undefined;
}, {
    slotProps: Record<string, any>;
    multiple: boolean;
}>;
export default _default;
