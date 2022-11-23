-- -*- mode: sql; sql-product: sqlite; -*-
BEGIN;
ALTER TABLE user ADD stealth INTEGER NOT NULL DEFAULT FALSE;

DROP VIEW public_visit;
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
