import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

/**
 * 🚨 `Extend.Admin`, not `app.extensionData`.
 *
 * Flarum 2 removed `app.extensionData` entirely. An extension still calling it
 * does not warn — it throws while the admin area is building its pages, and the
 * only thing anybody sees is the extension's own settings screen saying it
 * failed to initialize.
 *
 * 🚨 Every setting below is READ somewhere. The three defaults are what
 * `Service\Leagues::create()` starts a new league from when the form does not
 * say otherwise, and the label is what the forum's sidebar link is called. A
 * settings screen whose controls change nothing is worse than no screen: it
 * tells an operator they have configured something.
 */
export default [
  new Extend.Admin()
    .setting(() => ({
      setting: 'ernestdefoe-fantasy.nav_label',
      type: 'text',
      label: app.translator.trans('ernestdefoe-fantasy.admin.settings.nav_label'),
      help: app.translator.trans('ernestdefoe-fantasy.admin.settings.nav_label_help'),
      placeholder: app.translator.trans('ernestdefoe-fantasy.forum.title'),
    }))
    .setting(() => ({
      setting: 'ernestdefoe-fantasy.default_max_franchises',
      type: 'number',
      min: 2,
      max: 32,
      label: app.translator.trans('ernestdefoe-fantasy.admin.settings.max_franchises'),
      help: app.translator.trans('ernestdefoe-fantasy.admin.settings.max_franchises_help'),
    }))
    .setting(() => ({
      setting: 'ernestdefoe-fantasy.default_roster_size',
      type: 'number',
      min: 1,
      max: 25,
      label: app.translator.trans('ernestdefoe-fantasy.admin.settings.roster_size'),
      help: app.translator.trans('ernestdefoe-fantasy.admin.settings.roster_size_help'),
    }))
    .setting(() => ({
      setting: 'ernestdefoe-fantasy.default_starters',
      type: 'number',
      min: 1,
      max: 25,
      label: app.translator.trans('ernestdefoe-fantasy.admin.settings.starters'),
      help: app.translator.trans('ernestdefoe-fantasy.admin.settings.starters_help'),
    })),
];
