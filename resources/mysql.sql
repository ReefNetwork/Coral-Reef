/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

-- #!mysql
-- #{ coral_reef
-- #    { init.tables
-- #        { user
CREATE TABLE IF NOT EXISTS USER
(
    xuid       BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    name       VARCHAR(100)    NOT NULL,
    ips        VARCHAR(9999)   NOT NULL,
    experience BIGINT UNSIGNED NOT NULL,
    skill      VARCHAR(99)
);
-- #        }
-- #        { ban
CREATE TABLE IF NOT EXISTS BAN
(
    PRIMARY KEY (type, value),
    type   ENUM ('ALL','XUID','IP') NOT NULL,
    value  VARCHAR(20)              NOT NULL,
    reason VARCHAR(999)             NOT NULL,
    time   DATETIME
);
-- #        }
-- #        { warp
CREATE TABLE IF NOT EXISTS WARP
(
    PRIMARY KEY (xuid, name),
    xuid  BIGINT UNSIGNED NOT NULL,
    name  VARCHAR(99)     NOT NULL,
    level VARCHAR(99)     NOT NULL,
    x     INT             NOT NULL,
    y     int             not null,
    z     int             not null
);
-- #        }
-- #        { land
CREATE TABLE IF NOT EXISTS LAND
(
    PRIMARY KEY (xuid, name),
    XUID  BIGINT UNSIGNED NOT NULL,
    name  VARCHAR(99)     NOT NULL,
    level VARCHAR(99)     NOT NULL,
    mx    INT             NOT NULL,
    sx    INT             NOT NULL,
    mz    INT             NOT NULL,
    SZ    INT             NOT NULL
);
-- #        }
-- #        { virtual_value
CREATE TABLE IF NOT EXISTS VIRTUAL_VALUES
(
    PRIMARY KEY (xuid, type, subtype),
    xuid    BIGINT UNSIGNED NOT NULL,
    type    VARCHAR(99)     NOT NULL,
    subtype VARCHAR(99)     NOT NULL,
    value   VARCHAR(99)
);
-- #        }
-- #    }
-- #    { user
-- #        { get
SELECT *
FROM USER
WHERE xuid = :xuid;
-- #        }
-- #        { get_ip
SELECT ips
FROM USER
WHERE xuid = :xuid;
-- #        }
-- #        { set
-- #            { account
INSERT INTO USER
VALUES (:xuid, :name, :ips, 0, null)
ON DUPLICATE KEY UPDATE name = :name,
                        ips  = :ips;
-- #            }
-- #            { xp
UPDATE USER
SET experience = :experience
WHERE xuid = :xuid;
-- #            }
-- #            { skill
UPDATE USER
SET skill = :skill
WHERE xuid = :xuid;
-- #            }
-- #        }
-- #    }
-- #    { values
-- #        { get
-- #            { one
SELECT value
FROM VIRTUAL_VALUES
WHERE xuid = :xuid
  AND type = :type
  AND subtype = :subtype;
-- #            }
-- #            { all_subtype
SELECT subtype, value
FROM VIRTUAL_VALUES
WHERE xuid = :xuid
  AND type = :type;
-- #            }
-- #        }
-- #        { set
INSERT INTO VIRTUAL_VALUES
VALUES (:xuid, :colum, :type, :value)
ON DUPLICATE KEY UPDATE value = :value;
-- #        }
-- #    }
-- #    { warp
-- #        { get
SELECT name, level, x, y, z
FROM WARP
WHERE xuid = :xuid;
-- #        }
-- #        { create
INSERT INTO WARP
VALUES (:xuid, :name, :level, :x, :y, :z)
ON DUPLICATE KEY UPDATE level = :level,
                        x     = :x,
                        y     = :y,
                        z     = :z;
-- #        }
-- #        { delete
DELETE
FROM WARP
WHERE xuid = :xuid
  AND name = :name;
-- #        }
-- #    }
-- #    { land
-- #        { get
SELECT *
FROM LAND;
-- #        }
-- #        { create
INSERT INTO LAND
VALUES (:xuid, :name, :level, :mx, :sx, :mz, :sz);
-- #        }
-- #        { delete
DELETE
FROM LAND
WHERE xuid = :xuid
  AND name = :name;
-- #        }
-- #    }
-- #}
