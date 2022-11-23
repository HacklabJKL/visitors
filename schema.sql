-- -*- mode: sql; sql-product: sqlite; -*-
BEGIN;

CREATE TABLE state (
	key TEXT PRIMARY KEY,
	value
);

CREATE TABLE visit(
	mac TEXT NOT NULL,
	enter INTEGER NOT NULL,
	leave INTEGER NOT NULL,
	ip TEXT NOT NULL,
	hostname TEXT NOT NULL,
	renewals INTEGER
);

CREATE INDEX ix_visit_mac ON visit (mac,enter);
CREATE INDEX ix_visit_enter ON visit (enter, mac);
CREATE INDEX ix_visit_leave ON visit (leave, mac);
CREATE INDEX ix_visit_ip ON visit (ip, leave DESC);

CREATE TABLE user(
	id INTEGER PRIMARY KEY,
	nick TEXT UNIQUE NOT NULL,
	flappiness INTEGER NOT NULL DEFAULT 0,
	stealth INTEGER NOT NULL DEFAULT FALSE
);

CREATE INDEX ix_user_nick ON user (nick);

CREATE TABLE user_mac(
	mac TEXT NOT NULL,
	id INTEGER, -- NULL if mac is removed
	changed INTEGER NOT NULL
);

CREATE INDEX ix_user_mac_mac on user_mac (mac,changed DESC);
CREATE INDEX ix_user_mac_id on user_mac (id);

CREATE VIEW public_visit AS
SELECT id, nick, enter, leave, stealth
FROM visit v
JOIN user u ON (SELECT id
                FROM user_mac m
                WHERE m.mac=v.mac AND changed<leave
                ORDER BY changed DESC
                LIMIT 1
               )=u.id
WHERE COALESCE(u.flappiness<=v.renewals, 1);

END;
