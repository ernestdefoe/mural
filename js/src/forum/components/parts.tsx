import app from 'flarum/forum/app';
import Link from 'flarum/common/components/Link';
import Avatar from 'flarum/common/components/Avatar';
import username from 'flarum/common/helpers/username';
import humanTime from 'flarum/common/helpers/humanTime';
import type Mithril from 'mithril';
import { fmt, person, t, type Leader, type StatsRow, type UserRow } from '../api';

/** A Mithril component from a function of its attrs (a bare function tag is a closure component to Mithril, not a render function). */
function fc<A>(render: (attrs: A) => Mithril.Children) {
  return () => ({ view: (vnode: { attrs: A }) => render(vnode.attrs) });
}

/** A member: avatar and name, linking to their profile. */
export const Person = fc(({ row, size }: { row: UserRow | null; size?: 'small' }) => {
  const user = person(row);
  if (!user) return <span className="MuralPerson MuralPerson--gone">{t('tile.deleted')}</span>;
  return (
    <Link className="MuralPerson" href={app.route.user(user)}>
      <Avatar user={user} className={size === 'small' ? 'Avatar--small' : ''} />
      <span className="MuralPerson-name">{username(user)}</span>
    </Link>
  );
});

export const Time = fc(({ at }: { at: string }) => humanTime(new Date(at)));

/** The top painters, with a bar for each one's share of the leader's count. */
export const Leaders = fc(({ rows }: { rows: Leader[] }) => {
  if (!rows.length) return <p className="MuralEmpty">{t('stats.no_painters')}</p>;
  const best = rows[0].tiles || 1;
  return (
    <ol className="MuralLeaders">
      {rows.map((row, i) => (
        <li className="MuralLeaders-row" style={{ '--share': `${Math.max(4, (row.tiles / best) * 100)}%` }}>
          <span className={`MuralLeaders-place MuralLeaders-place--${i + 1}`}>{i + 1}</span>
          <Person row={row.user} size="small" />
          <span className="MuralLeaders-count">{fmt(row.tiles)}</span>
        </li>
      ))}
    </ol>
  );
});

/** 24 hourly bars, oldest on the left. */
export const Hours = fc(({ stats }: { stats: StatsRow }) => {
  const max = Math.max(1, ...stats.hours);
  const from = Date.parse(stats.hoursFrom);
  const label = (h: number) => new Date(from + h * 3600000).toLocaleTimeString([], { hour: 'numeric' });
  return (
    <div className="MuralHours">
      <div className="MuralHours-bars" role="img" aria-label={t('stats.hours_label', { count: stats.last24 }) as string}>
        {stats.hours.map((n, h) => (
          <span className="MuralHours-bar" style={{ height: `${n ? Math.max(6, (n / max) * 100) : 2}%` }} title={`${label(h)} · ${fmt(n)}`} />
        ))}
      </div>
      <div className="MuralHours-axis">
        <span>{label(0)}</span>
        <span>{label(12)}</span>
        <span>{t('stats.now')}</span>
      </div>
    </div>
  );
});
