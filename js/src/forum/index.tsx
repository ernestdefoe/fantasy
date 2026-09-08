import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import LinkButton from 'flarum/common/components/LinkButton';

import FantasyIndexPage from './components/FantasyIndexPage';
import FantasyLeaguePage from './components/FantasyLeaguePage';

app.initializers.add('ernestdefoe-fantasy', () => {
  app.routes['fantasy.index'] = { path: '/fantasy', component: FantasyIndexPage };
  app.routes['fantasy.league'] = { path: '/fantasy/:slug', component: FantasyLeaguePage };

  extend(IndexPage.prototype, 'navItems', function (items: any) {
    items.add(
      'fantasy',
      <LinkButton href={app.route('fantasy.index')} icon="fas fa-trophy">
        {app.translator.trans('ernestdefoe-fantasy.forum.title')}
      </LinkButton>,
      -11
    );
  });
});
