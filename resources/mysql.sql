-- #!mysql
-- #{ coral_reef
-- #    { init
-- #        { functions
-- #            { add_value
-- #                { create
CREATE PROCEDURE add_value(IN _xuid BIGINT, IN _type VARCHAR(99), IN _subtype VARCHAR(99), IN _value INT)
BEGIN
    SELECT value
    INTO @get_value
    FROM VIRTUAL_VALUES
    WHERE xuid = _xuid
      AND type = _type
      AND subtype = _subtype;

    SET @int_value = CAST(@get_value AS SIGNED) + _value;

    INSERT INTO VIRTUAL_VALUES
    VALUES (_xuid, _type, _subtype, @int_value)
    ON DUPLICATE KEY UPDATE value = @int_value;
END;
-- #                }
-- #                { reset
DROP PROCEDURE IF EXISTS add_value;
-- #                }
-- #            }
-- #        }
-- #        { tables
-- #            { user
CREATE TABLE IF NOT EXISTS USER
(
    xuid       BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    name       VARCHAR(100)    NOT NULL,
    ips        VARCHAR(9999)   NOT NULL,
    experience BIGINT UNSIGNED NOT NULL,
    skill      VARCHAR(99)
);
-- #            }
-- #            { ban
CREATE TABLE IF NOT EXISTS BAN
(
    PRIMARY KEY (type, value),
    type   ENUM ('XUID','IP') NOT NULL,
    value  VARCHAR(20)        NOT NULL,
    reason VARCHAR(999)       NOT NULL,
    time   DATETIME
);
-- #            }
-- #            { warp
CREATE TABLE IF NOT EXISTS WARP
(
    PRIMARY KEY (xuid, name),
    xuid   BIGINT UNSIGNED NOT NULL,
    name   VARCHAR(99)     NOT NULL,
    server VARCHAR(99)     NOT NULL,
    level  VARCHAR(99)     NOT NULL,
    x      INT             NOT NULL,
    y      INT             not null,
    z      INT             not null
);
-- #            }
-- #            { land
CREATE TABLE IF NOT EXISTS LAND
(
    PRIMARY KEY (xuid, name),
    xuid   BIGINT UNSIGNED NOT NULL,
    name   VARCHAR(99)     NOT NULL,
    server VARCHAR(99)     NOT NULL,
    level  VARCHAR(99)     NOT NULL,
    mx     INT             NOT NULL,
    sx     INT             NOT NULL,
    mz     INT             NOT NULL,
    sz     INT             NOT NULL
);
-- #            }
-- #            { virtual_value
CREATE TABLE IF NOT EXISTS VIRTUAL_VALUES
(
    PRIMARY KEY (xuid, type, subtype),
    xuid    BIGINT UNSIGNED NOT NULL,
    type    VARCHAR(99)     NOT NULL,
    subtype VARCHAR(99)     NOT NULL,
    value   VARCHAR(9999)
);
-- #            }
-- #            { log
CREATE TABLE IF NOT EXISTS LOG
(
    xuid    BIGINT UNSIGNED NOT NULL,
    type    VARCHAR(99)     NOT NULL,
    subtype VARCHAR(99),
    value   VARCHAR(99)     NOT NULL,
    time    DATETIME
);
-- #            }
-- #        }
-- #    }
-- #    { user
-- #        { all
SELECT *
FROM USER;
-- #        }
-- #        { get
-- #        :xuid int
SELECT *
FROM USER
WHERE xuid = :xuid;
-- #        }
-- #        { get_ip
-- #        :xuid int
SELECT ips
FROM USER
WHERE xuid = :xuid;
-- #        }
-- #        { set
-- #            { account
-- #            :xuid int
-- #            :name string
-- #            :ips string
INSERT INTO USER
VALUES (:xuid, :name, :ips, 0, null)
ON DUPLICATE KEY UPDATE name = :name,
                        ips  = :ips;
-- #            }
-- #            { xp
-- #            :xuid int
-- #            :experience int
UPDATE USER
SET experience = :experience
WHERE xuid = :xuid;
-- #            }
-- #            { skill
-- #            :xuid int
-- #            :skill ?string
UPDATE USER
SET skill = :skill
WHERE xuid = :xuid;
-- #            }
-- #        }
-- #    }
-- #    { ban
-- #        { get
SELECT *
FROM BAN;
-- #        }
-- #    }
-- #    { values
-- #        { get
-- #            { one
-- #            :xuid int
-- #            :type string
-- #            :subtype string
SELECT value
FROM VIRTUAL_VALUES
WHERE xuid = :xuid
  AND type = :type
  AND subtype = :subtype;
-- #            }
-- #            { all_subtype
-- #            :xuid int
-- #            :type string
SELECT subtype, value
FROM VIRTUAL_VALUES
WHERE xuid = :xuid
  AND type = :type;
-- #            }
-- #        }
-- #        { add
-- #        :xuid int
-- #        :type string
-- #        :subtype string
-- #        :value int
CALL add_value(:xuid, :type, :subtype, :value);
-- #        }
-- #        { set
-- #        :xuid int
-- #        :type string
-- #        :subtype string
-- #        :value ?string
INSERT INTO VIRTUAL_VALUES
VALUES (:xuid, :type, :subtype, :value)
ON DUPLICATE KEY UPDATE value = :value;
-- #        }
-- #        { delete
-- #        :xuid int
-- #        :type string
-- #        :subtype string
DELETE
FROM VIRTUAL_VALUES
WHERE xuid = :xuid
  AND type = :type
  AND subtype = :subtype;
-- #        }
-- #    }
-- #    { warp
-- #        { get
-- #        :xuid int
-- #        :server string
SELECT name, level, x, y, z
FROM WARP
WHERE xuid = :xuid
  AND server = :server;
-- #        }
-- #        { create
-- #        :xuid int
-- #        :name string
-- #        :server string
-- #        :level string
-- #        :x int
-- #        :y int
-- #        :z int
INSERT INTO WARP
VALUES (:xuid, :name, :server, :level, :x, :y, :z)
ON DUPLICATE KEY UPDATE level = :level,
                        x     = :x,
                        y     = :y,
                        z     = :z;
-- #        }
-- #        { delete
-- #        :xuid int
-- #        :name string
-- #        :server string
DELETE
FROM WARP
WHERE xuid = :xuid
  AND name = :name
  AND server = :server;
-- #        }
-- #    }
-- #    { land
-- #        { get
-- #        :server string
SELECT *
FROM LAND
WHERE server = :server;
-- #        }
-- #        { create
-- #        :xuid int
-- #        :name string
-- #        :server string
-- #        :level string
-- #        :mx int
-- #        :sx int
-- #        :mz int
-- #        :sz int
INSERT INTO LAND
VALUES (:xuid, :name, :server, :level, :mx, :sx, :mz, :sz);
-- #        }
-- #        { delete
-- #        :xuid int
-- #        :name string
-- #        :server string
DELETE
FROM LAND
WHERE xuid = :xuid
  AND name = :name
  AND server = :server;
-- #        }
-- #    }
-- #    { log
-- #        { add
-- #        :xuid int
-- #        :type string
-- #        :subtype string
-- #        :value string
-- #        :time string
INSERT INTO LOG
VALUES (:xuid, :type, :subtype, :value, :time);
-- #        }
-- #        { get
-- #            { all
-- #            :xuid int
SELECT *
FROM LOG
WHERE xuid = :xuid;
-- #            }
-- #            { type
-- #        :xuid int
-- #        :type string
SELECT *
FROM LOG
WHERE xuid = :xuid
  AND type = :type;
-- #            }
-- #        }
-- #    }
-- #}
