# Coral Reef

[![Build and Deploy](https://github.com/ReefNetwork/Coral-Reef/actions/workflows/build.yml/badge.svg)](https://github.com/ReefNetwork/Coral-Reef/actions/workflows/build.yml)
[![Feature Merge](https://github.com/ReefNetwork/Coral-Reef/actions/workflows/feature.yml/badge.svg)](https://github.com/ReefNetwork/Coral-Reef/actions/workflows/feature.yml)
[![Stable](https://github.com/ReefNetwork/Coral-Reef/actions/workflows/stable.yml/badge.svg)](https://github.com/ReefNetwork/Coral-Reef/actions/workflows/stable.yml)
[![Discord](https://img.shields.io/discord/638760361369010177?logo=discord)](https://discord.gg/M4A6cak)

Reef Seichi Server Plugin

## Documents

[GitHub Actions](docs/GitHubActions.md)  
[コミットルール](docs/Commit.md)

## Config

```yaml
#サーバーの区別ID
server_0: seichi_1

# サーバーの表示名
server: "てすと"

# 起動時にsql系のセットアップをするか
is_sql_init: true
```

## MySQL

データベースとユーザーの作成

```mysql
CREATE DATABASE CoralReef;
CREATE USER pmmp IDENTIFIED BY 'password';
GRANT ALL on CoralReef.* to pmmp;
```

### sql.yml

```
database:
  type: mysql
  mysql:
    host: 127.0.0.1
    username: pmmp
    password: "password"
    schema: CoralReef
  worker-limit: 5
```

## Composer

```
composer install
```
