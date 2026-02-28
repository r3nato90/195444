import { inject as S, provide as M, defineComponent as p, onMounted as h, onUpdated as T, onBeforeUnmount as g, watch as y, computed as N, h as d, reactive as _, readonly as j, createApp as F, getCurrentInstance as O } from "vue";
const m = Symbol("wormhole");
function w() {
  const e = S(m);
  if (!e)
    throw new Error(`
    [portal-vue]: Necessary Injection not found. Make sur you installed the plugin properly.`);
  return e;
}
function x(e) {
  M(m, e);
}
const v = typeof window < "u";
function k(e, t) {
  return e.map((n, l) => [l, n]).sort(function(n, l) {
    return t(n[1], l[1]) || n[0] - l[0];
  }).map((n) => n[1]);
}
function B(e, t) {
  const n = w();
  function l() {
    if (!v)
      return;
    const { to: c, name: o, order: r } = e;
    t.default ? n.open({
      to: c,
      from: o,
      order: r,
      content: t.default
    }) : s();
  }
  function s(c) {
    n.close({
      to: c ?? e.to,
      from: e.name
    });
  }
  h(() => {
    e.disabled || l();
  }), T(() => {
    e.disabled ? s() : l();
  }), g(() => {
    s();
  }), y(
    () => e.to,
    (c, o) => {
      e.disabled || (o && o !== c && s(o), l());
    }
  );
}
const W = p({
  compatConfig: { MODE: 3 },
  name: "portal",
  props: {
    disabled: { type: Boolean },
    name: { type: [String, Symbol], default: () => Symbol() },
    order: { type: Number },
    slotProps: { type: Object, default: () => ({}) },
    to: {
      type: String,
      default: () => String(Math.round(Math.random() * 1e7))
    }
  },
  setup(e, { slots: t }) {
    return B(e, t), () => e.disabled && t.default ? t.default(e.slotProps) : null;
  }
}), E = (e, { slots: t }) => {
  var n;
  return (n = t.default) == null ? void 0 : n.call(t);
}, b = p({
  compatConfig: { MODE: 3 },
  name: "portalTarget",
  props: {
    multiple: { type: Boolean, default: !1 },
    name: { type: String, required: !0 },
    slotProps: { type: Object, default: () => ({}) }
  },
  emits: ["change"],
  setup(e, { emit: t, slots: n }) {
    const l = w(), s = N(
      () => {
        const c = l.getContentForTarget(
          e.name,
          e.multiple
        ), o = n.wrapper, r = c.map((u) => u.content(e.slotProps)), a = o ? r.flatMap(
          (u) => u.length ? o(u) : []
        ) : r.flat(1);
        return {
          vnodes: a,
          vnodesFn: () => a
        };
      }
    );
    return y(
      s,
      ({ vnodes: c }) => {
        const o = c.length > 0, r = l.transports.get(e.name), a = r ? [...r.keys()] : [];
        t("change", { hasContent: o, sources: a });
      },
      { flush: "post" }
    ), () => {
      var o;
      return s.value.vnodes.length ? [
        d("div", {
          style: "display: none",
          key: "__portal-vue-hacky-scoped-slot-repair__"
        }),
        d(E, s.value.vnodesFn)
      ] : (o = n.default) == null ? void 0 : o.call(n);
    };
  }
});
function I(e = !0) {
  const t = _(/* @__PURE__ */ new Map());
  function n(o) {
    if (!v)
      return;
    const { to: r, from: a, content: u, order: i = 1 / 0 } = o;
    if (!r || !a || !u)
      return;
    t.has(r) || t.set(r, /* @__PURE__ */ new Map());
    const f = t.get(r), P = {
      to: r,
      from: a,
      content: u,
      order: i
    };
    f.set(a, P);
  }
  function l(o) {
    const { to: r, from: a } = o;
    if (!r || !a)
      return;
    const u = t.get(r);
    !u || (u.delete(a), u.size || t.delete(r));
  }
  function s(o, r) {
    const a = t.get(o);
    if (!a)
      return [];
    const u = Array.from((a == null ? void 0 : a.values()) || []);
    return r ? k(
      u,
      (i, f) => i.order - f.order
    ) : [u.pop()];
  }
  const c = {
    open: n,
    close: l,
    transports: t,
    getContentForTarget: s
  };
  return e ? j(c) : c;
}
const C = I();
function A(e, t) {
  const n = F({
    render: () => d(b, e)
  });
  if (!e.multiple) {
    const l = O().provides ?? {};
    n._context.provides = Object.create(l);
  }
  h(() => {
    n.mount(t);
  }), g(() => {
    n.unmount();
  });
}
function D(e, t = {}) {
  t.portalName !== !1 && e.component(t.portalName || "Portal", W), t.portalTargetName !== !1 && e.component(t.portalTargetName || "PortalTarget", b);
  const n = t.wormhole ?? C;
  e.provide(m, n);
}
const q = C, z = "3.0.0";
export {
  W as Portal,
  b as PortalTarget,
  q as Wormhole,
  I as createWormhole,
  D as default,
  D as install,
  A as mountPortalTarget,
  x as provideWormhole,
  w as useWormhole,
  z as version
};
//# sourceMappingURL=portal-vue.mjs.map
