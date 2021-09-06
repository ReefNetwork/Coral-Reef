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
    XUID       BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    NAME       VARCHAR(100)    NOT NULL,
    IPS        VARCHAR(9999)   NOT NULL,
    EXPERIENCE BIGINT UNSIGNED NOT NULL,
    SKILL      VARCHAR(99)
);
-- #        }
-- #        { ban
CREATE TABLE IF NOT EXISTS BAN
(
    PRIMARY KEY (TYPE, VALUE),
    TYPE   ENUM ('ALL','XUID','IP') NOT NULL,
    VALUE  VARCHAR(20)              NOT NULL,
    REASON VARCHAR(999)             NOT NULL,
    TIME   DATETIME
);
-- #        }
-- #        { whitelist
CREATE TABLE IF NOT EXISTS WHITELIST
(
    PRIMARY KEY (TYPE, VALUE),
    TYPE   ENUM ('XUID','IP') NOT NULL,
    VALUE  VARCHAR(20)        NOT NULL,
    REASON VARCHAR(999)       NOT NULL,
    TIME   DATETIME
);
-- #        }
-- #        { warp
CREATE TABLE IF NOT EXISTS WARP
(
    PRIMARY KEY (XUID, NAME),
    XUID  BIGINT UNSIGNED NOT NULL,
    NAME  VARCHAR(99)     NOT NULL,
    LEVEL VARCHAR(99)     NOT NULL,
    X     INT             NOT NULL,
    Y     INT             NOT NULL,
    Z     INT             NOT NULL
);
-- #        }
-- #        { land
CREATE TABLE IF NOT EXISTS LAND
(
    PRIMARY KEY (XUID, NAME),
    XUID  BIGINT UNSIGNED NOT NULL,
    NAME  VARCHAR(99)     NOT NULL,
    LEVEL VARCHAR(99)     NOT NULL,
    MX    INT             NOT NULL,
    SX    INT             NOT NULL,
    MZ    INT             NOT NULL,
    SZ    INT             NOT NULL
);
-- #        }
-- #        { virtual_value
CREATE TABLE IF NOT EXISTS VIRTUAL_VALUES
(
    PRIMARY KEY (XUID, TYPE, SUBTYPE),
    XUID    BIGINT UNSIGNED NOT NULL,
    TYPE    VARCHAR(99)     NOT NULL,
    SUBTYPE VARCHAR(99)     NOT NULL,
    VALUE   VARCHAR(99)
);
-- #        }
-- #    }
-- #    { user
-- #        { get
SELECT *
FROM USER
WHERE XUID = :xuid;
-- #        }
-- #        { set
INSERT INTO USER
VALUES (:xuid, :name, :ips, 0, null)
ON DUPLICATE KEY UPDATE NAME = :name,
                        IPS  = :ips;
-- #        }
-- #    }
-- #}
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
-- #
