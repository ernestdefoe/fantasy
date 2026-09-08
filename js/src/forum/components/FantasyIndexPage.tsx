import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Link from 'flarum/common/components/Link';

declare const m: any;

/** Every fantasy league on the forum. */
export default class FantasyIndexPage extends Page {
  loading = true;
  leagues: any[] = [];

  oninit(vnode: any) {
    super.oninit(vnode);
    app.history.push('fantasy', app.translator.trans('ernestdefoe-fantasy.forum.title'));

    app
      .request({ method: 'GET', url: `${app.forum.attribute('apiUrl')}/fantasy/leagues` })
      .then((data: any) => {
        this.leagues = data.leagues || [];
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  view() {
    return (
      <div className="FantasyPage">
        <div className="container">
          <h1>{app.translator.trans('ernestdefoe-fantasy.forum.title')}</h1>
          <p className="FantasyPage-intro">{app.translator.trans('ernestdefoe-fantasy.forum.intro')}</p>

          {this.loading ? (
            <LoadingIndicator />
          ) : this.leagues.length === 0 ? (
            <p className="FantasyPage-empty">{app.translator.trans('ernestdefoe-fantasy.forum.empty')}</p>
          ) : (
            <div className="FantasyGrid">
              {this.leagues.map((l: any) => (
                <Link className="FantasyCard" href={app.route('fantasy.league', { slug: l.slug })}>
                  <span className="FantasyCard-name">{l.name}</span>
                  <span className={'FantasyCard-status FantasyCard-status--' + l.status}>
                    {app.translator.trans('ernestdefoe-fantasy.forum.status.' + l.status)}
                  </span>
                  {l.description ? <span className="FantasyCard-desc">{l.description}</span> : null}
                  <span className="FantasyCard-meta">
                    {app.translator.transChoice('ernestdefoe-fantasy.forum.franchise_count', l.franchises, {
                      count: l.franchises,
                    })}
                  </span>
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    );
  }
}
